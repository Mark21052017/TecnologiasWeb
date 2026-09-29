<?php

declare(strict_types=1);

final class MgAsignacionesTutor
{
    public function capacityRule(): array
    {
        $parameter = (new MgConfiguracion())->parameter('tutor_max_estudiantes') ?? [];
        $limit = (new MgConfiguracion())->effectiveValue('tutor_max_estudiantes', 3);
        return [
            'limite' => is_int($limit) && $limit > 0 ? $limit : null,
            'estado_evidencia' => $parameter['estado_evidencia'] ?? 'pendiente',
            'fuente' => $parameter['fuente'] ?? null,
        ];
    }

    public function activeWorks(): array
    {
        return Database::connection()->query(
            'SELECT w.id_trabajo, w.codigo, w.tipo_trabajo, w.tema, w.id_modalidad,
                    c.codigo AS codigo_cohorte, c.nombre AS cohorte, m.nombre AS modalidad,
                    m.requiere_tutor, a.id_asignacion, a.id_tutor,
                    CONCAT(ut.nombre, " ", ut.apellido) AS tutor,
                    COUNT(DISTINCT CASE WHEN i.estado = "activa" AND ti.estado = "activo" THEN i.id_estudiante END) AS estudiantes_activos,
                    GROUP_CONCAT(DISTINCT CASE WHEN i.estado = "activa" AND ti.estado = "activo" THEN CONCAT(ue.nombre, " ", ue.apellido, " (", COALESCE(e.registro_universitario, "sin RU"), ")") END ORDER BY ue.apellido SEPARATOR ", ") AS integrantes,
                    COUNT(DISTINCT CASE WHEN sh.estado = "pendiente" THEN sh.id_seguimiento END) AS hitos_pendientes,
                    COUNT(DISTINCT CASE WHEN sh.estado = "pendiente" AND sh.fecha_limite<CURRENT_DATE THEN sh.id_seguimiento END) AS hitos_vencidos
             FROM mg_trabajos w
             INNER JOIN mg_cohortes c ON c.id_cohorte = w.id_cohorte
             INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad
             LEFT JOIN mg_asignaciones_tutor a ON a.id_trabajo = w.id_trabajo AND a.estado = "activa"
             LEFT JOIN tutores t ON t.id_tutor = a.id_tutor
             LEFT JOIN usuarios ut ON ut.id_usuario = t.id_usuario
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo = w.id_trabajo
             LEFT JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion
             LEFT JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
             LEFT JOIN usuarios ue ON ue.id_usuario = e.id_usuario
             LEFT JOIN mg_seguimiento_hitos sh ON sh.id_trabajo = w.id_trabajo
             WHERE w.estado = "activo"
             GROUP BY w.id_trabajo, w.codigo, w.tipo_trabajo, w.tema, w.id_modalidad,
                      c.codigo, c.nombre, m.nombre, m.requiere_tutor,
                      a.id_asignacion, a.id_tutor, ut.nombre, ut.apellido
             ORDER BY c.fecha_inicio DESC, w.codigo'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tutorsWithLoad(): array
    {
        return Database::connection()->query(
            'SELECT t.id_tutor, t.especialidad, u.nombre, u.apellido, u.correo,
                    COUNT(DISTINCT CASE WHEN a.estado = "activa" AND i.estado = "activa" AND ti.estado = "activo" THEN i.id_estudiante END) AS estudiantes_activos
             FROM tutores t
             INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = "activo"
             INNER JOIN roles r ON r.id_rol = u.id_rol AND r.nombre_rol = "tutor"
             LEFT JOIN mg_asignaciones_tutor a ON a.id_tutor = t.id_tutor AND a.estado = "activa"
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo = a.id_trabajo AND ti.estado = "activo"
             LEFT JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion
             GROUP BY t.id_tutor, t.especialidad, u.nombre, u.apellido, u.correo
             ORDER BY u.apellido, u.nombre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assign(int $workId, int $tutorId, int $adminId, string $note): void
    {
        $note = trim($note);
        if ($workId < 1 || $tutorId < 1) {
            throw new RuntimeException('Seleccione un trabajo y un tutor válidos.');
        }
        if (mb_strlen($note) > 1000) {
            throw new RuntimeException('La observación no puede superar 1.000 caracteres.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $workQuery = $pdo->prepare(
                'SELECT w.id_trabajo, w.codigo, w.estado, m.requiere_tutor
                 FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad
                 WHERE w.id_trabajo = :id FOR UPDATE'
            );
            $workQuery->execute(['id' => $workId]);
            $work = $workQuery->fetch(PDO::FETCH_ASSOC);
            if (!$work || $work['estado'] !== 'activo') {
                throw new RuntimeException('El trabajo seleccionado no existe o ya no está activo.');
            }
            if ((int) $work['requiere_tutor'] !== 1) {
                throw new RuntimeException('La modalidad de este trabajo no requiere asignación de tutor.');
            }
            $studentCount = $pdo->prepare(
                'SELECT COUNT(DISTINCT i.id_estudiante)
                 FROM mg_trabajo_integrantes ti
                 INNER JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion AND i.estado = "activa"
                 WHERE ti.id_trabajo = :trabajo AND ti.estado = "activo"'
            );
            $studentCount->execute(['trabajo' => $workId]);
            $workStudentCount = (int) $studentCount->fetchColumn();
            if ($workStudentCount < 1) {
                throw new RuntimeException('No se puede asignar tutor a un trabajo sin estudiantes activos.');
            }

            $currentQuery = $pdo->prepare('SELECT id_asignacion, id_tutor FROM mg_asignaciones_tutor WHERE id_trabajo = :id AND estado = "activa" FOR UPDATE');
            $currentQuery->execute(['id' => $workId]);
            $current = $currentQuery->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($current && (int) $current['id_tutor'] === $tutorId) {
                throw new RuntimeException('Ese tutor ya está asignado al trabajo.');
            }
            $configuration = new MgConfiguracion();
            if ($current && !$configuration->effectiveValue('permitir_cambio_tutor', true)) {
                throw new RuntimeException('La configuración actual no permite cambiar el tutor asignado.');
            }
            if ($current && $configuration->effectiveValue('motivo_cambio_tutor_obligatorio', true) && $note === '') {
                throw new RuntimeException('Indique el motivo obligatorio para cambiar de tutor.');
            }

            $tutorQuery = $pdo->prepare(
                'SELECT t.id_tutor, u.nombre, u.apellido
                 FROM tutores t INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
                 INNER JOIN roles r ON r.id_rol = u.id_rol AND r.nombre_rol = "tutor"
                 WHERE t.id_tutor = :id AND u.estado = "activo" FOR UPDATE'
            );
            $tutorQuery->execute(['id' => $tutorId]);
            $tutor = $tutorQuery->fetch(PDO::FETCH_ASSOC);
            if (!$tutor) {
                throw new RuntimeException('Seleccione un tutor con perfil y cuenta activa.');
            }

            $maxStudents = $configuration->effectiveValue('tutor_max_estudiantes', 3);
            $maxStudents = is_int($maxStudents) && $maxStudents > 0 ? $maxStudents : null;
            if ($maxStudents !== null) {
                $load = $pdo->prepare(
                    'SELECT COUNT(DISTINCT i.id_estudiante)
                     FROM mg_asignaciones_tutor a
                     INNER JOIN mg_trabajo_integrantes ti ON ti.id_trabajo = a.id_trabajo AND ti.estado = "activo"
                     INNER JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion AND i.estado = "activa"
                     WHERE a.id_tutor = :tutor AND a.estado = "activa"
                       AND NOT EXISTS (
                           SELECT 1 FROM mg_trabajo_integrantes selected_member
                           INNER JOIN mg_inscripciones selected_enrollment
                               ON selected_enrollment.id_inscripcion=selected_member.id_inscripcion AND selected_enrollment.estado="activa"
                           WHERE selected_member.id_trabajo=:selected_work
                             AND selected_member.id_estudiante=i.id_estudiante
                             AND selected_member.estado="activo"
                       )'
                );
                $load->execute(['tutor' => $tutorId, 'selected_work' => $workId]);
                $currentLoad = (int) $load->fetchColumn();
                if ($currentLoad + $workStudentCount > $maxStudents) {
                    throw new RuntimeException("La asignación dejaría al tutor con " . ($currentLoad + $workStudentCount) . " estudiantes, por encima del límite confirmado de {$maxStudents}.");
                }
            }

            if ($current) {
                $end = $pdo->prepare('UPDATE mg_asignaciones_tutor SET estado = "finalizada", fecha_fin = CURRENT_TIMESTAMP WHERE id_asignacion = :id AND estado = "activa"');
                $end->execute(['id' => (int) $current['id_asignacion']]);
            }
            $insert = $pdo->prepare(
                'INSERT INTO mg_asignaciones_tutor (id_trabajo, id_tutor, asignado_por, observacion)
                 VALUES (:trabajo, :tutor, :usuario, :observacion)'
            );
            $insert->execute([
                'trabajo' => $workId, 'tutor' => $tutorId, 'usuario' => $adminId,
                'observacion' => $note !== '' ? $note : null,
            ]);

            $requests = $pdo->prepare(
                'SELECT DISTINCT s.id_solicitud, e.id_usuario FROM mg_trabajo_integrantes ti
                 INNER JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion AND i.estado = "activa"
                 INNER JOIN mg_solicitudes s ON s.id_solicitud = i.id_solicitud
                 INNER JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
                 WHERE ti.id_trabajo = :trabajo AND ti.estado = "activo"'
            );
            $requests->execute(['trabajo' => $workId]);
            $audit = $pdo->prepare(
                'INSERT INTO mg_solicitud_historial
                    (id_solicitud, accion, estado_anterior, estado_nuevo, id_actor, comentario, visible_estudiante)
                 VALUES (:solicitud, :accion, "aprobada", "aprobada", :actor, :comentario, 1)'
            );
            $action = $current ? 'tutor_cambiado' : 'tutor_asignado';
            $comment = ($current ? 'Tutor cambiado' : 'Tutor asignado') . ' para ' . $work['codigo'] . ': ' . $tutor['nombre'] . ' ' . $tutor['apellido'];
            if ($note !== '') {
                $comment .= ' · ' . $note;
            }
            foreach ($requests->fetchAll(PDO::FETCH_ASSOC) as $request) {
                $audit->execute(['solicitud' => (int) $request['id_solicitud'], 'accion' => $action, 'actor' => $adminId, 'comentario' => $comment]);
                (new Notificacion())->add(
                    $pdo, (int) $request['id_usuario'], $action, $current ? 'Tutor de grado actualizado' : 'Tutor de grado asignado',
                    mb_substr($comment, 0, 1000), 'modalidades-grado/mi-solicitud.php?id=' . (int) $request['id_solicitud'],
                    'mg-tutor-assignment:' . (int) $request['id_solicitud'] . ':' . (int) $pdo->lastInsertId()
                );
            }
            $tutorUser = $pdo->prepare('SELECT id_usuario FROM tutores WHERE id_tutor = :id LIMIT 1');
            $tutorUser->execute(['id' => $tutorId]);
            (new Notificacion())->add(
                $pdo, (int) $tutorUser->fetchColumn(), 'mg_trabajo_asignado', 'Nuevo trabajo de grado asignado',
                'Administración te asignó como tutor al trabajo ' . $work['codigo'] . '.',
                'modalidades-grado/seguimiento.php', 'mg-tutor-work:' . $workId . ':' . (int) $pdo->lastInsertId()
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
                throw new RuntimeException('La asignación activa cambió mientras se procesaba. Actualice e intente nuevamente.');
            }
            throw $exception;
        }
    }

    public function tutorWorks(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.id_asignacion, a.estado AS estado_asignacion, a.fecha_inicio, a.fecha_fin, a.observacion,
                    w.codigo, w.tema, w.tipo_trabajo, m.nombre AS modalidad,
                    c.codigo AS codigo_cohorte, c.nombre AS cohorte,
                    COUNT(DISTINCT CASE WHEN i.estado = "activa" AND ti.estado = "activo" THEN i.id_estudiante END) AS estudiantes_activos,
                    GROUP_CONCAT(DISTINCT CASE WHEN i.estado = "activa" AND ti.estado = "activo" THEN CONCAT(ue.nombre, " ", ue.apellido, " (", COALESCE(e.registro_universitario, "sin RU"), ")") END ORDER BY ue.apellido SEPARATOR ", ") AS integrantes
             FROM tutores t
             INNER JOIN usuarios tu ON tu.id_usuario = t.id_usuario AND tu.estado = "activo"
             INNER JOIN roles tr ON tr.id_rol = tu.id_rol AND tr.nombre_rol = "tutor"
             INNER JOIN mg_asignaciones_tutor a ON a.id_tutor = t.id_tutor
             INNER JOIN mg_trabajos w ON w.id_trabajo = a.id_trabajo
             INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad
             INNER JOIN mg_cohortes c ON c.id_cohorte = w.id_cohorte
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo = w.id_trabajo
             LEFT JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion
             LEFT JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
             LEFT JOIN usuarios ue ON ue.id_usuario = e.id_usuario
             WHERE t.id_usuario = :usuario AND a.estado = "activa"
             GROUP BY a.id_asignacion, a.estado, a.fecha_inicio, a.fecha_fin, a.observacion,
                      w.codigo, w.tema, w.tipo_trabajo, m.nombre, c.codigo, c.nombre
             ORDER BY a.fecha_inicio DESC'
        );
        $statement->execute(['usuario' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
