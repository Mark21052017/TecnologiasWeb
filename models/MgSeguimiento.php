<?php

declare(strict_types=1);

final class MgSeguimiento
{
    private const RESULTS = ['pendiente', 'en_curso', 'observado', 'aprobado', 'reprobado'];

    public function availableWorks(?int $tutorUserId = null): array
    {
        if ($tutorUserId !== null) {
            $statement = Database::connection()->prepare(
                'SELECT DISTINCT w.id_trabajo,w.codigo,w.tema,m.nombre AS modalidad,c.codigo AS codigo_cohorte
                 FROM mg_asignaciones_tutor a
                 INNER JOIN tutores t ON t.id_tutor=a.id_tutor
                 INNER JOIN mg_trabajos w ON w.id_trabajo=a.id_trabajo AND w.estado="activo"
                 INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad
                 INNER JOIN mg_cohortes c ON c.id_cohorte=w.id_cohorte
                 WHERE t.id_usuario=:usuario AND a.estado="activa"
                 ORDER BY c.codigo,w.codigo'
            );
            $statement->execute(['usuario' => $tutorUserId]);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }
        return Database::connection()->query(
            'SELECT w.id_trabajo,w.codigo,w.tema,m.nombre AS modalidad,c.codigo AS codigo_cohorte
             FROM mg_trabajos w INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad
             INNER JOIN mg_cohortes c ON c.id_cohorte=w.id_cohorte
             WHERE w.estado="activo" ORDER BY c.codigo,w.codigo'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function workDetail(int $workId, int $userId, string $role): ?array
    {
        $this->assertWorkAccess($workId, $userId, $role);
        $workQuery = Database::connection()->prepare(
            'SELECT w.*,m.nombre AS modalidad,m.requiere_asistencia,m.asistencia_minima_pct,
                    c.codigo AS codigo_cohorte,c.nombre AS cohorte,
                    COUNT(DISTINCT CASE WHEN i.estado="activa" AND ti.estado="activo" THEN i.id_estudiante END) AS estudiantes_activos
             FROM mg_trabajos w
             INNER JOIN mg_modalidades m ON m.id_modalidad=w.id_modalidad
             INNER JOIN mg_cohortes c ON c.id_cohorte=w.id_cohorte
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo=w.id_trabajo
             LEFT JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion
             WHERE w.id_trabajo=:id
             GROUP BY w.id_trabajo,m.nombre,m.requiere_asistencia,m.asistencia_minima_pct,c.codigo,c.nombre LIMIT 1'
        );
        $workQuery->execute(['id' => $workId]);
        $work = $workQuery->fetch(PDO::FETCH_ASSOC);
        if (!$work) {
            return null;
        }
        $work['hitos'] = $this->workMilestones($workId);
        $work['sesiones'] = $this->sessions($workId);
        $work['resultados'] = $this->stageResults($workId);
        $work['asistencia'] = $this->attendanceSummary($workId);
        return $work;
    }

    private function workMilestones(int $workId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT sh.id_seguimiento,sh.id_hito,sh.estado,sh.fecha_limite,sh.fecha_entrega,sh.avance_real_pct,
                    sh.observacion_estudiante,sh.observacion_tutor,h.etapa,h.tipo,h.nombre,h.orden,m.requiere_informes,
                    ri.id_informe,ri.estado AS estado_informe,ri.version_actual,
                    iv.id_version AS id_version_actual,iv.nombre_archivo,iv.estado AS estado_version,
                    iv.comentario_estudiante,iv.observacion_tutor AS revision_actual,
                    (sh.estado="pendiente" AND sh.fecha_limite IS NOT NULL AND sh.fecha_limite<CURRENT_DATE) AS vencido
             FROM mg_seguimiento_hitos sh
             INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito
             INNER JOIN mg_modalidades m ON m.id_modalidad=h.id_modalidad
             LEFT JOIN mg_informes_grado ri ON ri.id_seguimiento=sh.id_seguimiento
             LEFT JOIN mg_informe_versiones iv ON iv.id_informe=ri.id_informe AND iv.numero_version=ri.version_actual
             WHERE sh.id_trabajo=:trabajo AND h.estado="activo"
             ORDER BY h.orden,h.fecha_limite,h.nombre'
        );
        $statement->execute(['trabajo' => $workId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $configuration = new MgConfiguracion();
        foreach ($rows as &$row) {
            $row['versiones'] = $row['id_informe'] ? $this->reportVersions((int) $row['id_informe']) : [];
            if (!$configuration->effectiveValue('marcar_hitos_vencidos_automaticamente', true)) {
                $row['vencido'] = 0;
            }
            if (!$configuration->effectiveValue('control_informes', true)) {
                $row['requiere_informes'] = 0;
            }
        }
        unset($row);
        return $rows;
    }

    private function reportVersions(int $reportId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT v.id_version,v.numero_version,v.nombre_archivo,v.estado,v.comentario_estudiante,
                    v.observacion_tutor,v.subido_en,v.revisado_en,
                    CONCAT(us.nombre," ",us.apellido) AS subido_por,
                    CONCAT(ur.nombre," ",ur.apellido) AS revisado_por
             FROM mg_informe_versiones v
             INNER JOIN usuarios us ON us.id_usuario=v.subido_por
             LEFT JOIN usuarios ur ON ur.id_usuario=v.revisado_por
             WHERE v.id_informe=:id ORDER BY v.numero_version DESC'
        );
        $statement->execute(['id' => $reportId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function uploadReport(int $taskId, int $studentId, int $actorId, array $file, string $comment, ?string $progress): void
    {
        $comment = trim($comment);
        if (mb_strlen($comment) > 5000) {
            throw new RuntimeException('El comentario no puede superar 5.000 caracteres.');
        }
        $progressValue = null;
        if ($progress !== null && trim($progress) !== '') {
            if (!is_numeric($progress) || (float) $progress < 0 || (float) $progress > 100) {
                throw new RuntimeException('El avance real debe estar entre 0 y 100.');
            }
            $progressValue = number_format((float) $progress, 2, '.', '');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new RuntimeException('Seleccione un archivo PDF válido.');
        }
        $temporaryPath = (string) $file['tmp_name'];
        $size = (int) ($file['size'] ?? 0);
        $configuration = new MgConfiguracion();
        $maximumBytes = max(1, (int)$configuration->effectiveValue('tamano_maximo_informe_mb',5)) * 1_000_000;
        if ($size < 1 || $size > $maximumBytes) {
            throw new RuntimeException('El informe PDF supera el tamaño máximo de ' . number_format($maximumBytes / 1_000_000, 0) . ' MB.');
        }
        if (strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'pdf'
            || (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) !== 'application/pdf') {
            throw new RuntimeException('Los informes de esta fase deben ser archivos PDF.');
        }
        $handle = fopen($temporaryPath, 'rb');
        $signature = $handle ? fread($handle, 5) : false;
        if ($handle) {
            fclose($handle);
        }
        if ($signature !== '%PDF-') {
            throw new RuntimeException('El archivo seleccionado no parece ser un PDF válido.');
        }
        $hash = hash_file('sha256', $temporaryPath);
        if ($hash === false) {
            throw new RuntimeException('No se pudo verificar el archivo recibido.');
        }
        $safeOriginalName = basename((string) ($file['name'] ?? 'informe.pdf'));
        $relativePath = 'storage/mg-reports/' . $hash . '-' . bin2hex(random_bytes(5)) . '.pdf';
        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento privado de informes.');
        }

        $pdo = Database::connection();
        $stored = false;
        $pdo->beginTransaction();
        try {
            $task = $this->lockStudentTask($pdo, $taskId, $studentId, $actorId);
            if (!$configuration->effectiveValue('control_informes', true)
                || $task['tipo'] !== 'informe' || (int) $task['requiere_informes'] !== 1) {
                throw new RuntimeException('Este hito no está configurado como entrega de informe.');
            }
            if ($task['fecha_limite'] !== null && $task['fecha_limite'] < date('Y-m-d')
                && !$configuration->effectiveValue('permitir_entrega_informe_fuera_plazo', true)) {
                throw new RuntimeException('El plazo de entrega del informe ya venció.');
            }
            if ($progressValue !== null && $task['avance_esperado_pct'] !== null
                && !$configuration->effectiveValue('permitir_avance_superior_esperado', true)
                && (float)$progressValue > (float)$task['avance_esperado_pct']) {
                throw new RuntimeException('El avance no puede superar el porcentaje esperado para este hito.');
            }
            $reportQuery = $pdo->prepare('SELECT id_informe,estado,version_actual FROM mg_informes_grado WHERE id_seguimiento=:id FOR UPDATE');
            $reportQuery->execute(['id' => $taskId]);
            $report = $reportQuery->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($report && $report['estado'] !== 'observado') {
                throw new RuntimeException('Solo se puede entregar un informe pendiente o una corrección solicitada.');
            }
            if ($report) {
                $maxCorrections = (int)$configuration->effectiveValue('max_correcciones_informe', PHP_INT_MAX);
                if ((int)$report['version_actual'] - 1 >= $maxCorrections) {
                    throw new RuntimeException('El informe alcanzó el máximo de correcciones permitidas.');
                }
            }
            if ($task['estado'] !== 'pendiente' && (!$report || $report['estado'] !== 'observado')) {
                throw new RuntimeException('Este hito ya tiene una entrega registrada.');
            }
            if (!move_uploaded_file($temporaryPath, $absolutePath)) {
                throw new RuntimeException('No se pudo guardar el informe.');
            }
            $stored = true;
            if (!$report) {
                $pdo->prepare('INSERT INTO mg_informes_grado (id_seguimiento) VALUES (:id)')->execute(['id' => $taskId]);
                $reportId = (int) $pdo->lastInsertId();
                $version = 1;
                $state = 'entregado';
            } else {
                $reportId = (int) $report['id_informe'];
                $version = (int) $report['version_actual'] + 1;
                $state = 'corregido';
            }
            $saveVersion = $pdo->prepare(
                'INSERT INTO mg_informe_versiones
                    (id_informe,numero_version,nombre_archivo,ruta_archivo,hash_archivo,tamano_bytes,comentario_estudiante,estado,subido_por)
                 VALUES (:informe,:version,:nombre,:ruta,:hash,:bytes,:comentario,:estado,:usuario)'
            );
            $saveVersion->execute([
                'informe' => $reportId, 'version' => $version, 'nombre' => mb_substr($safeOriginalName, 0, 255),
                'ruta' => $relativePath, 'hash' => $hash, 'bytes' => $size,
                'comentario' => $comment !== '' ? $comment : null, 'estado' => $state, 'usuario' => $actorId,
            ]);
            $pdo->prepare('UPDATE mg_informes_grado SET estado=:estado,version_actual=:version WHERE id_informe=:id')
                ->execute(['estado' => $state, 'version' => $version, 'id' => $reportId]);
            $pdo->prepare(
                'UPDATE mg_seguimiento_hitos SET estado=:estado,fecha_entrega=CURRENT_TIMESTAMP,
                    avance_real_pct=:avance,observacion_estudiante=:comentario WHERE id_seguimiento=:id'
            )->execute(['estado' => $state, 'avance' => $progressValue,
                'comentario' => $comment !== '' ? $comment : null, 'id' => $taskId]);
            $this->auditRequest($pdo, (int) $task['id_solicitud'], $actorId, $version === 1 ? 'informe_entregado' : 'informe_corregido', "Versión {$version} cargada.");
            $tutor = $pdo->prepare(
                'SELECT u.id_usuario FROM mg_asignaciones_tutor a
                 INNER JOIN tutores t ON t.id_tutor=a.id_tutor
                 INNER JOIN usuarios u ON u.id_usuario=t.id_usuario
                 WHERE a.id_trabajo=:work AND a.estado="activa" LIMIT 1'
            );
            $tutor->execute(['work' => (int) $task['id_trabajo']]);
            $tutorUserId = (int) $tutor->fetchColumn();
            if ($tutorUserId > 0) {
                (new Notificacion())->add(
                    $pdo, $tutorUserId, 'mg_informe_entregado', 'Nuevo informe de grado',
                    'Un estudiante cargó la versión ' . $version . ' de un informe para revisión.',
                    'modalidades-grado/seguimiento.php?trabajo=' . (int) $task['id_trabajo'],
                    'mg-report-version:' . $reportId . ':' . $version
                );
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($stored && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            throw $exception;
        }
    }

    public function reviewReport(int $versionId, int $actorId, string $role, string $action, string $comment): void
    {
        if (!in_array($action, ['iniciar_revision', 'observar', 'aprobar'], true)) {
            throw new RuntimeException('Seleccione una acción de revisión válida.');
        }
        $comment = trim($comment);
        if ($action === 'observar' && $comment === '') {
            throw new RuntimeException('Indique qué debe corregirse en el informe.');
        }
        if (mb_strlen($comment) > 5000) {
            throw new RuntimeException('La observación no puede superar 5.000 caracteres.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT v.*,r.id_seguimiento,r.id_informe,r.version_actual,r.estado AS estado_informe,
                        sh.id_trabajo,sh.estado AS estado_hito,m.requiere_informes
                 FROM mg_informe_versiones v
                 INNER JOIN mg_informes_grado r ON r.id_informe=v.id_informe
                 INNER JOIN mg_seguimiento_hitos sh ON sh.id_seguimiento=r.id_seguimiento
                 INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito
                 INNER JOIN mg_modalidades m ON m.id_modalidad=h.id_modalidad
                 WHERE v.id_version=:id FOR UPDATE'
            );
            $statement->execute(['id' => $versionId]);
            $version = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$version || (int) $version['version_actual'] !== (int) $version['numero_version']) {
                throw new RuntimeException('Solo se puede revisar la versión vigente del informe.');
            }
            if ((int) $version['requiere_informes'] !== 1) {
                throw new RuntimeException('Esta modalidad no requiere informes versionados para este proceso.');
            }
            if (!(new MgConfiguracion())->effectiveValue('control_informes', true)) {
                throw new RuntimeException('El control general de informes está desactivado.');
            }
            $this->assertCanManageWork((int) $version['id_trabajo'], $actorId, $role, $pdo);
            if ($action === 'iniciar_revision') {
                if (!in_array($version['estado'], ['entregado', 'corregido'], true)) {
                    throw new RuntimeException('El informe no está listo para revisión.');
                }
                $newState = 'en_revision';
            } else {
                if ($version['estado'] !== 'en_revision') {
                    throw new RuntimeException('Inicie la revisión antes de observar o aprobar el informe.');
                }
                $newState = $action === 'observar' ? 'observado' : 'aprobado';
            }
            $pdo->prepare(
                'UPDATE mg_informe_versiones SET estado=:estado,observacion_tutor=:observacion,
                    revisado_por=:usuario,revisado_en=CURRENT_TIMESTAMP WHERE id_version=:id'
            )->execute(['estado' => $newState, 'observacion' => $comment !== '' ? $comment : null, 'usuario' => $actorId, 'id' => $versionId]);
            $pdo->prepare('UPDATE mg_informes_grado SET estado=:estado WHERE id_informe=:id')->execute(['estado' => $newState, 'id' => (int) $version['id_informe']]);
            $pdo->prepare('UPDATE mg_seguimiento_hitos SET estado=:estado,observacion_tutor=:comentario,revisado_por=:usuario,revisado_en=CURRENT_TIMESTAMP WHERE id_seguimiento=:id')
                ->execute(['estado' => $newState, 'comentario' => $comment !== '' ? $comment : null, 'usuario' => $actorId, 'id' => (int) $version['id_seguimiento']]);
            $auditAction = $action === 'iniciar_revision' ? 'informe_revision_iniciada' : ($action === 'observar' ? 'informe_observado' : 'informe_aprobado');
            $this->auditAllRequestsForWork($pdo, (int) $version['id_trabajo'], $actorId, $auditAction, $comment);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function reportDownload(int $versionId, int $userId, string $role): array
    {
        $statement = Database::connection()->prepare(
            'SELECT v.id_version,v.nombre_archivo,v.ruta_archivo,v.tamano_bytes,
                    sh.id_trabajo,ti.id_estudiante,a.id_tutor
             FROM mg_informe_versiones v
             INNER JOIN mg_informes_grado r ON r.id_informe=v.id_informe
             INNER JOIN mg_seguimiento_hitos sh ON sh.id_seguimiento=r.id_seguimiento
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo=sh.id_trabajo AND ti.estado IN ("activo","finalizado")
             LEFT JOIN mg_asignaciones_tutor a ON a.id_trabajo=sh.id_trabajo AND a.estado="activa"
             WHERE v.id_version=:id'
        );
        $statement->execute(['id' => $versionId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            throw new RuntimeException('No se encontró el archivo del informe.');
        }
        $file = $rows[0];
        $allowed = $role === 'administrador';
        if ($role === 'estudiante') {
            foreach ($rows as $row) {
                if ((int) $row['id_estudiante'] > 0) {
                    $owner = Database::connection()->prepare(
                        'SELECT 1 FROM mg_trabajo_integrantes ti
                         INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado IN ("activa","finalizada")
                         INNER JOIN estudiantes e ON e.id_estudiante=i.id_estudiante
                         WHERE ti.id_trabajo=:trabajo AND e.id_usuario=:usuario AND ti.estado IN ("activo","finalizado") AND i.estado IN ("activa","finalizada") LIMIT 1'
                    );
                    $owner->execute(['trabajo' => (int) $row['id_trabajo'], 'usuario' => $userId]);
                    if ($owner->fetchColumn()) { $allowed = true; break; }
                }
            }
        } elseif ($role === 'tutor') {
            $tutor = Database::connection()->prepare(
                'SELECT 1 FROM mg_asignaciones_tutor a INNER JOIN tutores t ON t.id_tutor=a.id_tutor
                 WHERE a.id_trabajo=:trabajo AND a.estado="activa" AND t.id_usuario=:usuario LIMIT 1'
            );
            $tutor->execute(['trabajo' => (int) $file['id_trabajo'], 'usuario' => $userId]);
            $allowed = (bool) $tutor->fetchColumn();
        }
        if (!$allowed) {
            throw new RuntimeException('No tiene acceso a este archivo.');
        }
        $relativePath = (string) $file['ruta_archivo'];
        $prefix = 'storage/mg-reports/';
        if (!str_starts_with($relativePath, $prefix) || basename($relativePath) !== substr($relativePath, strlen($prefix))) {
            throw new RuntimeException('La ruta del archivo no es válida.');
        }
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('El archivo asociado no está disponible.');
        }
        return ['path' => $path, 'name' => basename((string) $file['nombre_archivo']), 'size' => filesize($path)];
    }

    public function createSession(int $workId, int $actorId, string $role, array $input): int
    {
        if (!(new MgConfiguracion())->effectiveValue('control_tutorias_mg', true)) {
            throw new RuntimeException('La programación del control de tutorías MG está desactivada por configuración.');
        }
        $type = (string) ($input['tipo'] ?? 'seguimiento');
        $title = trim((string) ($input['titulo'] ?? ''));
        $description = trim((string) ($input['descripcion'] ?? ''));
        $start = trim((string) ($input['inicio'] ?? ''));
        $end = trim((string) ($input['fin'] ?? ''));
        $mode = (string) ($input['modalidad'] ?? 'presencial');
        $location = trim((string) ($input['ubicacion'] ?? ''));
        if (!in_array($type, ['tutoria', 'taller', 'seguimiento'], true)
            || !in_array($mode, ['presencial', 'virtual'], true)
            || $title === '' || mb_strlen($title) > 180
            || mb_strlen($description) > 5000 || mb_strlen($location) > 255
            || !$this->validDateTime($start) || ($end !== '' && !$this->validDateTime($end))) {
            throw new RuntimeException('Complete tipo, título, fecha y modalidad válidos para la sesión.');
        }
        if ($end !== '' && strtotime($end) <= strtotime($start)) {
            throw new RuntimeException('La fecha/hora de fin debe ser posterior al inicio.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->assertCanManageWork($workId, $actorId, $role, $pdo);
            $work = $this->lockedWork($pdo, $workId);
            if ($work['estado'] !== 'activo') {
                throw new RuntimeException('Solo se programan sesiones para trabajos activos.');
            }
            $memberCount = $pdo->prepare(
                'SELECT COUNT(*) FROM mg_trabajo_integrantes ti
                 INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion
                 WHERE ti.id_trabajo=:work AND ti.estado="activo" AND i.estado="activa"'
            );
            $memberCount->execute(['work' => $workId]);
            if ((int) $memberCount->fetchColumn() < 1) {
                throw new RuntimeException('No se puede programar una sesión para un trabajo sin estudiantes activos.');
            }
            $assignedTutor = $this->activeTutorForWork($pdo, $workId);
            $insert = $pdo->prepare(
                'INSERT INTO mg_sesiones_seguimiento
                    (id_trabajo,id_seguimiento,id_tutor,tipo,titulo,descripcion,inicio,fin,modalidad,ubicacion,creado_por)
                 VALUES (:trabajo,:hito,:tutor,:tipo,:titulo,:descripcion,:inicio,:fin,:modalidad,:ubicacion,:usuario)'
            );
            $hitoId = filter_var($input['id_seguimiento'] ?? null, FILTER_VALIDATE_INT);
            $hitoId = $hitoId !== false && $hitoId > 0 ? (int) $hitoId : null;
            if ($hitoId !== null) {
                $verify = $pdo->prepare('SELECT 1 FROM mg_seguimiento_hitos WHERE id_seguimiento=:id AND id_trabajo=:work');
                $verify->execute(['id' => $hitoId, 'work' => $workId]);
                if (!$verify->fetchColumn()) {
                    throw new RuntimeException('El hito elegido no pertenece al trabajo.');
                }
            }
            $insert->execute([
                'trabajo' => $workId, 'hito' => $hitoId, 'tutor' => $assignedTutor, 'tipo' => $type,
                'titulo' => $title, 'descripcion' => $description !== '' ? $description : null,
                'inicio' => $this->sqlDateTime($start), 'fin' => $end !== '' ? $this->sqlDateTime($end) : null,
                'modalidad' => $mode, 'ubicacion' => $location !== '' ? $location : null, 'usuario' => $actorId,
            ]);
            $sessionId = (int) $pdo->lastInsertId();
            $members = $pdo->prepare(
                'SELECT i.id_estudiante,i.id_solicitud FROM mg_trabajo_integrantes ti
                 INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado="activa"
                 INNER JOIN estudiantes e ON e.id_estudiante=i.id_estudiante
                 WHERE ti.id_trabajo=:work AND ti.estado="activo"'
            );
            $members->execute(['work' => $workId]);
            $attendance = $pdo->prepare('INSERT INTO mg_asistencias (id_sesion,id_estudiante) VALUES (:sesion,:estudiante)');
            foreach ($members->fetchAll(PDO::FETCH_ASSOC) as $member) {
                $attendance->execute(['sesion' => $sessionId, 'estudiante' => (int) $member['id_estudiante']]);
                $this->auditRequest($pdo, (int) $member['id_solicitud'], $actorId, 'sesion_programada', $title . ' · ' . $this->sqlDateTime($start));
            }
            $pdo->commit();
            return $sessionId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public function recordAttendance(int $sessionId, int $actorId, string $role, array $statuses, array $comments): void
    {
        if (!$statuses) {
            throw new RuntimeException('No hay asistencias para registrar.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $sessionQuery = $pdo->prepare('SELECT id_sesion,id_trabajo,estado FROM mg_sesiones_seguimiento WHERE id_sesion=:id FOR UPDATE');
            $sessionQuery->execute(['id' => $sessionId]);
            $session = $sessionQuery->fetch(PDO::FETCH_ASSOC);
            if (!$session || $session['estado'] === 'cancelada') {
                throw new RuntimeException('La sesión no existe o fue cancelada.');
            }
            $this->assertCanManageWork((int) $session['id_trabajo'], $actorId, $role, $pdo);
            $exists = $pdo->prepare('SELECT 1 FROM mg_asistencias WHERE id_sesion=:sesion AND id_estudiante=:estudiante');
            $update = $pdo->prepare(
                'UPDATE mg_asistencias SET estado=:estado,observacion=:nota,registrado_por=:usuario,registrado_en=CURRENT_TIMESTAMP
                 WHERE id_sesion=:sesion AND id_estudiante=:estudiante'
            );
            $requestForStudent = $pdo->prepare(
                'SELECT i.id_solicitud FROM mg_inscripciones i
                 INNER JOIN mg_trabajo_integrantes ti ON ti.id_inscripcion=i.id_inscripcion AND ti.estado="activo"
                 WHERE ti.id_trabajo=:work AND i.id_estudiante=:student AND i.estado="activa" LIMIT 1'
            );
            foreach ($statuses as $studentId => $status) {
                if (!in_array((string) $status, ['pendiente', 'presente', 'ausente', 'justificada'], true)) {
                    throw new RuntimeException('Seleccione un estado de asistencia válido.');
                }
                $exists->execute(['sesion' => $sessionId, 'estudiante' => (int) $studentId]);
                if (!$exists->fetchColumn()) {
                    throw new RuntimeException('La persona no pertenece a la sesión.');
                }
                $note = trim((string) ($comments[$studentId] ?? ''));
                if (mb_strlen($note) > 500) { throw new RuntimeException('Una observación de asistencia excede 500 caracteres.'); }
                $update->execute([
                    'estado' => $status, 'nota' => $note !== '' ? $note : null, 'usuario' => $actorId,
                    'sesion' => $sessionId, 'estudiante' => (int) $studentId,
                ]);
                $requestForStudent->execute(['work' => (int) $session['id_trabajo'], 'student' => (int) $studentId]);
                $requestId = $requestForStudent->fetchColumn();
                if ($requestId) {
                    $this->auditRequest($pdo, (int) $requestId, $actorId, 'asistencia_registrada', 'Sesión #' . $sessionId . ': ' . $status . ($note !== '' ? ' · ' . $note : ''));
                }
            }
            $pdo->prepare('UPDATE mg_sesiones_seguimiento SET estado="realizada" WHERE id_sesion=:id AND inicio<=CURRENT_TIMESTAMP')->execute(['id' => $sessionId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public function saveStageResult(int $workId, int $actorId, string $role, string $stage, string $state, string $grade, string $comment): void
    {
        if (!in_array($stage, ['mdg1', 'mdg2'], true) || !in_array($state, self::RESULTS, true)) {
            throw new RuntimeException('Seleccione etapa y resultado válidos.');
        }
        $comment = trim($comment);
        if (mb_strlen($comment) > 5000) { throw new RuntimeException('Las observaciones no pueden superar 5.000 caracteres.'); }
        $gradeValue = null;
        if (trim($grade) !== '') {
            if (!is_numeric($grade) || (float) $grade < 0 || (float) $grade > 100) {
                throw new RuntimeException('La nota debe estar entre 0 y 100.');
            }
            $gradeValue = number_format((float) $grade, 2, '.', '');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->assertCanManageWork($workId, $actorId, $role, $pdo);
            $workQuery = $pdo->prepare('SELECT id_trabajo FROM mg_trabajos WHERE id_trabajo=:id AND estado="activo" FOR UPDATE');
            $workQuery->execute(['id' => $workId]);
            if (!$workQuery->fetchColumn()) {
                throw new RuntimeException('Solo se pueden registrar resultados para trabajos activos.');
            }
            if ($stage === 'mdg2' && (new MgConfiguracion())->effectiveValue('mdg1_aprobado_antes_mdg2', true)) {
                $stageOne = $pdo->prepare('SELECT estado FROM mg_resultados_etapa WHERE id_trabajo=:work AND etapa="mdg1" FOR UPDATE');
                $stageOne->execute(['work' => $workId]);
                if ($stageOne->fetchColumn() !== 'aprobado') {
                    throw new RuntimeException('MDG I debe estar aprobado antes de iniciar o registrar MDG II.');
                }
            }
            $select = $pdo->prepare('SELECT id_resultado,estado FROM mg_resultados_etapa WHERE id_trabajo=:work AND etapa=:stage FOR UPDATE');
            $select->execute(['work' => $workId, 'stage' => $stage]);
            $old = $select->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($old) {
                $resultId = (int) $old['id_resultado'];
                $pdo->prepare('UPDATE mg_resultados_etapa SET estado=:estado,nota=:nota,observaciones=:obs,actualizado_por=:actor WHERE id_resultado=:id')
                    ->execute(['estado' => $state, 'nota' => $gradeValue, 'obs' => $comment !== '' ? $comment : null, 'actor' => $actorId, 'id' => $resultId]);
            } else {
                $pdo->prepare('INSERT INTO mg_resultados_etapa (id_trabajo,etapa,estado,nota,observaciones,actualizado_por) VALUES (:work,:stage,:estado,:nota,:obs,:actor)')
                    ->execute(['work' => $workId, 'stage' => $stage, 'estado' => $state, 'nota' => $gradeValue, 'obs' => $comment !== '' ? $comment : null, 'actor' => $actorId]);
                $resultId = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('INSERT INTO mg_resultado_etapa_historial (id_resultado,estado_anterior,estado_nuevo,nota,observaciones,registrado_por) VALUES (:id,:old,:new,:nota,:obs,:actor)')
                ->execute(['id' => $resultId, 'old' => $old['estado'] ?? null, 'new' => $state, 'nota' => $gradeValue, 'obs' => $comment !== '' ? $comment : null, 'actor' => $actorId]);
            $requests = $pdo->prepare('SELECT DISTINCT i.id_solicitud FROM mg_trabajo_integrantes ti INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado="activa" WHERE ti.id_trabajo=:work AND ti.estado="activo"');
            $requests->execute(['work' => $workId]);
            foreach ($requests->fetchAll(PDO::FETCH_COLUMN) as $requestId) {
                $this->auditRequest($pdo, (int) $requestId, $actorId, 'resultado_' . $stage, strtoupper($state) . ($gradeValue !== null ? ' · ' . $gradeValue : '') . ($comment !== '' ? ' · ' . $comment : ''));
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public function attendanceSummary(int $workId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT s.id_sesion) AS sesiones,COUNT(CASE WHEN a.estado="presente" THEN 1 END) AS presentes,
                    COUNT(CASE WHEN a.estado="ausente" THEN 1 END) AS ausencias,
                    COUNT(CASE WHEN a.estado="justificada" THEN 1 END) AS justificadas,
                    COUNT(CASE WHEN a.estado="pendiente" THEN 1 END) AS sin_registrar
             FROM mg_asistencias a INNER JOIN mg_sesiones_seguimiento s ON s.id_sesion=a.id_sesion
             WHERE s.id_trabajo=:work AND s.estado<>"cancelada"'
        );
        $statement->execute(['work' => $workId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        $counted = (int) ($row['presentes'] ?? 0) + (int) ($row['ausencias'] ?? 0);
        $row['porcentaje_presencia'] = $counted > 0 ? round(((int) $row['presentes'] / $counted) * 100, 2) : null;
        return $row;
    }

    public function studentSessions(int $workId, int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id_sesion,s.tipo,s.titulo,s.descripcion,s.inicio,s.fin,s.modalidad,s.ubicacion,s.estado,
                    a.estado AS asistencia,a.observacion AS observacion_asistencia,
                    CONCAT(tu.nombre," ",tu.apellido) AS tutor
             FROM mg_sesiones_seguimiento s
             INNER JOIN mg_asistencias a ON a.id_sesion=s.id_sesion AND a.id_estudiante=:estudiante
             LEFT JOIN tutores t ON t.id_tutor=s.id_tutor
             LEFT JOIN usuarios tu ON tu.id_usuario=t.id_usuario
             WHERE s.id_trabajo=:trabajo ORDER BY s.inicio DESC'
        );
        $statement->execute(['estudiante' => $studentId, 'trabajo' => $workId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function studentStageResults(int $workId): array
    {
        return $this->stageResults($workId);
    }

    private function sessions(int $workId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id_sesion,s.id_trabajo,s.id_seguimiento,s.id_tutor,s.tipo,s.titulo,s.descripcion,
                    s.inicio,s.fin,s.modalidad,s.ubicacion,s.estado,
                    CONCAT(ut.nombre," ",ut.apellido) AS tutor,
                    COUNT(a.id_estudiante) AS integrantes,
                    COUNT(CASE WHEN a.estado="presente" THEN 1 END) AS presentes,
                    COUNT(CASE WHEN a.estado="ausente" THEN 1 END) AS ausentes,
                    COUNT(CASE WHEN a.estado="justificada" THEN 1 END) AS justificadas,
                    COUNT(CASE WHEN a.estado="pendiente" THEN 1 END) AS sin_registrar
             FROM mg_sesiones_seguimiento s
             LEFT JOIN tutores t ON t.id_tutor=s.id_tutor
             LEFT JOIN usuarios ut ON ut.id_usuario=t.id_usuario
             LEFT JOIN mg_asistencias a ON a.id_sesion=s.id_sesion
             WHERE s.id_trabajo=:work
             GROUP BY s.id_sesion,s.id_trabajo,s.id_seguimiento,s.id_tutor,s.tipo,s.titulo,s.descripcion,
                      s.inicio,s.fin,s.modalidad,s.ubicacion,s.estado,ut.nombre,ut.apellido
             ORDER BY s.inicio DESC'
        );
        $statement->execute(['work' => $workId]);
        $sessions = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sessions as &$session) {
            $session['asistentes'] = $this->attendanceRows((int) $session['id_sesion']);
        }
        unset($session);
        return $sessions;
    }

    private function attendanceRows(int $sessionId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT a.id_estudiante,a.estado,a.observacion,e.registro_universitario,
                    CONCAT(u.nombre," ",u.apellido) AS estudiante
             FROM mg_asistencias a INNER JOIN estudiantes e ON e.id_estudiante=a.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario=e.id_usuario
             WHERE a.id_sesion=:id ORDER BY u.apellido,u.nombre'
        );
        $statement->execute(['id' => $sessionId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function stageResults(int $workId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT r.id_resultado,r.etapa,r.estado,r.nota,r.observaciones,r.actualizado_en,
                    CONCAT(u.nombre," ",u.apellido) AS actualizado_por
             FROM mg_resultados_etapa r INNER JOIN usuarios u ON u.id_usuario=r.actualizado_por
             WHERE r.id_trabajo=:work ORDER BY FIELD(r.etapa,"mdg1","mdg2")'
        );
        $statement->execute(['work' => $workId]);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$result) {
            $result['historial'] = $this->stageResultHistory((int) $result['id_resultado']);
        }
        unset($result);
        return $results;
    }

    private function stageResultHistory(int $resultId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT h.estado_anterior,h.estado_nuevo,h.nota,h.observaciones,h.registrado_en,
                    CONCAT(u.nombre," ",u.apellido) AS registrado_por
             FROM mg_resultado_etapa_historial h INNER JOIN usuarios u ON u.id_usuario=h.registrado_por
             WHERE h.id_resultado=:id ORDER BY h.registrado_en,h.id_evento'
        );
        $statement->execute(['id' => $resultId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function lockStudentTask(PDO $pdo, int $taskId, int $studentId, int $actorId): array
    {
        $statement = $pdo->prepare(
            'SELECT sh.id_seguimiento,sh.id_trabajo,sh.id_hito,sh.estado,sh.fecha_limite,h.avance_esperado_pct,h.tipo,m.requiere_informes,
                    i.id_solicitud,u.estado AS estado_usuario
             FROM mg_seguimiento_hitos sh
             INNER JOIN mg_trabajo_integrantes ti ON ti.id_trabajo=sh.id_trabajo AND ti.id_estudiante=:estudiante AND ti.estado="activo"
             INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion AND i.estado="activa"
             INNER JOIN estudiantes e ON e.id_estudiante=ti.id_estudiante AND e.id_usuario=:usuario
             INNER JOIN usuarios u ON u.id_usuario=e.id_usuario AND u.estado="activo"
             INNER JOIN mg_trabajos w ON w.id_trabajo=sh.id_trabajo AND w.estado="activo"
             INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado="activo"
             INNER JOIN mg_modalidades m ON m.id_modalidad=h.id_modalidad
             WHERE sh.id_seguimiento=:tarea FOR UPDATE'
        );
        $statement->execute(['estudiante' => $studentId, 'usuario' => $actorId, 'tarea' => $taskId]);
        $task = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$task) {
            throw new RuntimeException('El hito no existe para un trabajo propio activo.');
        }
        return $task;
    }

    private function assertCanManageWork(int $workId, int $userId, string $role, PDO $pdo): void
    {
        if ($role === 'administrador') {
            return;
        }
        if ($role !== 'tutor') {
            throw new RuntimeException('Solo el tutor asignado o Administración pueden revisar este trabajo.');
        }
        $statement = $pdo->prepare(
            'SELECT 1 FROM mg_asignaciones_tutor a INNER JOIN tutores t ON t.id_tutor=a.id_tutor
             INNER JOIN usuarios u ON u.id_usuario=t.id_usuario AND u.estado="activo"
             WHERE a.id_trabajo=:work AND a.estado="activa" AND t.id_usuario=:usuario LIMIT 1'
        );
        $statement->execute(['work' => $workId, 'usuario' => $userId]);
        if (!$statement->fetchColumn()) {
            throw new RuntimeException('El tutor no tiene una asignación activa a este trabajo.');
        }
    }

    private function lockedWork(PDO $pdo, int $workId): array
    {
        $statement = $pdo->prepare('SELECT id_trabajo,estado FROM mg_trabajos WHERE id_trabajo=:id FOR UPDATE');
        $statement->execute(['id' => $workId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) { throw new RuntimeException('No se encontró el trabajo.'); }
        return $row;
    }

    private function assertWorkAccess(int $workId, int $userId, string $role): void
    {
        if ($role === 'administrador') {
            return;
        }
        if ($role !== 'tutor') {
            throw new RuntimeException('No tiene acceso a este trabajo de grado.');
        }
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM mg_asignaciones_tutor a
             INNER JOIN tutores t ON t.id_tutor=a.id_tutor
             INNER JOIN usuarios u ON u.id_usuario=t.id_usuario AND u.estado="activo"
             INNER JOIN roles r ON r.id_rol=u.id_rol AND r.nombre_rol="tutor"
             WHERE a.id_trabajo=:trabajo AND a.id_tutor=t.id_tutor AND a.estado="activa"
               AND t.id_usuario=:usuario LIMIT 1'
        );
        $statement->execute(['trabajo' => $workId, 'usuario' => $userId]);
        if (!$statement->fetchColumn()) {
            throw new RuntimeException('Solo puedes consultar trabajos que tienes asignados activamente.');
        }
    }

    private function activeTutorForWork(PDO $pdo, int $workId): ?int
    {
        $statement = $pdo->prepare(
            'SELECT a.id_tutor FROM mg_asignaciones_tutor a
             INNER JOIN tutores t ON t.id_tutor=a.id_tutor
             INNER JOIN usuarios u ON u.id_usuario=t.id_usuario AND u.estado="activo"
             WHERE a.id_trabajo=:id AND a.estado="activa" LIMIT 1'
        );
        $statement->execute(['id' => $workId]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    private function validDateTime(string $value): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
        return $parsed !== false && $parsed->format('Y-m-d\TH:i') === $value;
    }

    private function sqlDateTime(string $value): string
    {
        return str_replace('T', ' ', $value) . ':00';
    }

    private function auditRequest(PDO $pdo, int $requestId, int $actorId, string $action, string $comment): void
    {
        $pdo->prepare(
            'INSERT INTO mg_solicitud_historial
                (id_solicitud,accion,estado_anterior,estado_nuevo,id_actor,comentario,visible_estudiante)
             VALUES (:solicitud,:accion,"aprobada","aprobada",:actor,:comentario,1)'
        )->execute(['solicitud' => $requestId, 'accion' => $action, 'actor' => $actorId, 'comentario' => $comment]);
        $student = $pdo->prepare(
            'SELECT e.id_usuario FROM mg_solicitudes s
             INNER JOIN estudiantes e ON e.id_estudiante=s.id_estudiante
             WHERE s.id_solicitud=:id LIMIT 1'
        );
        $student->execute(['id' => $requestId]);
        $studentUserId = (int) $student->fetchColumn();
        if ($studentUserId > 0) {
            (new Notificacion())->add(
                $pdo, $studentUserId, 'mg_' . $action, 'Actualización de Modalidad de Grado',
                mb_substr($comment, 0, 1000),
                'modalidades-grado/mi-solicitud.php?id=' . $requestId,
                'mg-history:' . (int) $pdo->lastInsertId()
            );
        }
    }

    private function auditAllRequestsForWork(PDO $pdo, int $workId, int $actorId, string $action, string $comment): void
    {
        $requests = $pdo->prepare(
            'SELECT DISTINCT i.id_solicitud,e.id_usuario FROM mg_trabajo_integrantes ti
             INNER JOIN mg_inscripciones i ON i.id_inscripcion=ti.id_inscripcion
             INNER JOIN estudiantes e ON e.id_estudiante=i.id_estudiante
             WHERE ti.id_trabajo=:work'
        );
        $requests->execute(['work' => $workId]);
        foreach ($requests->fetchAll(PDO::FETCH_ASSOC) as $request) {
            $this->auditRequest($pdo, (int) $request['id_solicitud'], $actorId, $action, $comment);
        }
    }
}
