<?php

declare(strict_types=1);

final class SolicitudAperturaMateria
{
    public function futurePeriods(): array
    {
        return Database::connection()->query(
            "SELECT id_periodo, nombre_periodo, fecha_inicio, fecha_fin
             FROM periodos_tutoria
             WHERE estado = 'publicado' AND fecha_inicio > CURRENT_DATE AND id_tipo_tutoria IS NOT NULL
             ORDER BY fecha_inicio, nombre_periodo"
        )->fetchAll();
    }

    public function activeTurns(): array
    {
        return (new Turno())->all(false);
    }

    public function availableSubjects(int $studentId, int $periodId, int $turnId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT m.id_materia, m.nombre_materia, c.nombre_carrera
             FROM estudiantes e
             INNER JOIN materias m ON m.id_carrera = e.id_carrera OR m.id_carrera IS NULL
             LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
             INNER JOIN periodos_tutoria p ON p.id_periodo = :period_id
             INNER JOIN turnos t ON t.id_turno = :turn_id AND t.estado = 'activo'
             WHERE e.id_estudiante = :student_id
               AND p.estado = 'publicado' AND p.fecha_inicio > CURRENT_DATE
               AND p.id_tipo_tutoria IS NOT NULL
               AND NOT EXISTS (
                    SELECT 1 FROM ofertas_tutoria o
                    WHERE o.id_periodo = p.id_periodo
                      AND o.id_materia = m.id_materia
                      AND o.id_turno = t.id_turno
                      AND o.estado = 'publicada'
               )
               AND NOT EXISTS (
                    SELECT 1 FROM solicitudes_apertura_materia s
                    WHERE s.id_estudiante = e.id_estudiante
                      AND s.id_periodo = p.id_periodo
                      AND s.id_materia = m.id_materia
                      AND s.id_turno = t.id_turno
                      AND s.estado IN ('pendiente', 'aprobada')
               )
             ORDER BY m.nombre_materia"
        );
        $statement->execute([
            'student_id' => $studentId,
            'period_id' => $periodId,
            'turn_id' => $turnId,
        ]);
        return $statement->fetchAll();
    }

    public function ownRequests(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT s.id_solicitud, s.id_periodo, s.id_materia, s.id_turno, s.motivo, s.estado,
                    s.observaciones_revision, s.fecha_solicitud, s.fecha_revision,
                    p.nombre_periodo, p.fecha_inicio, m.nombre_materia, t.nombre_turno,
                    o.id_oferta, o.estado AS estado_oferta
             FROM solicitudes_apertura_materia s
             INNER JOIN periodos_tutoria p ON p.id_periodo = s.id_periodo
             INNER JOIN materias m ON m.id_materia = s.id_materia
             INNER JOIN turnos t ON t.id_turno = s.id_turno
             LEFT JOIN ofertas_tutoria o ON o.id_oferta = s.id_oferta_generada
             WHERE s.id_estudiante = :id_estudiante
             ORDER BY s.fecha_solicitud DESC, s.id_solicitud DESC"
        );
        $statement->execute(['id_estudiante' => $studentId]);
        return $statement->fetchAll();
    }

    public function allForReview(): array
    {
        return Database::connection()->query(
            "SELECT s.id_solicitud, s.id_estudiante, s.id_periodo, s.id_materia, s.id_turno,
                    s.motivo, s.estado, s.observaciones_revision, s.fecha_solicitud, s.fecha_revision,
                    p.nombre_periodo, p.fecha_inicio, m.nombre_materia, c.nombre_carrera, t.nombre_turno,
                    CONCAT(u.nombre, ' ', u.apellido) AS estudiante, u.correo,
                    CONCAT(r.nombre, ' ', r.apellido) AS revisor, o.id_oferta, o.estado AS estado_oferta,
                    (SELECT COUNT(*) FROM solicitudes_apertura_materia d
                     WHERE d.id_periodo = s.id_periodo AND d.id_materia = s.id_materia AND d.id_turno = s.id_turno
                       AND d.estado IN ('pendiente', 'aprobada')) AS demanda
             FROM solicitudes_apertura_materia s
             INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             INNER JOIN periodos_tutoria p ON p.id_periodo = s.id_periodo
             INNER JOIN materias m ON m.id_materia = s.id_materia
             LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
             INNER JOIN turnos t ON t.id_turno = s.id_turno
             LEFT JOIN usuarios r ON r.id_usuario = s.revisado_por
             LEFT JOIN ofertas_tutoria o ON o.id_oferta = s.id_oferta_generada
             ORDER BY FIELD(s.estado, 'pendiente', 'aprobada', 'rechazada'), s.fecha_solicitud DESC"
        )->fetchAll();
    }

    public function create(int $studentId, int $periodId, int $subjectId, int $turnId, string $reason): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $studentStatement = $pdo->prepare(
                'SELECT id_carrera FROM estudiantes WHERE id_estudiante = :id_estudiante FOR UPDATE'
            );
            $studentStatement->execute(['id_estudiante' => $studentId]);
            $student = $studentStatement->fetch();
            if (!$student) {
                throw new RuntimeException('No se encontró el perfil de estudiante asociado a la cuenta.');
            }

            $periodStatement = $pdo->prepare(
                "SELECT id_tipo_tutoria FROM periodos_tutoria
                 WHERE id_periodo = :id_periodo AND estado = 'publicado'
                   AND fecha_inicio > CURRENT_DATE AND id_tipo_tutoria IS NOT NULL
                 FOR UPDATE"
            );
            $periodStatement->execute(['id_periodo' => $periodId]);
            if (!$periodStatement->fetchColumn()) {
                throw new RuntimeException('Seleccione un periodo futuro publicado con tipo de tutoría configurado.');
            }

            $subjectStatement = $pdo->prepare(
                'SELECT 1 FROM materias WHERE id_materia = :id_materia AND (id_carrera = :id_carrera OR id_carrera IS NULL) LIMIT 1'
            );
            $subjectStatement->execute(['id_materia' => $subjectId, 'id_carrera' => (int) $student['id_carrera']]);
            if (!$subjectStatement->fetchColumn()) {
                throw new RuntimeException('La materia no pertenece a la carrera del estudiante.');
            }

            $turnStatement = $pdo->prepare("SELECT 1 FROM turnos WHERE id_turno = :id_turno AND estado = 'activo' LIMIT 1");
            $turnStatement->execute(['id_turno' => $turnId]);
            if (!$turnStatement->fetchColumn()) {
                throw new RuntimeException('Seleccione un turno activo.');
            }

            $publishedStatement = $pdo->prepare(
                "SELECT 1 FROM ofertas_tutoria
                 WHERE id_periodo = :id_periodo AND id_materia = :id_materia
                   AND id_turno = :id_turno AND estado = 'publicada'
                 LIMIT 1"
            );
            $publishedStatement->execute(['id_periodo' => $periodId, 'id_materia' => $subjectId, 'id_turno' => $turnId]);
            if ($publishedStatement->fetchColumn()) {
                throw new RuntimeException('Esa materia ya tiene una oferta publicada en el turno elegido.');
            }

            $duplicateStatement = $pdo->prepare(
                "SELECT 1 FROM solicitudes_apertura_materia
                 WHERE id_estudiante = :id_estudiante AND id_periodo = :id_periodo
                   AND id_materia = :id_materia AND id_turno = :id_turno
                   AND estado IN ('pendiente', 'aprobada')
                 LIMIT 1"
            );
            $duplicateStatement->execute([
                'id_estudiante' => $studentId,
                'id_periodo' => $periodId,
                'id_materia' => $subjectId,
                'id_turno' => $turnId,
            ]);
            if ($duplicateStatement->fetchColumn()) {
                throw new RuntimeException('Ya existe una solicitud pendiente o aprobada para esa materia, periodo y turno.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO solicitudes_apertura_materia
                    (id_estudiante, id_periodo, id_materia, id_turno, motivo)
                 VALUES (:id_estudiante, :id_periodo, :id_materia, :id_turno, :motivo)'
            );
            $insert->execute([
                'id_estudiante' => $studentId,
                'id_periodo' => $periodId,
                'id_materia' => $subjectId,
                'id_turno' => $turnId,
                'motivo' => $reason !== '' ? $reason : null,
            ]);
            $pdo->commit();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (str_starts_with((string) $exception->getCode(), '23')) {
                throw new RuntimeException('Ya existe una solicitud activa para esa materia, periodo y turno.');
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function review(int $requestId, string $state, int $reviewerId, string $notes): void
    {
        if (!in_array($state, ['aprobada', 'rechazada'], true)) {
            throw new InvalidArgumentException('Seleccione una decisión válida.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $requestStatement = $pdo->prepare(
                'SELECT id_solicitud, id_estudiante, id_periodo, id_materia, id_turno, estado
                 FROM solicitudes_apertura_materia WHERE id_solicitud = :id FOR UPDATE'
            );
            $requestStatement->execute(['id' => $requestId]);
            $request = $requestStatement->fetch();
            if (!$request) {
                throw new RuntimeException('La solicitud no existe.');
            }
            if ($request['estado'] !== 'pendiente') {
                throw new RuntimeException('Solo se pueden revisar solicitudes pendientes.');
            }

            $offerId = null;
            if ($state === 'aprobada') {
                // Serializar aprobaciones del mismo periodo para no crear ofertas pendientes duplicadas.
                $period = $pdo->prepare(
                    'SELECT id_tipo_tutoria FROM periodos_tutoria WHERE id_periodo = :id_periodo FOR UPDATE'
                );
                $period->execute(['id_periodo' => $request['id_periodo']]);
                $typeId = $period->fetchColumn();
                if (!$typeId) {
                    throw new RuntimeException('El periodo no tiene tipo de tutoría configurado; no se puede crear la oferta pendiente.');
                }

                $published = $pdo->prepare(
                    "SELECT id_oferta FROM ofertas_tutoria
                     WHERE id_periodo = :id_periodo AND id_materia = :id_materia
                       AND id_turno = :id_turno AND estado = 'publicada'
                     LIMIT 1 FOR UPDATE"
                );
                $published->execute([
                    'id_periodo' => $request['id_periodo'],
                    'id_materia' => $request['id_materia'],
                    'id_turno' => $request['id_turno'],
                ]);
                if ($published->fetchColumn()) {
                    throw new RuntimeException('Ya existe una oferta publicada para esa materia, periodo y turno. Rechace la solicitud o revise el caso.');
                }

                $turn = $pdo->prepare("SELECT 1 FROM turnos WHERE id_turno = :id_turno AND estado = 'activo' LIMIT 1");
                $turn->execute(['id_turno' => $request['id_turno']]);
                if (!$turn->fetchColumn()) {
                    throw new RuntimeException('El turno solicitado ya no está activo.');
                }

                $draft = $pdo->prepare(
                    "SELECT id_oferta FROM ofertas_tutoria
                     WHERE id_periodo = :id_periodo AND id_materia = :id_materia
                       AND id_turno = :id_turno AND estado = 'pendiente'
                     ORDER BY id_oferta LIMIT 1 FOR UPDATE"
                );
                $draft->execute([
                    'id_periodo' => $request['id_periodo'],
                    'id_materia' => $request['id_materia'],
                    'id_turno' => $request['id_turno'],
                ]);
                $existingDraftId = $draft->fetchColumn();
                if ($existingDraftId !== false) {
                    $offerId = (int) $existingDraftId;
                } else {
                    $insertOffer = $pdo->prepare(
                        "INSERT INTO ofertas_tutoria
                            (id_periodo, id_materia, id_tipo_tutoria, id_turno, frecuencia_programacion,
                             nombre_grupo, cupo, descripcion, estado)
                         VALUES
                            (:id_periodo, :id_materia, :id_tipo_tutoria, :id_turno, 'semanal',
                              :nombre_grupo, 20, :descripcion, 'pendiente')"
                    );
                    $insertOffer->execute([
                        'id_periodo' => $request['id_periodo'],
                        'id_materia' => $request['id_materia'],
                        'id_tipo_tutoria' => $typeId,
                        'id_turno' => $request['id_turno'],
                        'nombre_grupo' => 'Solicitud ' . (int) $request['id_solicitud'],
                        'descripcion' => 'Oferta pendiente creada al aprobar la solicitud #' . (int) $request['id_solicitud'] . '. Completar calendario, aula y cupos antes de publicar.',
                    ]);
                    $offerId = (int) $pdo->lastInsertId();
                }
            }

            $update = $pdo->prepare(
                'UPDATE solicitudes_apertura_materia
                 SET estado = :estado, id_oferta_generada = :id_oferta, revisado_por = :revisor,
                     observaciones_revision = :observaciones, fecha_revision = CURRENT_TIMESTAMP
                 WHERE id_solicitud = :id'
            );
            $update->execute([
                'estado' => $state,
                'id_oferta' => $offerId,
                'revisor' => $reviewerId,
                'observaciones' => $notes !== '' ? $notes : null,
                'id' => $requestId,
            ]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
