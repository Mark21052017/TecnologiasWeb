<?php

declare(strict_types=1);

final class PostulacionTutoria
{
    public function create(int $studentId, int $actorUserId, int $offerId, string $reason): int
    {
        $reason = trim($reason);
        if (mb_strlen($reason) > 1000) {
            throw new RuntimeException('El motivo no puede superar 1.000 caracteres.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $student = $pdo->prepare(
                'SELECT e.id_estudiante,u.estado FROM estudiantes e
                 INNER JOIN usuarios u ON u.id_usuario=e.id_usuario
                 WHERE e.id_estudiante=:student AND e.id_usuario=:user FOR UPDATE'
            );
            $student->execute(['student' => $studentId, 'user' => $actorUserId]);
            $studentData = $student->fetch(PDO::FETCH_ASSOC);
            if (!$studentData || $studentData['estado'] !== 'activo') {
                throw new RuntimeException('Se requiere una cuenta de estudiante activa para postularse.');
            }

            $this->assertOfferOpen($pdo, $offerId, false);
            $enrollment = $pdo->prepare(
                'SELECT estado FROM inscripciones_tutoria
                 WHERE id_estudiante=:student AND id_oferta=:offer FOR UPDATE'
            );
            $enrollment->execute(['student' => $studentId, 'offer' => $offerId]);
            $enrollmentState = $enrollment->fetchColumn();
            if ($enrollmentState === 'inscrita') {
                throw new RuntimeException('Ya tienes una inscripción activa en esta oferta.');
            }
            if ($enrollmentState === 'finalizada') {
                throw new RuntimeException('La inscripción anterior ya fue finalizada; no se puede volver a postular.');
            }
            $previouslyApproved = $pdo->prepare(
                'SELECT id_postulacion FROM postulaciones_tutoria
                 WHERE id_estudiante=:student AND id_oferta=:offer AND estado="aprobada" LIMIT 1'
            );
            $previouslyApproved->execute(['student' => $studentId, 'offer' => $offerId]);
            if ($previouslyApproved->fetchColumn()) {
                throw new RuntimeException('Ya se aprobó una postulación anterior para esta oferta. Contacta a Administración si necesitas reactivar la inscripción.');
            }

            $pending = $pdo->prepare(
                'SELECT id_postulacion FROM postulaciones_tutoria
                 WHERE id_estudiante=:student AND id_oferta=:offer AND estado="pendiente" LIMIT 1 FOR UPDATE'
            );
            $pending->execute(['student' => $studentId, 'offer' => $offerId]);
            if ($pending->fetchColumn()) {
                throw new RuntimeException('Ya tienes una postulación pendiente para esta oferta.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO postulaciones_tutoria (id_estudiante,id_oferta,motivo)
                 VALUES (:student,:offer,:reason)'
            );
            $insert->execute(['student' => $studentId, 'offer' => $offerId, 'reason' => $reason !== '' ? $reason : null]);
            $applicationId = (int) $pdo->lastInsertId();

            (new Notificacion())->notifyAdministrators(
                $pdo,
                'postulacion_tutoria_pendiente',
                'Nueva postulación a tutoría',
                'Hay una postulación pendiente de revisión para una oferta publicada.',
                'postulaciones-tutoria/',
                'tutoring-application:' . $applicationId . ':pending'
            );
            $pdo->commit();
            return $applicationId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
                throw new RuntimeException('Ya existe una postulación pendiente para esta oferta.');
            }
            throw $exception;
        }
    }

    private function assertOfferOpen(PDO $pdo, int $offerId, bool $checkCapacity = true): array
    {
        $statement = $pdo->prepare(
            "SELECT o.cupo,o.estado,p.estado AS estado_periodo,p.inscripcion_inicio,p.inscripcion_fin,p.fecha_fin,
                    (SELECT COUNT(*) FROM inscripciones_tutoria i WHERE i.id_oferta=o.id_oferta AND i.estado='inscrita') AS inscritos
             FROM ofertas_tutoria o INNER JOIN periodos_tutoria p ON p.id_periodo=o.id_periodo
             WHERE o.id_oferta=:offer AND o.estado='publicada' AND p.estado='publicado'
               AND p.fecha_fin>=CURRENT_DATE
               AND CURRENT_DATE BETWEEN p.inscripcion_inicio AND p.inscripcion_fin
               AND EXISTS (SELECT 1 FROM oferta_tutoria_fechas f WHERE f.id_oferta=o.id_oferta AND f.estado='activa')
             FOR UPDATE"
        );
        $statement->execute(['offer' => $offerId]);
        $offer = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$offer) {
            throw new RuntimeException('La oferta no está publicada o el plazo de postulación cerró.');
        }
        if ($checkCapacity && (int) $offer['inscritos'] >= (int) $offer['cupo']) {
            throw new RuntimeException('La oferta ya no tiene cupos; las postulaciones pendientes no reservan cupo.');
        }
        return $offer;
    }

    public function ownApplications(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT p.id_postulacion,p.estado,p.motivo,p.observaciones_revision,p.fecha_postulacion,p.fecha_revision,
                    o.id_oferta,o.nombre_grupo,o.cupo,o.estado AS estado_oferta,
                    m.nombre_materia,tt.nombre AS tipo_tutoria,t.nombre_turno AS turno,
                    per.nombre_periodo,per.fecha_inicio,per.inscripcion_fin,
                    i.id_inscripcion,i.estado AS estado_inscripcion,
                    CONCAT(rv.nombre," ",rv.apellido) AS revisor,
                    (SELECT COUNT(*) FROM inscripciones_tutoria active_i WHERE active_i.id_oferta=o.id_oferta AND active_i.estado="inscrita") AS inscritos
             FROM postulaciones_tutoria p
             INNER JOIN ofertas_tutoria o ON o.id_oferta=p.id_oferta
             INNER JOIN materias m ON m.id_materia=o.id_materia
             INNER JOIN tipos_tutoria tt ON tt.id_tipo_tutoria=o.id_tipo_tutoria
             INNER JOIN turnos t ON t.id_turno=o.id_turno
             INNER JOIN periodos_tutoria per ON per.id_periodo=o.id_periodo
             LEFT JOIN inscripciones_tutoria i ON i.id_inscripcion=p.id_inscripcion
             LEFT JOIN usuarios rv ON rv.id_usuario=p.revisado_por
             WHERE p.id_estudiante=:student ORDER BY p.fecha_postulacion DESC,p.id_postulacion DESC'
        );
        $statement->execute(['student' => $studentId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allPending(): array
    {
        return Database::connection()->query(
            'SELECT p.id_postulacion,p.id_estudiante,p.id_oferta,p.motivo,p.fecha_postulacion,
                    e.registro_universitario,CONCAT(u.nombre," ",u.apellido) AS estudiante,u.correo,c.nombre_carrera,
                    o.nombre_grupo,o.cupo,o.estado AS estado_oferta,m.nombre_materia,tt.nombre AS tipo_tutoria,
                    t.nombre_turno AS turno,per.nombre_periodo,per.inscripcion_inicio,per.inscripcion_fin,per.fecha_fin,
                    (SELECT COUNT(*) FROM inscripciones_tutoria i WHERE i.id_oferta=o.id_oferta AND i.estado="inscrita") AS inscritos
             FROM postulaciones_tutoria p
             INNER JOIN estudiantes e ON e.id_estudiante=p.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario=e.id_usuario
             INNER JOIN carreras c ON c.id_carrera=e.id_carrera
             INNER JOIN ofertas_tutoria o ON o.id_oferta=p.id_oferta
             INNER JOIN materias m ON m.id_materia=o.id_materia
             INNER JOIN tipos_tutoria tt ON tt.id_tipo_tutoria=o.id_tipo_tutoria
             INNER JOIN turnos t ON t.id_turno=o.id_turno
             INNER JOIN periodos_tutoria per ON per.id_periodo=o.id_periodo
             WHERE p.estado="pendiente" ORDER BY p.fecha_postulacion,p.id_postulacion LIMIT 500'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelOwn(int $applicationId, int $studentId, int $userId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $student = $pdo->prepare('SELECT 1 FROM estudiantes e INNER JOIN usuarios u ON u.id_usuario=e.id_usuario AND u.estado="activo" WHERE e.id_estudiante=:student AND e.id_usuario=:user FOR UPDATE');
            $student->execute(['student' => $studentId, 'user' => $userId]);
            if (!$student->fetchColumn()) {
                throw new RuntimeException('Se requiere un perfil de estudiante activo.');
            }
            $postulation = $pdo->prepare('SELECT id_oferta,estado FROM postulaciones_tutoria WHERE id_postulacion=:id AND id_estudiante=:student FOR UPDATE');
            $postulation->execute(['id' => $applicationId, 'student' => $studentId]);
            $row = $postulation->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['estado'] !== 'pendiente') {
                throw new RuntimeException('Solo puedes cancelar una postulación pendiente propia.');
            }
            $pdo->prepare('UPDATE postulaciones_tutoria SET estado="cancelada" WHERE id_postulacion=:id')->execute(['id' => $applicationId]);
            (new Notificacion())->notifyAdministrators(
                $pdo, 'postulacion_tutoria_cancelada', 'Postulación a tutoría cancelada',
                'Un estudiante canceló su postulación pendiente a una oferta.',
                'postulaciones-tutoria/', 'tutoring-application:' . $applicationId . ':cancelled'
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public function review(int $applicationId, int $adminId, string $decision, string $note): void
    {
        $note = trim($note);
        if (!in_array($decision, ['aprobada', 'rechazada'], true)) {
            throw new RuntimeException('Seleccione aprobar o rechazar la postulación.');
        }
        if ($decision === 'rechazada' && $note === '') {
            throw new RuntimeException('Indique al estudiante el motivo del rechazo.');
        }
        if (mb_strlen($note) > 1000) {
            throw new RuntimeException('La observación no puede superar 1.000 caracteres.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $lookup = $pdo->prepare('SELECT id_estudiante FROM postulaciones_tutoria WHERE id_postulacion=:id LIMIT 1');
            $lookup->execute(['id' => $applicationId]);
            $studentId = (int) $lookup->fetchColumn();
            if ($studentId < 1) {
                throw new RuntimeException('La postulación no existe o ya fue revisada.');
            }
            $student = $pdo->prepare(
                'SELECT e.id_usuario,u.estado FROM estudiantes e INNER JOIN usuarios u ON u.id_usuario=e.id_usuario
                 WHERE e.id_estudiante=:id FOR UPDATE'
            );
            $student->execute(['id' => $studentId]);
            $studentData = $student->fetch(PDO::FETCH_ASSOC);
            $statement = $pdo->prepare('SELECT * FROM postulaciones_tutoria WHERE id_postulacion=:id FOR UPDATE');
            $statement->execute(['id' => $applicationId]);
            $application = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$application || $application['estado'] !== 'pendiente') {
                throw new RuntimeException('La postulación no existe o ya fue revisada.');
            }
            if ((int) $application['id_estudiante'] !== $studentId || !$studentData || $studentData['estado'] !== 'activo') {
                throw new RuntimeException('El perfil del estudiante no está activo.');
            }

            $enrollmentId = null;
            if ($decision === 'aprobada') {
                // Revalida publicación, periodo y cupo al aprobar. El cupo no se reserva al postular.
                $this->assertOfferOpen($pdo, (int) $application['id_oferta']);
                (new InscripcionTutoria())->create((int) $application['id_estudiante'], (int) $application['id_oferta'], $pdo);
                $find = $pdo->prepare('SELECT id_inscripcion FROM inscripciones_tutoria WHERE id_estudiante=:student AND id_oferta=:offer AND estado="inscrita" LIMIT 1');
                $find->execute(['student' => (int) $application['id_estudiante'], 'offer' => (int) $application['id_oferta']]);
                $enrollmentId = (int) $find->fetchColumn();
            }

            $update = $pdo->prepare(
                'UPDATE postulaciones_tutoria SET estado=:estado,observaciones_revision=:nota,
                    id_inscripcion=:inscripcion,revisado_por=:revisor,fecha_revision=CURRENT_TIMESTAMP
                 WHERE id_postulacion=:id AND estado="pendiente"'
            );
            $update->execute([
                'estado' => $decision, 'nota' => $note !== '' ? $note : null,
                'inscripcion' => $enrollmentId, 'revisor' => $adminId, 'id' => $applicationId,
            ]);

            (new Notificacion())->add(
                $pdo, (int) $studentData['id_usuario'], 'postulacion_tutoria_' . $decision,
                $decision === 'aprobada' ? 'Postulación aprobada' : 'Postulación rechazada',
                $decision === 'aprobada'
                    ? 'Tu postulación fue aprobada y ya estás inscrito. Administración asignará un tutor después.'
                    : ($note !== '' ? $note : 'Tu postulación fue rechazada.'),
                'postulaciones-tutoria/',
                'tutoring-application:' . $applicationId . ':' . $decision
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
