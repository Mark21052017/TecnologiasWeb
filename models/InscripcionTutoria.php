<?php

declare(strict_types=1);

final class InscripcionTutoria
{
    public function allForViewer(string $role, int $userId): array
    {
        $sql = <<<'SQL'
            SELECT i.id_inscripcion, i.id_oferta, i.id_oferta_tutor, i.id_oferta_horario,
                   i.estado, i.fecha_inscripcion,
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
            INNER JOIN oferta_tutores ot ON ot.id_oferta_tutor = i.id_oferta_tutor
            INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
            INNER JOIN usuarios tu ON tu.id_usuario = t.id_usuario
            INNER JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
            INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
            INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = i.id_oferta_horario
              INNER JOIN turnos b ON b.id_turno = o.id_turno
            LEFT JOIN aulas a ON a.id_aula = oh.id_aula
        SQL;
        $params = [];
        if ($role === 'estudiante') {
            $sql .= ' WHERE eu.id_usuario = :user_id';
            $params['user_id'] = $userId;
        } elseif ($role === 'tutor') {
            $sql .= ' WHERE tu.id_usuario = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' ORDER BY p.fecha_inicio DESC, m.nombre_materia, i.fecha_inscripcion DESC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function detailForAdmin(int $enrollmentId): ?array
    {
        $statement = Database::connection()->prepare(
            "SELECT focus.id_inscripcion, focus.estado AS inscripcion_estado,
                    o.id_oferta, o.nombre_grupo, o.cupo,
                    p.nombre_periodo, p.fecha_inicio, p.fecha_fin,
                    m.nombre_materia,
                    CONCAT(tu.nombre, ' ', tu.apellido) AS tutor,
                    oh.id_oferta_horario, oh.dia_semana,
                     b.nombre_turno, b.hora_inicio, b.hora_fin,
                    a.nombre_aula, a.ubicacion,
                    COUNT(CASE WHEN enrolled.estado = 'inscrita' THEN enrolled.id_inscripcion END) AS inscritos
             FROM inscripciones_tutoria focus
             INNER JOIN ofertas_tutoria o ON o.id_oferta = focus.id_oferta
             INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
             INNER JOIN materias m ON m.id_materia = o.id_materia
             INNER JOIN oferta_tutores ot ON ot.id_oferta_tutor = focus.id_oferta_tutor
             INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
             INNER JOIN usuarios tu ON tu.id_usuario = t.id_usuario
             INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = focus.id_oferta_horario
              INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
             LEFT JOIN inscripciones_tutoria enrolled
                ON enrolled.id_oferta = focus.id_oferta
               AND enrolled.id_oferta_tutor = focus.id_oferta_tutor
               AND enrolled.id_oferta_horario = focus.id_oferta_horario
             WHERE focus.id_inscripcion = :id_inscripcion
             GROUP BY focus.id_inscripcion, focus.estado, o.id_oferta, o.nombre_grupo, o.cupo,
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
               AND enrolled.id_oferta_tutor = focus.id_oferta_tutor
               AND enrolled.id_oferta_horario = focus.id_oferta_horario
             INNER JOIN estudiantes e ON e.id_estudiante = enrolled.id_estudiante
             INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
             INNER JOIN carreras c ON c.id_carrera = e.id_carrera
             WHERE focus.id_inscripcion = :id_inscripcion
             ORDER BY enrolled.estado, eu.apellido, eu.nombre"
        );
        $statement->execute(['id_inscripcion' => $enrollmentId]);

        return $statement->fetchAll();
    }

    public function create(int $studentId, int $offerId, int $offerTutorId, int $offerScheduleId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $offer = $pdo->prepare(
                "SELECT o.cupo, o.estado, p.estado AS periodo_estado,
                        p.inscripcion_inicio, p.inscripcion_fin,
                        ot.id_oferta_tutor, oth.id_oferta_horario
                 FROM ofertas_tutoria o
                 INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
                 INNER JOIN oferta_tutores ot ON ot.id_oferta_tutor = :id_oferta_tutor
                    AND ot.id_oferta = o.id_oferta AND ot.estado = 'confirmada'
                 INNER JOIN oferta_tutor_horarios oth ON oth.id_oferta_tutor = ot.id_oferta_tutor
                    AND oth.id_oferta_horario = :id_oferta_horario
                 WHERE o.id_oferta = :id_oferta AND o.estado = 'publicada'
                   AND p.estado = 'publicado'
                   AND CURRENT_DATE BETWEEN p.inscripcion_inicio AND p.inscripcion_fin
                 FOR UPDATE"
            );
            $offer->execute([
                'id_oferta' => $offerId,
                'id_oferta_tutor' => $offerTutorId,
                'id_oferta_horario' => $offerScheduleId,
            ]);
            $offerData = $offer->fetch();
            if (!$offerData) {
                throw new RuntimeException('La oferta, tutor u horario no esta disponible.');
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
                     SET id_oferta_tutor = :id_oferta_tutor, id_oferta_horario = :id_oferta_horario,
                         estado = 'inscrita', fecha_inscripcion = CURRENT_TIMESTAMP
                     WHERE id_inscripcion = :id_inscripcion"
                );
                $insert->execute([
                    'id_oferta_tutor' => $offerTutorId,
                    'id_oferta_horario' => $offerScheduleId,
                    'id_inscripcion' => $existingEnrollment['id_inscripcion'],
                ]);
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO inscripciones_tutoria (id_oferta, id_oferta_tutor, id_oferta_horario, id_estudiante)
                     VALUES (:id_oferta, :id_oferta_tutor, :id_oferta_horario, :id_estudiante)'
                );
                $insert->execute([
                    'id_oferta' => $offerId,
                    'id_oferta_tutor' => $offerTutorId,
                    'id_oferta_horario' => $offerScheduleId,
                    'id_estudiante' => $studentId,
                ]);
            }

            $countAfter = $pdo->prepare("SELECT COUNT(*) FROM inscripciones_tutoria WHERE id_oferta = :id_oferta AND estado = 'inscrita'");
            $countAfter->execute(['id_oferta' => $offerId]);
            if ((int) $countAfter->fetchColumn() >= (int) $offerData['cupo']) {
                (new OfertaTutoria())->ensureNextGroupForFullOffer($pdo, $offerId);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
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
