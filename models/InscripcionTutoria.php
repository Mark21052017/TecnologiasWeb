<?php

declare(strict_types=1);

final class InscripcionTutoria
{
    public function allForViewer(string $role, int $userId): array
    {
        $sql = <<<'SQL'
            SELECT i.id_inscripcion, i.id_oferta, i.id_oferta_tutor, i.id_oferta_horario,
                    i.estado, i.fecha_inscripcion, ot.estado AS estado_tutor,
                   p.nombre_periodo, p.fecha_inicio, p.fecha_fin,
                   m.nombre_materia, o.nombre_grupo, o.cupo,
                   CONCAT(tu.nombre, ' ', tu.apellido) AS tutor,
                   oh.dia_semana, b.nombre_turno, b.hora_inicio, b.hora_fin,
                   a.nombre_aula, a.ubicacion,
                   CONCAT(eu.nombre, ' ', eu.apellido) AS estudiante
            FROM inscripciones_tutoria i
            INNER JOIN ofertas_tutoria o ON o.id_oferta = i.id_oferta
            INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
            INNER JOIN materias m ON m.id_materia = o.id_materia
             LEFT JOIN oferta_tutores ot ON ot.id_oferta_tutor = i.id_oferta_tutor
             LEFT JOIN tutores t ON t.id_tutor = ot.id_tutor
             LEFT JOIN usuarios tu ON tu.id_usuario = t.id_usuario
            INNER JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
            INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
             LEFT JOIN oferta_horarios oh ON oh.id_oferta_horario = i.id_oferta_horario
              INNER JOIN turnos b ON b.id_turno = o.id_turno
            LEFT JOIN aulas a ON a.id_aula = oh.id_aula
        SQL;
        $params = [];
        if ($role === 'estudiante') {
            $sql .= ' WHERE eu.id_usuario = :user_id';
            $params['user_id'] = $userId;
        } elseif ($role === 'tutor') {
            $sql .= ' WHERE tu.id_usuario = :user_id AND i.id_oferta_tutor IS NOT NULL';
            $params['user_id'] = $userId;
        }
        $sql .= ' ORDER BY p.fecha_inicio DESC, m.nombre_materia, i.fecha_inscripcion DESC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function confirmedTutorOptions(array $offerIds): array
    {
        $offerIds = array_values(array_unique(array_filter(array_map('intval', $offerIds))));
        if (!$offerIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($offerIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT ot.id_oferta_tutor, ot.id_oferta, CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                    t.especialidad
             FROM oferta_tutores ot
             INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
             INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
             INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
             INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
             WHERE ot.id_oferta IN ($placeholders)
               AND ot.estado = 'confirmada'
               AND o.estado IN ('publicada', 'cerrada')
               AND p.estado IN ('publicado', 'cerrado')
               AND p.fecha_fin >= CURRENT_DATE
               AND EXISTS (
                   SELECT 1 FROM oferta_horarios oh
                   INNER JOIN oferta_tutor_horarios oth
                       ON oth.id_oferta_horario = oh.id_oferta_horario
                      AND oth.id_oferta_tutor = ot.id_oferta_tutor
                   WHERE oh.id_oferta = ot.id_oferta
               )
             ORDER BY tutor, ot.id_oferta_tutor"
        );
        $statement->execute($offerIds);
        $options = [];
        foreach ($statement->fetchAll() as $row) {
            $options[(int) $row['id_oferta']][] = $row;
        }
        return $options;
    }

    public function assignTutor(int $enrollmentId, int $offerTutorId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $enrollmentQuery = $pdo->prepare(
                "SELECT id_oferta, id_estudiante, id_oferta_tutor, estado FROM inscripciones_tutoria
                 WHERE id_inscripcion = :id FOR UPDATE"
            );
            $enrollmentQuery->execute(['id' => $enrollmentId]);
            $enrollment = $enrollmentQuery->fetch();
            if (!$enrollment || $enrollment['estado'] !== 'inscrita') {
                throw new RuntimeException('Solo se puede asignar un tutor a una inscripción activa.');
            }
            if ($enrollment['id_oferta_tutor'] !== null) {
                throw new RuntimeException('La inscripción ya tiene un tutor asignado.');
            }

            $assignmentQuery = $pdo->prepare(
                "SELECT ot.id_oferta_tutor, ot.id_tutor, u.nombre, u.apellido
                 FROM oferta_tutores ot
                 INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
                 INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
                 INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
                 INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
                 WHERE ot.id_oferta_tutor = :id_asignacion
                   AND ot.id_oferta = :id_oferta
                   AND ot.estado = 'confirmada'
                   AND o.estado IN ('publicada', 'cerrada')
                   AND p.estado IN ('publicado', 'cerrado')
                   AND p.fecha_fin >= CURRENT_DATE
                   AND EXISTS (SELECT 1 FROM oferta_horarios any_schedule WHERE any_schedule.id_oferta = o.id_oferta)
                   AND NOT EXISTS (
                       SELECT 1 FROM oferta_horarios oh
                       WHERE oh.id_oferta = o.id_oferta
                         AND NOT EXISTS (
                             SELECT 1 FROM oferta_tutor_horarios oth
                             WHERE oth.id_oferta_tutor = ot.id_oferta_tutor
                               AND oth.id_oferta_horario = oh.id_oferta_horario
                         )
                   )
                 LIMIT 1 FOR UPDATE"
            );
            $assignmentQuery->execute([
                'id_asignacion' => $offerTutorId,
                'id_oferta' => (int) $enrollment['id_oferta'],
            ]);
            $assignment = $assignmentQuery->fetch(PDO::FETCH_ASSOC);
            if (!$assignment) {
                throw new RuntimeException('Seleccione un tutor confirmado para esta misma oferta y con todos sus horarios aceptados.');
            }

            $update = $pdo->prepare(
                'UPDATE inscripciones_tutoria
                 SET id_oferta_tutor = :id_oferta_tutor, id_oferta_horario = NULL
                 WHERE id_inscripcion = :id AND estado = \'inscrita\' AND id_oferta_tutor IS NULL'
            );
            $update->execute(['id_oferta_tutor' => $offerTutorId, 'id' => $enrollmentId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('La inscripción cambió antes de asignar el tutor. Recargue la página.');
            }
            $studentUser = $pdo->prepare('SELECT id_usuario FROM estudiantes WHERE id_estudiante = :id LIMIT 1');
            $studentUser->execute(['id' => (int) $enrollment['id_estudiante']]);
            $studentUserId = (int) $studentUser->fetchColumn();
            $notifications = new Notificacion();
            $notifications->add(
                $pdo, $studentUserId, 'tutor_asignado', 'Tutor asignado',
                'Administración asignó a ' . trim($assignment['nombre'] . ' ' . $assignment['apellido']) . ' para acompañarte en esta tutoría.',
                'inscripciones/', 'tutor-assigned:' . $enrollmentId . ':' . $offerTutorId . ':student'
            );
            $tutorUser = $pdo->prepare('SELECT id_usuario FROM tutores WHERE id_tutor = :id LIMIT 1');
            $tutorUser->execute(['id' => (int) $assignment['id_tutor']]);
            $notifications->add(
                $pdo, (int) $tutorUser->fetchColumn(), 'estudiante_asignado', 'Nueva asignación de tutoría',
                'Administración te asignó a una inscripción de tutoría.',
                'inscripciones/', 'tutor-assigned:' . $enrollmentId . ':' . $offerTutorId . ':tutor'
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function detailForAdmin(int $enrollmentId): ?array
    {
        $statement = Database::connection()->prepare(
            "SELECT focus.id_inscripcion, focus.id_oferta_tutor, focus.id_oferta_horario, focus.estado AS inscripcion_estado,
                    o.id_oferta, o.nombre_grupo, o.cupo,
                    p.nombre_periodo, p.fecha_inicio, p.fecha_fin,
                    m.nombre_materia,
                    CASE WHEN ot.id_oferta_tutor IS NULL THEN NULL ELSE CONCAT(tu.nombre, ' ', tu.apellido) END AS tutor,
                    oh.id_oferta_horario, oh.dia_semana,
                     b.nombre_turno, b.hora_inicio, b.hora_fin,
                    a.nombre_aula, a.ubicacion,
                    COUNT(CASE WHEN enrolled.estado = 'inscrita' THEN enrolled.id_inscripcion END) AS inscritos
             FROM inscripciones_tutoria focus
             INNER JOIN ofertas_tutoria o ON o.id_oferta = focus.id_oferta
             INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
             INNER JOIN materias m ON m.id_materia = o.id_materia
              LEFT JOIN oferta_tutores ot ON ot.id_oferta_tutor = focus.id_oferta_tutor
              LEFT JOIN tutores t ON t.id_tutor = ot.id_tutor
              LEFT JOIN usuarios tu ON tu.id_usuario = t.id_usuario
              LEFT JOIN oferta_horarios oh ON oh.id_oferta_horario = focus.id_oferta_horario
              INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
              LEFT JOIN inscripciones_tutoria enrolled
                 ON enrolled.id_oferta = focus.id_oferta
                AND enrolled.estado = 'inscrita'
                AND ((focus.id_oferta_tutor IS NULL AND enrolled.id_oferta_tutor IS NULL)
                     OR (focus.id_oferta_tutor IS NOT NULL AND enrolled.id_oferta_tutor = focus.id_oferta_tutor))
              WHERE focus.id_inscripcion = :id_inscripcion
              GROUP BY focus.id_inscripcion, focus.id_oferta_tutor, focus.id_oferta_horario, focus.estado, o.id_oferta, o.nombre_grupo, o.cupo,
                      p.nombre_periodo, p.fecha_inicio, p.fecha_fin, m.nombre_materia,
                      tu.nombre, tu.apellido, oh.id_oferta_horario, oh.dia_semana,
                       b.nombre_turno, b.hora_inicio, b.hora_fin, a.nombre_aula, a.ubicacion
             LIMIT 1"
        );
        $statement->execute(['id_inscripcion' => $enrollmentId]);
        $detail = $statement->fetch();

        return $detail ?: null;
    }

    public function studentsForDetail(int $enrollmentId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT e.registro_universitario,
                    CONCAT(eu.nombre, ' ', eu.apellido) AS estudiante,
                    c.nombre_carrera, enrolled.fecha_inscripcion, enrolled.estado
             FROM inscripciones_tutoria focus
             INNER JOIN inscripciones_tutoria enrolled
                ON enrolled.id_oferta = focus.id_oferta
               AND enrolled.estado = 'inscrita'
               AND ((focus.id_oferta_tutor IS NULL AND enrolled.id_oferta_tutor IS NULL)
                    OR (focus.id_oferta_tutor IS NOT NULL AND enrolled.id_oferta_tutor = focus.id_oferta_tutor))
             INNER JOIN estudiantes e ON e.id_estudiante = enrolled.id_estudiante
             INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
             INNER JOIN carreras c ON c.id_carrera = e.id_carrera
             WHERE focus.id_inscripcion = :id_inscripcion
             ORDER BY enrolled.estado, eu.apellido, eu.nombre"
        );
        $statement->execute(['id_inscripcion' => $enrollmentId]);

        return $statement->fetchAll();
    }

    public function create(int $studentId, int $offerId, ?PDO $transactionPdo = null): void
    {
        $ownsTransaction = $transactionPdo === null;
        $pdo = $transactionPdo ?? Database::connection();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $student = $pdo->prepare('SELECT id_estudiante FROM estudiantes WHERE id_estudiante = :id_estudiante FOR UPDATE');
            $student->execute(['id_estudiante' => $studentId]);
            if (!$student->fetchColumn()) {
                throw new RuntimeException('El perfil del estudiante no existe.');
            }

            $offer = $pdo->prepare(
                "SELECT o.cupo, o.estado, p.estado AS periodo_estado,
                        p.inscripcion_inicio, p.inscripcion_fin,
                        p.fecha_fin
                 FROM ofertas_tutoria o
                 INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
                 WHERE o.id_oferta = :id_oferta AND o.estado = 'publicada'
                   AND p.estado = 'publicado'
                   AND p.fecha_fin >= CURRENT_DATE
                   AND CURRENT_DATE BETWEEN p.inscripcion_inicio AND p.inscripcion_fin
                   AND EXISTS (
                       SELECT 1 FROM oferta_tutoria_fechas f
                       WHERE f.id_oferta = o.id_oferta AND f.estado = 'activa'
                   )
                  FOR UPDATE"
            );
            $offer->execute(['id_oferta' => $offerId]);
            $offerData = $offer->fetch();
            if (!$offerData) {
                throw new RuntimeException('La oferta publicada no está disponible para inscripción en este momento.');
            }

            $existing = $pdo->prepare(
                'SELECT id_inscripcion, estado FROM inscripciones_tutoria
                 WHERE id_estudiante = :id_estudiante AND id_oferta = :id_oferta LIMIT 1 FOR UPDATE'
            );
            $existing->execute(['id_estudiante' => $studentId, 'id_oferta' => $offerId]);
            $existingEnrollment = $existing->fetch();
            if ($existingEnrollment && $existingEnrollment['estado'] === 'inscrita') {
                throw new RuntimeException('Ya tienes una inscripcion activa en esta oferta.');
            }
            if ($existingEnrollment && $existingEnrollment['estado'] === 'finalizada') {
                throw new RuntimeException('La inscripcion anterior ya fue finalizada.');
            }

            $count = $pdo->prepare("SELECT COUNT(*) FROM inscripciones_tutoria WHERE id_oferta = :id_oferta AND estado = 'inscrita'");
            $count->execute(['id_oferta' => $offerId]);
            if ((int) $count->fetchColumn() >= (int) $offerData['cupo']) {
                throw new RuntimeException('La oferta ya no tiene cupos disponibles.');
            }

            if ($existingEnrollment) {
                $insert = $pdo->prepare(
                    "UPDATE inscripciones_tutoria
                     SET id_oferta_tutor = NULL, id_oferta_horario = NULL,
                         estado = 'inscrita', fecha_inscripcion = CURRENT_TIMESTAMP
                     WHERE id_inscripcion = :id_inscripcion"
                );
                $insert->execute(['id_inscripcion' => $existingEnrollment['id_inscripcion']]);
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO inscripciones_tutoria (id_oferta, id_estudiante)
                     VALUES (:id_oferta, :id_estudiante)'
                );
                $insert->execute([
                    'id_oferta' => $offerId,
                    'id_estudiante' => $studentId,
                ]);
            }

            $countAfter = $pdo->prepare("SELECT COUNT(*) FROM inscripciones_tutoria WHERE id_oferta = :id_oferta AND estado = 'inscrita'");
            $countAfter->execute(['id_oferta' => $offerId]);
            if ((int) $countAfter->fetchColumn() >= (int) $offerData['cupo']) {
                (new OfertaTutoria())->ensureNextGroupForFullOffer($pdo, $offerId);
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function cancel(int $id, int $studentId): ?string
    {
        $activeSessions = Database::connection()->prepare(
            "SELECT COUNT(*)
             FROM tutorias t
             INNER JOIN inscripciones_tutoria i ON i.id_inscripcion = t.id_inscripcion
             WHERE i.id_inscripcion = :id
               AND i.id_estudiante = :id_estudiante
               AND i.estado = 'inscrita'
               AND t.estado IN ('pendiente', 'confirmada')"
        );
        $activeSessions->execute(['id' => $id, 'id_estudiante' => $studentId]);
        if ((int) $activeSessions->fetchColumn() > 0) {
            return 'No puede cancelar la inscripcion mientras tenga sesiones pendientes o confirmadas.';
        }

        $statement = Database::connection()->prepare(
            "UPDATE inscripciones_tutoria SET estado = 'cancelada'
             WHERE id_inscripcion = :id AND id_estudiante = :id_estudiante AND estado = 'inscrita'"
        );
        $statement->execute(['id' => $id, 'id_estudiante' => $studentId]);

        return $statement->rowCount() > 0 ? null : 'La inscripcion no existe o ya fue cancelada.';
    }
}
