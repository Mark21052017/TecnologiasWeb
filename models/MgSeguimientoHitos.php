<?php

declare(strict_types=1);

final class MgSeguimientoHitos
{
    public function generateForWork(PDO $pdo, int $workId): int
    {
        $statement = $pdo->prepare(
            'SELECT id_cohorte,id_modalidad FROM mg_trabajos WHERE id_trabajo=:id AND estado="activo"'
        );
        $statement->execute(['id' => $workId]);
        $work = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$work) {
            return 0;
        }
        $hitos = $pdo->prepare(
            'SELECT id_hito,fecha_limite FROM mg_calendario
             WHERE id_cohorte=:cohorte AND id_modalidad=:modalidad AND estado="activo"'
        );
        $hitos->execute(['cohorte' => (int) $work['id_cohorte'], 'modalidad' => (int) $work['id_modalidad']]);
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO mg_seguimiento_hitos (id_hito,id_trabajo,fecha_limite) VALUES (:hito,:trabajo,:limite)'
        );
        $count = 0;
        foreach ($hitos->fetchAll(PDO::FETCH_ASSOC) as $hito) {
            $insert->execute(['hito' => (int) $hito['id_hito'], 'trabajo' => $workId, 'limite' => $hito['fecha_limite']]);
            $count += $insert->rowCount();
        }
        return $count;
    }

    public function tasksForStudent(int $workId, int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT sh.id_seguimiento, sh.estado, sh.fecha_limite, sh.fecha_entrega,
                    sh.avance_real_pct, sh.observacion_estudiante, sh.observacion_tutor,
                    h.etapa,h.tipo,th.nombre AS tipo_nombre,h.nombre,h.orden,h.avance_esperado_pct,m.requiere_informes,
                    ri.id_informe,ri.estado AS estado_informe,ri.version_actual,
                    (sh.estado="pendiente" AND sh.fecha_limite IS NOT NULL AND sh.fecha_limite<CURRENT_DATE) AS vencido
             FROM mg_trabajo_integrantes ti
              INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado IN ("activa","finalizada")
             INNER JOIN mg_seguimiento_hitos sh ON sh.id_trabajo=ti.id_trabajo
             INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado="activo"
              INNER JOIN mg_modalidades m ON m.id_modalidad=h.id_modalidad
              LEFT JOIN mg_tipos_hito th ON th.codigo=h.tipo
              LEFT JOIN mg_informes_grado ri ON ri.id_seguimiento=sh.id_seguimiento
             WHERE ti.id_trabajo=:trabajo AND ti.id_estudiante=:estudiante AND ti.estado IN ("activo","finalizado")
             ORDER BY h.orden,h.fecha_limite,h.nombre'
        );
        $statement->execute(['trabajo' => $workId, 'estudiante' => $studentId]);
        $tasks = $statement->fetchAll(PDO::FETCH_ASSOC);
        $configuration = new MgConfiguracion();
        $maximumCorrections = (int)$configuration->effectiveValue('max_correcciones_informe', PHP_INT_MAX);
        $allowLateWork = (bool)$configuration->effectiveValue('permitir_hitos_fuera_plazo', true);
        $allowLateReports = (bool)$configuration->effectiveValue('permitir_entrega_informe_fuera_plazo', true);
        if (!$configuration->effectiveValue('marcar_hitos_vencidos_automaticamente', true)) {
            foreach ($tasks as &$task) {
                $task['vencido'] = 0;
            }
            unset($task);
        }
        foreach ($tasks as &$task) {
            $task['versiones'] = [];
            if ($task['id_informe'] !== null) {
                $versions = Database::connection()->prepare(
                    'SELECT id_version,numero_version,nombre_archivo,estado,observacion_tutor,subido_en
                     FROM mg_informe_versiones WHERE id_informe=:informe ORDER BY numero_version'
                );
                $versions->execute(['informe' => (int)$task['id_informe']]);
                $task['versiones'] = $versions->fetchAll(PDO::FETCH_ASSOC);
            }
            $task['correccion_permitida'] = $task['estado_informe'] === 'observado'
                && (int)$task['version_actual'] - 1 < $maximumCorrections;
            $late = $task['fecha_limite'] !== null && $task['fecha_limite'] < date('Y-m-d');
            $task['entrega_hito_permitida'] = !$late || $allowLateWork;
            $task['entrega_informe_permitida'] = !$late || $allowLateReports;
            if (!$configuration->effectiveValue('control_informes', true)) {
                $task['requiere_informes'] = 0;
            }
        }
        unset($task);
        return $tasks;
    }

    public function deliver(int $taskId, int $studentId, int $actorId, ?string $progress, string $comment): void
    {
        $comment = trim($comment);
        if ($comment === '' || mb_strlen($comment) > 5000) {
            throw new RuntimeException('Ingrese un comentario de entrega (máximo 5.000 caracteres).');
        }
        $progressValue = null;
        if ($progress !== null && trim($progress) !== '') {
            if (!is_numeric($progress) || (float) $progress < 0 || (float) $progress > 100) {
                throw new RuntimeException('El avance real debe estar entre 0 y 100.');
            }
            $progressValue = number_format((float) $progress, 2, '.', '');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT sh.id_seguimiento,sh.id_trabajo,sh.id_hito,sh.estado,sh.fecha_limite,h.tipo,h.avance_esperado_pct,m.requiere_informes,
                        i.id_solicitud,u.estado AS estado_usuario
                 FROM mg_seguimiento_hitos sh
             INNER JOIN mg_trabajo_integrantes ti ON ti.id_trabajo=sh.id_trabajo AND ti.id_estudiante=:estudiante AND ti.estado IN ("activo","finalizado")
             INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado IN ("activa","finalizada")
                 INNER JOIN estudiantes e ON e.id_estudiante=ti.id_estudiante AND e.id_usuario=:usuario
                 INNER JOIN usuarios u ON u.id_usuario=e.id_usuario AND u.estado="activo"
                 INNER JOIN mg_trabajos w ON w.id_trabajo=sh.id_trabajo AND w.estado="activo"
                 INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado="activo"
                 INNER JOIN mg_modalidades m ON m.id_modalidad=h.id_modalidad
                 WHERE sh.id_seguimiento=:tarea FOR UPDATE'
            );
            $statement->execute(['estudiante' => $studentId, 'usuario' => $actorId, 'tarea' => $taskId]);
            $task = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$task || $task['estado_usuario'] !== 'activo') {
                throw new RuntimeException('El hito no existe para un trabajo propio activo.');
            }
            $configuration = new MgConfiguracion();
            if ($task['tipo'] === 'informe' && (int) $task['requiere_informes'] === 1
                && $configuration->effectiveValue('control_informes', true)) {
                throw new RuntimeException('Este hito requiere subir un archivo PDF de informe.');
            }
            if ($task['estado'] !== 'pendiente') {
                throw new RuntimeException('El hito ya tiene una entrega o decisión registrada.');
            }
            $configuration = new MgConfiguracion();
            if ($task['fecha_limite'] !== null && $task['fecha_limite'] < date('Y-m-d')
                && !$configuration->effectiveValue('permitir_hitos_fuera_plazo', true)) {
                throw new RuntimeException('El plazo de este hito ya venció.');
            }
            if ($progressValue !== null && $task['avance_esperado_pct'] !== null
                && !$configuration->effectiveValue('permitir_avance_superior_esperado', true)
                && (float)$progressValue > (float)$task['avance_esperado_pct']) {
                throw new RuntimeException('El avance no puede superar el porcentaje esperado para este hito.');
            }
            $update = $pdo->prepare(
                'UPDATE mg_seguimiento_hitos SET estado="entregado",fecha_entrega=CURRENT_TIMESTAMP,
                    avance_real_pct=:avance,observacion_estudiante=:comentario WHERE id_seguimiento=:id'
            );
            $update->execute(['avance' => $progressValue, 'comentario' => $comment, 'id' => $taskId]);
            $audit = $pdo->prepare(
                'INSERT INTO mg_solicitud_historial
                    (id_solicitud,accion,estado_anterior,estado_nuevo,id_actor,comentario,visible_estudiante)
                 VALUES (:solicitud,"hito_entregado","aprobada","aprobada",:actor,:comentario,1)'
            );
            $audit->execute([
                'solicitud' => (int) $task['id_solicitud'], 'actor' => $actorId,
                'comentario' => 'Hito #' . $taskId . ' entregado' . ($progressValue !== null ? ' · Avance ' . $progressValue . '%' : '') . ' · ' . $comment,
            ]);
            $tutor = $pdo->prepare(
                'SELECT u.id_usuario FROM mg_asignaciones_tutor a
                 INNER JOIN tutores t ON t.id_tutor = a.id_tutor
                 INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
                 WHERE a.id_trabajo = :work AND a.estado = "activa" LIMIT 1'
            );
            $tutor->execute(['work' => (int) $task['id_trabajo']]);
            $tutorUserId = (int) $tutor->fetchColumn();
            if ($tutorUserId > 0) {
                (new Notificacion())->add(
                    $pdo, $tutorUserId, 'mg_hito_entregado', 'Nuevo hito entregado',
                    'Un estudiante entregó el hito #' . $taskId . ' para revisión.',
                    'modalidades-grado/seguimiento.php?trabajo=' . (int) $task['id_trabajo'],
                    'mg-hito-delivered:' . $taskId
                );
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
