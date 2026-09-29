<?php

declare(strict_types=1);

final class MgSolicitudes
{
    private const ACTIVE_STATES = ['borrador', 'enviada', 'en_revision', 'observada', 'aprobada'];

    public function modalitiesForStudent(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id_modalidad, m.codigo, m.nombre, m.descripcion, m.requiere_tutor, m.min_interesados, m.promedio_minimo,
                    m.permite_trabajo_grupal, m.max_integrantes, m.requiere_tema_preliminar, m.requiere_descripcion
             FROM estudiantes e
             INNER JOIN mg_carrera_modalidades cm ON cm.id_carrera = e.id_carrera AND cm.disponible = 1
             INNER JOIN mg_modalidades m ON m.id_modalidad = cm.id_modalidad AND m.estado = "activa"
             WHERE e.id_estudiante = :estudiante ORDER BY m.nombre'
        );
        $statement->execute(['estudiante' => $studentId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function studentRequests(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, m.codigo AS codigo_modalidad, m.nombre AS modalidad,m.promedio_minimo,
                    h.habilitado_en, h.observacion AS observacion_habilitacion,
                    i.id_inscripcion, i.estado AS estado_inscripcion, i.inscrito_en,
                    c.codigo AS codigo_cohorte, c.nombre AS cohorte,
                    w.id_trabajo, w.estado AS estado_trabajo, w.codigo AS codigo_trabajo, w.tema AS tema_trabajo, w.tipo_trabajo AS tipo_trabajo_formal,
                    cl.resultado AS resultado_cierre,cl.cerrado_en,cl.observaciones AS observaciones_cierre,
                    COALESCE(wc.integrantes, 0) AS integrantes
             FROM mg_solicitudes s
             INNER JOIN mg_modalidades m ON m.id_modalidad = s.id_modalidad
             LEFT JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
             LEFT JOIN mg_inscripciones i ON i.id_solicitud = s.id_solicitud
              LEFT JOIN mg_trabajo_integrantes ti ON ti.id_inscripcion = i.id_inscripcion AND ti.estado IN ("activo","finalizado")
             LEFT JOIN mg_trabajos w ON w.id_trabajo = ti.id_trabajo
             LEFT JOIN mg_cohortes c ON c.id_cohorte = i.id_cohorte
             LEFT JOIN mg_cierres cl ON cl.id_trabajo = w.id_trabajo
              LEFT JOIN (SELECT id_trabajo, COUNT(*) AS integrantes FROM mg_trabajo_integrantes WHERE estado IN ("activo","finalizado") GROUP BY id_trabajo) wc ON wc.id_trabajo = w.id_trabajo
             WHERE s.id_estudiante = :estudiante
             ORDER BY s.creado_en DESC, s.id_solicitud DESC'
        );
        $statement->execute(['estudiante' => $studentId]);
        $requests = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($requests as &$request) {
            $request['evidencia_academica'] = $this->latestAcademicEvidence((int)$request['id_solicitud']);
        }
        unset($request);
        return $requests;
    }

    public function requestForStudent(int $requestId, int $studentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, m.codigo AS codigo_modalidad, m.nombre AS modalidad,m.promedio_minimo,
                    m.requiere_tema_preliminar, m.requiere_descripcion, m.permite_trabajo_grupal,
                    m.max_integrantes, h.habilitado_en, h.observacion AS observacion_habilitacion,
                    i.id_inscripcion, i.estado AS estado_inscripcion, i.inscrito_en,
                    c.codigo AS codigo_cohorte, c.nombre AS cohorte,
                    w.id_trabajo, w.estado AS estado_trabajo, w.codigo AS codigo_trabajo, w.tema AS tema_trabajo, w.tipo_trabajo AS tipo_trabajo_formal,
                    cl.resultado AS resultado_cierre,cl.cerrado_en,cl.observaciones AS observaciones_cierre,
                    COALESCE(wc.integrantes, 0) AS integrantes
             FROM mg_solicitudes s
             INNER JOIN mg_modalidades m ON m.id_modalidad = s.id_modalidad
             LEFT JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
             LEFT JOIN mg_inscripciones i ON i.id_solicitud = s.id_solicitud
              LEFT JOIN mg_trabajo_integrantes ti ON ti.id_inscripcion = i.id_inscripcion AND ti.estado IN ("activo","finalizado")
             LEFT JOIN mg_trabajos w ON w.id_trabajo = ti.id_trabajo
             LEFT JOIN mg_cohortes c ON c.id_cohorte = i.id_cohorte
             LEFT JOIN mg_cierres cl ON cl.id_trabajo = w.id_trabajo
              LEFT JOIN (SELECT id_trabajo, COUNT(*) AS integrantes FROM mg_trabajo_integrantes WHERE estado IN ("activo","finalizado") GROUP BY id_trabajo) wc ON wc.id_trabajo = w.id_trabajo
             WHERE s.id_solicitud = :solicitud AND s.id_estudiante = :estudiante LIMIT 1'
        );
        $statement->execute(['solicitud' => $requestId, 'estudiante' => $studentId]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            return null;
        }
        $request['historial'] = $this->history($requestId, true);
        $request['evidencia_academica'] = $this->latestAcademicEvidence($requestId);
        return $request;
    }

    public function latestAcademicEvidence(int $requestId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT ev.id_evidencia,ev.id_solicitud,ev.id_plan_estudio,ev.id_carrera,ev.plan_referencia,ev.numero_version,
                    ev.nombre_archivo,ev.tamano_bytes,ev.comentario_estudiante,ev.estado,
                    ev.materias_requeridas,ev.materias_aprobadas,ev.promedio_verificado,
                    ev.observacion_revision,ev.subido_en,ev.revisado_en,
                    p.codigo_plan,p.version_plan,c.nombre_carrera,
                    CONCAT(su.nombre," ",su.apellido) AS subido_por,
                    CONCAT(ru.nombre," ",ru.apellido) AS revisado_por
             FROM mg_solicitud_evidencias ev
             LEFT JOIN mg_planes_estudio p ON p.id_plan_estudio=ev.id_plan_estudio
             INNER JOIN carreras c ON c.id_carrera=COALESCE(ev.id_carrera,p.id_carrera)
             INNER JOIN usuarios su ON su.id_usuario=ev.subido_por
             LEFT JOIN usuarios ru ON ru.id_usuario=ev.revisado_por
             WHERE ev.id_solicitud=:solicitud ORDER BY ev.numero_version DESC LIMIT 1'
        );
        $statement->execute(['solicitud' => $requestId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['materias'] = $this->evidenceSubjects((int)$row['id_evidencia']);
        return $row;
    }

    public function academicEvidenceDownload(int $evidenceId, ?int $studentId = null): array
    {
        $sql = 'SELECT ev.nombre_archivo,ev.ruta_archivo,s.id_estudiante
                FROM mg_solicitud_evidencias ev
                INNER JOIN mg_solicitudes s ON s.id_solicitud=ev.id_solicitud
                WHERE ev.id_evidencia=:id';
        $params = ['id' => $evidenceId];
        if ($studentId !== null) {
            $sql .= ' AND s.id_estudiante=:estudiante';
            $params['estudiante'] = $studentId;
        }
        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        $file = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$file) {
            throw new RuntimeException('No se encontró la evidencia académica solicitada.');
        }
        $relative = (string)$file['ruta_archivo'];
        $prefix = 'storage/mg-academic-evidence/';
        if (!str_starts_with($relative, $prefix) || basename($relative) !== substr($relative, strlen($prefix))) {
            throw new RuntimeException('La ruta de la evidencia no es válida.');
        }
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('El archivo de evidencia ya no está disponible.');
        }
        return ['path' => $path, 'name' => basename((string)$file['nombre_archivo']), 'size' => filesize($path)];
    }

    private function evidenceSubjects(int $evidenceId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT em.id_materia,m.nombre_materia,em.estado,em.nota,em.periodo
             FROM mg_solicitud_evidencia_materias em
             INNER JOIN materias m ON m.id_materia=em.id_materia
             WHERE em.id_evidencia=:evidencia
             UNION ALL
             SELECT NULL AS id_materia,ml.nombre_materia,ml.estado,ml.nota,ml.periodo
             FROM mg_solicitud_evidencia_materias_libres ml
             WHERE ml.id_evidencia=:evidencia_libre
             ORDER BY nombre_materia'
        );
        $statement->execute(['evidencia' => $evidenceId, 'evidencia_libre' => $evidenceId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveDraft(int $studentId, int $actorId, array $input): int
    {
        $requestId = filter_var($input['id_solicitud'] ?? null, FILTER_VALIDATE_INT);
        $requestId = $requestId !== false && $requestId > 0 ? (int) $requestId : null;
        $modalityId = filter_var($input['id_modalidad'] ?? null, FILTER_VALIDATE_INT);
        if ($modalityId === false || $modalityId < 1) {
            throw new RuntimeException('Seleccione una modalidad disponible.');
        }
        $workType = (string) ($input['tipo_trabajo'] ?? 'individual');
        if (!in_array($workType, ['individual', 'grupal'], true)) {
            throw new RuntimeException('Seleccione un tipo de trabajo válido.');
        }
        $topic = trim((string) ($input['tema_preliminar'] ?? ''));
        $description = trim((string) ($input['descripcion'] ?? ''));
        $studentNotes = trim((string) ($input['observaciones_estudiante'] ?? ''));
        if (mb_strlen($topic) > 250 || mb_strlen($description) > 10000 || mb_strlen($studentNotes) > 5000) {
            throw new RuntimeException('Uno o más campos superan la longitud permitida.');
        }

        $pdo = Database::connection();
        $configuration = new MgConfiguracion();
        $pdo->beginTransaction();
        try {
            $student = $this->lockStudent($pdo, $studentId, $actorId);
            $modality = $this->availableModality($pdo, $studentId, $modalityId);
            if ($workType === 'grupal' && (int) $modality['permite_trabajo_grupal'] !== 1) {
                throw new RuntimeException('Esta modalidad no permite solicitar intención de trabajo grupal.');
            }
            if ((int) $modality['requiere_tema_preliminar'] === 1 && $topic === '') {
                throw new RuntimeException('Ingrese el tema preliminar requerido por la modalidad.');
            }
            if ((int) $modality['requiere_descripcion'] === 1 && $description === '') {
                throw new RuntimeException('Ingrese la descripción requerida por la modalidad.');
            }

            if ($requestId === null) {
                $active = $pdo->prepare(
                    'SELECT id_solicitud FROM mg_solicitudes
                     WHERE id_estudiante = :estudiante AND estado IN ("borrador", "enviada", "en_revision", "observada", "aprobada")
                     LIMIT 1 FOR UPDATE'
                );
                $active->execute(['estudiante' => $studentId]);
                if ($active->fetchColumn()) {
                    throw new RuntimeException('Ya existe una solicitud activa. Continúe desde su solicitud actual.');
                }
                $insert = $pdo->prepare(
                    'INSERT INTO mg_solicitudes
                        (id_estudiante, id_modalidad, tipo_trabajo, tema_preliminar, descripcion, observaciones_estudiante)
                     VALUES (:estudiante, :modalidad, :tipo, :tema, :descripcion, :observaciones)'
                );
                $insert->execute([
                    'estudiante' => $studentId, 'modalidad' => $modalityId, 'tipo' => $workType,
                    'tema' => $topic !== '' ? $topic : null, 'descripcion' => $description !== '' ? $description : null,
                    'observaciones' => $studentNotes !== '' ? $studentNotes : null,
                ]);
                $requestId = (int) $pdo->lastInsertId();
                $this->event($pdo, $requestId, 'creada', null, 'borrador', $actorId, null, null);
            } else {
                $current = $this->lockOwnedRequest($pdo, $requestId, $studentId);
                $editableStates = ['borrador'];
                if ($configuration->effectiveValue('permitir_corregir_solicitud_observada', true)) {
                    $editableStates[] = 'observada';
                }
                if ($configuration->effectiveValue('permitir_editar_solicitud_enviada', false)) {
                    $editableStates = array_merge($editableStates, ['enviada', 'en_revision']);
                }
                if (!in_array($current['estado'], $editableStates, true)) {
                    throw new RuntimeException('Solo se puede editar un borrador o una solicitud observada.');
                }
                $submittedEdit = in_array($current['estado'], ['enviada', 'en_revision'], true);
                $summary = $submittedEdit ? $this->academicSummaryOrNull($studentId) : null;
                if ($submittedEdit) {
                    $this->requireAllRequiredSubjectsPassed($summary);
                }
                $update = $pdo->prepare(
                    'UPDATE mg_solicitudes SET id_modalidad = :modalidad, tipo_trabajo = :tipo,
                        tema_preliminar = :tema, descripcion = :descripcion, observaciones_estudiante = :observaciones,
                        estado = :estado, enviado_en = CASE WHEN :reenviada = 1 THEN CURRENT_TIMESTAMP ELSE enviado_en END,
                        revisado_por = CASE WHEN :reinicia_revision_usuario = 1 THEN NULL ELSE revisado_por END,
                        revisado_en = CASE WHEN :reinicia_revision_fecha = 1 THEN NULL ELSE revisado_en END,
                        id_plan_verificado = CASE WHEN :actualiza_resumen_plan = 1 THEN :plan ELSE id_plan_verificado END,
                        materias_requeridas_snapshot = CASE WHEN :actualiza_resumen_requeridas = 1 THEN :requeridas ELSE materias_requeridas_snapshot END,
                        materias_aprobadas_snapshot = CASE WHEN :actualiza_resumen_aprobadas = 1 THEN :aprobadas ELSE materias_aprobadas_snapshot END,
                        promedio_snapshot = CASE WHEN :actualiza_resumen_promedio = 1 THEN :promedio ELSE promedio_snapshot END,
                        verificado_en = CASE WHEN :actualiza_resumen_fecha = 1 THEN CURRENT_TIMESTAMP ELSE verificado_en END
                     WHERE id_solicitud = :id'
                );
                $newState = $submittedEdit ? 'enviada' : $current['estado'];
                $update->execute([
                    'modalidad' => $modalityId, 'tipo' => $workType,
                    'tema' => $topic !== '' ? $topic : null, 'descripcion' => $description !== '' ? $description : null,
                    'observaciones' => $studentNotes !== '' ? $studentNotes : null, 'estado' => $newState,
                    'reenviada' => $submittedEdit ? 1 : 0,
                    'reinicia_revision_usuario' => $submittedEdit ? 1 : 0,
                    'reinicia_revision_fecha' => $submittedEdit ? 1 : 0,
                    'actualiza_resumen_plan' => $submittedEdit ? 1 : 0,
                    'actualiza_resumen_requeridas' => $submittedEdit ? 1 : 0,
                    'actualiza_resumen_aprobadas' => $submittedEdit ? 1 : 0,
                    'actualiza_resumen_promedio' => $submittedEdit ? 1 : 0,
                    'actualiza_resumen_fecha' => $submittedEdit ? 1 : 0,
                    'plan' => $summary['id_plan_estudio'] ?? null, 'requeridas' => $summary['materias_requeridas'] ?? null,
                    'aprobadas' => $summary['materias_aprobadas'] ?? null, 'promedio' => $summary['promedio_aprobadas'] ?? null,
                    'id' => $requestId,
                ]);
                $action = $submittedEdit ? 'actualizada_por_estudiante' : ($current['estado'] === 'observada' ? 'corregida' : 'borrador_actualizado');
                $this->event($pdo, $requestId, $action, $current['estado'], $newState, $actorId, null, $summary);
                if ($submittedEdit) {
                    (new Notificacion())->notifyAdministrators(
                        $pdo, 'mg_solicitud_actualizada', 'Solicitud de Modalidad de Grado actualizada',
                        'El estudiante actualizó la solicitud #' . $requestId . '; vuelve a estar pendiente de revisión.',
                        'modalidades-grado/solicitudes.php?id=' . $requestId,
                        'mg-request:' . $requestId . ':student-edit:' . date('YmdHis')
                    );
                }
            }
            $pdo->commit();
            return $requestId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
                throw new RuntimeException('Ya existe una solicitud activa para este estudiante.');
            }
            throw $exception;
        }
    }

    public function uploadAcademicEvidence(int $requestId, int $studentId, int $actorId, array $file, string $comment): int
    {
        $comment = trim($comment);
        if (mb_strlen($comment) > 2000) {
            throw new RuntimeException('El comentario del archivo no puede superar 2.000 caracteres.');
        }
        $temporaryPath = (string)($file['tmp_name'] ?? '');
        $originalName = basename((string)($file['name'] ?? 'calificaciones.pdf'));
        $size = (int)($file['size'] ?? 0);
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($temporaryPath)
            || strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf'
            || $size < 1 || $size > 5_000_000
            || (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) !== 'application/pdf') {
            throw new RuntimeException('Adjunte un PDF válido de calificaciones que no supere 5 MB.');
        }
        $handle = fopen($temporaryPath, 'rb');
        $signature = $handle ? fread($handle, 5) : false;
        if ($handle) {
            fclose($handle);
        }
        if ($signature !== '%PDF-') {
            throw new RuntimeException('El archivo no tiene una firma PDF válida.');
        }
        $hash = hash_file('sha256', $temporaryPath);
        if ($hash === false) {
            throw new RuntimeException('No se pudo verificar el archivo recibido.');
        }
        $plan = (new MgAcademico())->assignedPlan($studentId);
        $student = (new Estudiante())->findById($studentId);
        if (!$student) {
            throw new RuntimeException('El perfil de estudiante no está disponible.');
        }

        $relativePath = 'storage/mg-academic-evidence/' . $hash . '-' . bin2hex(random_bytes(5)) . '.pdf';
        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento privado de evidencias.');
        }

        $pdo = Database::connection();
        $stored = false;
        $pdo->beginTransaction();
        try {
            $this->lockStudent($pdo, $studentId, $actorId);
            $request = $this->lockOwnedRequest($pdo, $requestId, $studentId);
            if (!in_array($request['estado'], ['borrador', 'observada'], true)) {
                throw new RuntimeException('Solo se puede adjuntar evidencia a un borrador o solicitud observada.');
            }
            $planLock = $pdo->prepare('SELECT id_plan_estudio FROM mg_estudiante_plan WHERE id_estudiante=:id FOR UPDATE');
            $planLock->execute(['id' => $studentId]);
            $currentPlanId = $planLock->fetchColumn();
            if (($currentPlanId === false ? null : (int)$currentPlanId) !== ($plan === null ? null : (int)$plan['id_plan_estudio'])) {
                throw new RuntimeException('El plan del estudiante cambió; recargue y vuelva a adjuntar el documento.');
            }
            $versionQuery = $pdo->prepare('SELECT COALESCE(MAX(numero_version),0) FROM mg_solicitud_evidencias WHERE id_solicitud=:id FOR UPDATE');
            $versionQuery->execute(['id' => $requestId]);
            $version = (int)$versionQuery->fetchColumn() + 1;
            if (!move_uploaded_file($temporaryPath, $absolutePath)) {
                throw new RuntimeException('No se pudo guardar el archivo de calificaciones.');
            }
            $stored = true;
            $insert = $pdo->prepare(
                'INSERT INTO mg_solicitud_evidencias
                    (id_solicitud,id_plan_estudio,id_carrera,numero_version,nombre_archivo,ruta_archivo,hash_archivo,tamano_bytes,comentario_estudiante,subido_por)
                 VALUES (:solicitud,:plan,:carrera,:version,:nombre,:ruta,:hash,:bytes,:comentario,:actor)'
            );
            $insert->execute([
                'solicitud' => $requestId, 'plan' => $plan ? (int)$plan['id_plan_estudio'] : null,
                'carrera' => (int)$student['id_carrera'], 'version' => $version,
                'nombre' => mb_substr($originalName, 0, 255), 'ruta' => $relativePath, 'hash' => $hash,
                'bytes' => $size, 'comentario' => $comment !== '' ? $comment : null, 'actor' => $actorId,
            ]);
            $evidenceId = (int)$pdo->lastInsertId();
            $pdo->prepare(
                'UPDATE mg_solicitudes SET fuente_verificacion_academica=NULL,id_evidencia_verificada=NULL,
                    id_plan_verificado=NULL,materias_requeridas_snapshot=NULL,materias_aprobadas_snapshot=NULL,
                    promedio_snapshot=NULL,verificado_en=NULL WHERE id_solicitud=:id'
            )->execute(['id' => $requestId]);
            $this->event($pdo, $requestId, 'evidencia_academica_subida', $request['estado'], $request['estado'], $actorId,
                'Versión ' . $version . ' del documento de calificaciones adjuntada.', [
                    'fuente' => 'documento_pendiente', 'evidencia_id' => $evidenceId,
                    'version' => $version, 'plan' => $plan ? (int)$plan['id_plan_estudio'] : null,
                ]);
            $pdo->commit();
            return $evidenceId;
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

    public function reviewAcademicEvidence(int $requestId, int $adminId, string $action, array $input): void
    {
        if (!in_array($action, ['verificar_evidencia', 'observar_evidencia'], true)) {
            throw new RuntimeException('Seleccione una acción de revisión de evidencia válida.');
        }
        $note = trim((string)($input['observacion_revision'] ?? ''));
        if (($action === 'observar_evidencia' && $note === '') || mb_strlen($note) > 5000) {
            throw new RuntimeException('Indique qué debe corregirse en el documento (máximo 5.000 caracteres).');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $request = $this->lockAdminRequest($pdo, $requestId);
            $this->assertState($request, ['en_revision']);
            $evidenceQuery = $pdo->prepare(
                'SELECT * FROM mg_solicitud_evidencias
                 WHERE id_solicitud=:solicitud ORDER BY numero_version DESC LIMIT 1 FOR UPDATE'
            );
            $evidenceQuery->execute(['solicitud' => $requestId]);
            $evidence = $evidenceQuery->fetch(PDO::FETCH_ASSOC);
            if (!$evidence || $evidence['estado'] !== 'pendiente') {
                throw new RuntimeException('No hay una versión pendiente de evidencia para revisar.');
            }
            if ((int)($input['id_evidencia'] ?? 0) !== (int)$evidence['id_evidencia']) {
                throw new RuntimeException('El documento cambió antes de la revisión. Recargue el expediente.');
            }
            if ($action === 'observar_evidencia') {
                $pdo->prepare('UPDATE mg_solicitud_evidencias SET estado="observada",observacion_revision=:note,revisado_por=:actor,revisado_en=CURRENT_TIMESTAMP WHERE id_evidencia=:id')
                    ->execute(['note' => $note, 'actor' => $adminId, 'id' => (int)$evidence['id_evidencia']]);
                $this->transition($pdo, $request, 'observada', 'evidencia_academica_observada', $adminId, $note, [
                    'evidencia_id' => (int)$evidence['id_evidencia'], 'version' => (int)$evidence['numero_version'],
                ]);
                (new Notificacion())->add(
                    $pdo, (int)$request['id_usuario_estudiante'], 'mg_evidencia_observada', 'Calificaciones observadas',
                    $note, 'modalidades-grado/mi-solicitud.php?id=' . $requestId,
                    'mg-grade-evidence:' . $evidence['id_evidencia'] . ':observed'
                );
                $pdo->commit();
                return;
            }

            $storedFile = $this->academicEvidenceDownload((int)$evidence['id_evidencia']);
            $storedHash = hash_file('sha256', $storedFile['path']);
            if ($storedHash === false || !hash_equals((string)$evidence['hash_archivo'], $storedHash)) {
                throw new RuntimeException('El archivo guardado no coincide con la evidencia cargada; no puede verificarse.');
            }

            $plan = (new MgAcademico())->assignedPlan((int)$request['id_estudiante']);
            $student = (new Estudiante())->findById((int)$request['id_estudiante']);
            if (!$student || (int)$student['id_carrera'] !== (int)$evidence['id_carrera']
                || ($plan ? (int)$plan['id_plan_estudio'] !== (int)$evidence['id_plan_estudio'] : $evidence['id_plan_estudio'] !== null)) {
                throw new RuntimeException('La carrera o el plan cambió; observe el documento y solicite una nueva versión.');
            }
            $planReference = null;
            if ($plan) {
                $subjects = (new MgAcademico())->requiredSubjectsForPlan((int)$plan['id_plan_estudio']);
                if (!$subjects) {
                    throw new RuntimeException('El plan no tiene materias obligatorias para verificar.');
                }
                $submitted = is_array($input['materias'] ?? null) ? $input['materias'] : [];
                $expectedIds = array_map(static fn(array $subject): string => (string)$subject['id_materia'], $subjects);
                $submittedIds = array_map('strval', array_keys($submitted));
                sort($expectedIds, SORT_STRING);
                sort($submittedIds, SORT_STRING);
                if ($expectedIds !== $submittedIds) {
                    throw new RuntimeException('Verifique cada materia obligatoria del plan; no se aceptan filas incompletas o ajenas al plan.');
                }
            } else {
                $planReference = trim((string)($input['plan_referencia'] ?? ''));
                if ($planReference === '' || mb_strlen($planReference) > 100
                    || (string)($input['confirma_materias_plan'] ?? '') !== '1') {
                    throw new RuntimeException('Indique el plan o malla comprobado y confirme que el PDF incluye todas sus materias obligatorias.');
                }
                $lines = preg_split('/\R/u', trim((string)($input['materias_sin_plan'] ?? '')));
                $lines = array_values(array_filter(array_map('trim', $lines ?: []), static fn(string $line): bool => $line !== ''));
                if (!$lines || count($lines) > 200) {
                    throw new RuntimeException('Liste entre 1 y 200 materias verificadas del plan o malla.');
                }
                $subjects = [];
                $submitted = [];
                $seen = [];
                foreach ($lines as $index => $line) {
                    $columns = str_getcsv($line, ';', '"', '');
                    if (count($columns) !== 4) {
                        throw new RuntimeException('Fila ' . ($index + 1) . ': use Materia;Estado;Nota;Periodo.');
                    }
                    [$name, $status, $score, $period] = array_map('trim', $columns);
                    $key = mb_strtolower($name);
                    if ($name === '' || mb_strlen($name) > 150 || isset($seen[$key])) {
                        throw new RuntimeException('Fila ' . ($index + 1) . ': nombre de materia vacío, repetido o demasiado largo.');
                    }
                    $seen[$key] = true;
                    $subjects[] = ['id_materia' => null, 'nombre_materia' => $name];
                    $submitted[$index] = ['estado' => $status, 'nota' => $score, 'periodo' => $period];
                }
            }
            $approved = 0;
            $gradeTotal = 0.0;
            $save = $pdo->prepare(
                'INSERT INTO mg_solicitud_evidencia_materias (id_evidencia,id_materia,estado,nota,periodo)
                 VALUES (:evidencia,:materia,:estado,:nota,:periodo)'
            );
            $saveManual = $pdo->prepare(
                'INSERT INTO mg_solicitud_evidencia_materias_libres (id_evidencia,nombre_materia,estado,nota,periodo)
                 VALUES (:evidencia,:materia,:estado,:nota,:periodo)'
            );
            foreach ($subjects as $index => $subject) {
                $id = $subject['id_materia'] !== null ? (int)$subject['id_materia'] : null;
                $grade = $id !== null ? ($submitted[$id] ?? []) : ($submitted[$index] ?? []);
                $status = strtoupper(trim((string)($grade['estado'] ?? '')));
                $score = trim((string)($grade['nota'] ?? ''));
                $period = trim((string)($grade['periodo'] ?? ''));
                if (!in_array($status, ['APROBADA','REPROBADA'], true)
                    || !is_numeric($score) || (float)$score < 0 || (float)$score > 100
                    || mb_strlen($period) > 40) {
                    throw new RuntimeException('Complete estado, nota (0–100) y periodo válido para ' . $subject['nombre_materia'] . '.');
                }
                if ($status === 'APROBADA') {
                    $approved++;
                }
                $gradeTotal += (float)$score;
                ($id !== null ? $save : $saveManual)->execute([
                    'evidencia' => (int)$evidence['id_evidencia'], 'materia' => $id ?? $subject['nombre_materia'], 'estado' => $status,
                    'nota' => number_format((float)$score, 2, '.', ''), 'periodo' => $period !== '' ? $period : null,
                ]);
            }
            if ($approved !== count($subjects)) {
                throw new RuntimeException('La evidencia contiene materias sin aprobar. Obsérvela y solicite una corrección; no puede marcarse como verificada.');
            }
            $average = number_format($gradeTotal / count($subjects), 2, '.', '');
            if ($request['codigo_modalidad'] === 'GRADUACION_EXCELENCIA'
                && ((float)$average <= 90.0 || ($request['promedio_minimo'] !== null && (float)$average < (float)$request['promedio_minimo']))) {
                throw new RuntimeException('El promedio verificado no cumple Graduación por Excelencia: debe superar 90 puntos y el mínimo propio de la modalidad.');
            }
            $pdo->prepare(
                'UPDATE mg_solicitud_evidencias SET estado="verificada",materias_requeridas=:total,
                    materias_aprobadas=:aprobadas,promedio_verificado=:promedio,
                    plan_referencia=:plan_referencia,
                    observacion_revision=:note,revisado_por=:actor,revisado_en=CURRENT_TIMESTAMP
                 WHERE id_evidencia=:id'
            )->execute([
                'total' => count($subjects), 'aprobadas' => $approved, 'promedio' => $average,
                'plan_referencia' => $planReference, 'note' => $note !== '' ? $note : null,
                'actor' => $adminId, 'id' => (int)$evidence['id_evidencia'],
            ]);
            $this->event($pdo, $requestId, 'evidencia_academica_verificada', 'en_revision', 'en_revision', $adminId,
                $note !== '' ? $note : 'Documento y calificaciones verificados por Administración.', [
                    'fuente' => 'documento_verificado', 'evidencia_id' => (int)$evidence['id_evidencia'],
                    'plan' => $plan ? (int)$plan['id_plan_estudio'] : $planReference,
                    'plan_asignado' => $plan !== null, 'materias_requeridas' => count($subjects),
                    'materias_aprobadas' => $approved, 'promedio' => (float)$average,
                ]);
            (new Notificacion())->add(
                $pdo, (int)$request['id_usuario_estudiante'], 'mg_evidencia_verificada', 'Calificaciones verificadas',
                'Administración verificó el documento. La solicitud todavía requiere aprobación administrativa.',
                'modalidades-grado/mi-solicitud.php?id=' . $requestId,
                'mg-grade-evidence:' . $evidence['id_evidencia'] . ':verified'
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function submit(int $requestId, int $studentId, int $actorId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->lockStudent($pdo, $studentId, $actorId);
            $request = $this->lockOwnedRequest($pdo, $requestId, $studentId);
            if (!in_array($request['estado'], ['borrador', 'observada'], true)) {
                throw new RuntimeException('Solo un borrador o una solicitud corregida puede enviarse.');
            }
            $configuration = new MgConfiguracion();
            if ($request['estado'] === 'observada') {
                if (!$configuration->effectiveValue('permitir_reenviar_solicitud_observada', true)) {
                    throw new RuntimeException('La configuración actual no permite reenviar solicitudes observadas.');
                }
                $resubmissions = $pdo->prepare('SELECT COUNT(*) FROM mg_solicitud_historial WHERE id_solicitud=:id AND accion="reenviada"');
                $resubmissions->execute(['id' => $requestId]);
                $maximum = (int) $configuration->effectiveValue('max_reenvios_solicitud', PHP_INT_MAX);
                if ((int) $resubmissions->fetchColumn() >= $maximum) {
                    throw new RuntimeException('La solicitud alcanzó el máximo de reenvíos permitidos.');
                }
            }
            $modality = $this->validateRequestModality($pdo, $studentId, $request);
            $decision = $this->academicVerificationForDecision($studentId, $request + $modality);
            $evidence = $this->latestAcademicEvidence($requestId);
            $plan = (new MgAcademico())->assignedPlan($studentId);
            $evidenceReadyForReview = $this->academicEvidenceReadyToSubmit($studentId, $requestId, $request);
            if ($decision === null && !$evidenceReadyForReview) {
                throw new RuntimeException('Para enviar, completa la verificación académica oficial o adjunta el PDF de calificaciones para revisión administrativa.');
            }
            $summary = $decision['resumen'] ?? null;
            $verificationSource = $decision['fuente'] ?? null;
            $verifiedEvidenceId = $decision['evidencia_id'] ?? null;
            $update = $pdo->prepare(
                'UPDATE mg_solicitudes SET estado = "enviada", id_plan_verificado = :plan,
                    materias_requeridas_snapshot = :requeridas, materias_aprobadas_snapshot = :aprobadas,
                    promedio_snapshot = :promedio, verificado_en = CASE WHEN :verified=1 THEN CURRENT_TIMESTAMP ELSE NULL END, enviado_en = CURRENT_TIMESTAMP,
                    fuente_verificacion_academica=:fuente, id_evidencia_verificada=:evidencia
                 WHERE id_solicitud = :id'
            );
            $update->execute([
                'plan' => $summary['id_plan_estudio'] ?? null,
                'requeridas' => $summary['materias_requeridas'] ?? null,
                'aprobadas' => $summary['materias_aprobadas'] ?? null, 'promedio' => $summary['promedio_aprobadas'] ?? null,
                'verified' => $verificationSource !== null ? 1 : 0,
                'fuente' => $verificationSource, 'evidencia' => $verifiedEvidenceId, 'id' => $requestId,
            ]);
            $action = $request['estado'] === 'observada' ? 'reenviada' : 'enviada';
            $eventSummary = $summary ?? [];
            if ($evidenceReadyForReview && $verificationSource === null) {
                $eventSummary += [
                    'fuente' => $evidence['estado'] === 'pendiente' ? 'documento_pendiente' : 'documento_verificado_revisar_elegibilidad',
                    'evidencia_id' => (int)$evidence['id_evidencia'],
                ];
            }
            $this->event($pdo, $requestId, $action, $request['estado'], 'enviada', $actorId, null, $eventSummary);
            (new Notificacion())->notifyAdministrators(
                $pdo, 'mg_solicitud_enviada', 'Solicitud de Modalidad de Grado enviada',
                'Hay una nueva solicitud #' . $requestId . ' pendiente de revisión.',
                'modalidades-grado/solicitudes.php', 'mg-request:' . $requestId . ':' . $action . ':' . date('YmdHis')
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function cancelOwn(int $requestId, int $studentId, int $actorId, string $note): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->lockStudent($pdo, $studentId, $actorId);
            $request = $this->lockOwnedRequest($pdo, $requestId, $studentId);
            $configuration = new MgConfiguracion();
            $cancellableStates = ['borrador'];
            if ($configuration->effectiveValue('permitir_corregir_solicitud_observada', true)) {
                $cancellableStates[] = 'observada';
            }
            if ($configuration->effectiveValue('permitir_cancelar_solicitud_enviada', false)) {
                $cancellableStates = array_merge($cancellableStates, ['enviada', 'en_revision']);
            }
            if (!in_array($request['estado'], $cancellableStates, true)) {
                throw new RuntimeException('La solicitud no se puede cancelar en su estado actual.');
            }
            $update = $pdo->prepare('UPDATE mg_solicitudes SET estado = "cancelada" WHERE id_solicitud = :id');
            $update->execute(['id' => $requestId]);
            $this->event($pdo, $requestId, 'cancelada', $request['estado'], 'cancelada', $actorId, $note !== '' ? $note : null, null);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function adminRequests(string $state = ''): array
    {
        $sql = 'SELECT s.id_solicitud, s.estado, s.tipo_trabajo, s.tema_preliminar, s.enviado_en,
                       e.id_estudiante, e.registro_universitario, CONCAT(u.nombre, " ", u.apellido) AS estudiante,
                       c.nombre_carrera, m.nombre AS modalidad,
                       h.id_habilitacion, h.habilitado_en
                FROM mg_solicitudes s
                INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
                INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
                INNER JOIN carreras c ON c.id_carrera = e.id_carrera
                INNER JOIN mg_modalidades m ON m.id_modalidad = s.id_modalidad
                LEFT JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud';
        $params = [];
        if (in_array($state, ['borrador', 'enviada', 'en_revision', 'observada', 'aprobada', 'rechazada', 'cancelada'], true)) {
            $sql .= ' WHERE s.estado = :estado';
            $params['estado'] = $state;
        }
        $sql .= ' ORDER BY FIELD(s.estado, "enviada", "en_revision", "observada", "aprobada", "borrador", "rechazada", "cancelada"), s.enviado_en, s.creado_en DESC LIMIT 500';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adminRequest(int $requestId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, e.registro_universitario, e.id_carrera, e.id_estudiante,
                    CONCAT(u.nombre, " ", u.apellido) AS estudiante, u.estado AS estado_usuario,
                    u.correo, c.nombre_carrera, m.codigo AS codigo_modalidad, m.nombre AS modalidad,
                    m.promedio_minimo,m.min_interesados,
                    m.permite_trabajo_grupal, m.requiere_tema_preliminar, m.requiere_descripcion,
                    CONCAT(r.nombre, " ", r.apellido) AS revisor,
                    h.id_habilitacion, h.habilitado_en, h.observacion AS observacion_habilitacion,
                    CONCAT(hu.nombre, " ", hu.apellido) AS habilitado_por,
                    EXISTS (
                        SELECT 1 FROM mg_carrera_modalidades cm
                        WHERE cm.id_carrera = e.id_carrera AND cm.id_modalidad = m.id_modalidad
                          AND cm.disponible = 1 AND m.estado = "activa"
                    ) AS modalidad_disponible
             FROM mg_solicitudes s
             INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             INNER JOIN carreras c ON c.id_carrera = e.id_carrera
             INNER JOIN mg_modalidades m ON m.id_modalidad = s.id_modalidad
             LEFT JOIN usuarios r ON r.id_usuario = s.revisado_por
             LEFT JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
             LEFT JOIN usuarios hu ON hu.id_usuario = h.habilitado_por
             WHERE s.id_solicitud = :id LIMIT 1'
        );
        $statement->execute(['id' => $requestId]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            return null;
        }
        $request['historial'] = $this->history($requestId);
        $request['verificacion_actual'] = $this->academicSummaryOrNull((int) $request['id_estudiante']);
        $request['materias_pendientes_actuales'] = (new MgAcademico())->studentPendingSubjects((int) $request['id_estudiante']);
        $request['plan_asignado'] = (new MgAcademico())->assignedPlan((int)$request['id_estudiante']);
        $request['materias_plan'] = $request['plan_asignado']
            ? (new MgAcademico())->requiredSubjectsForPlan((int)$request['plan_asignado']['id_plan_estudio'])
            : [];
        $request['evidencia_academica'] = $this->latestAcademicEvidence($requestId);
        $request['academic_verification_ready'] = $this->academicVerificationForDecision((int)$request['id_estudiante'], $request) !== null;
        return $request;
    }

    public function review(int $requestId, int $reviewerId, string $action, string $note): void
    {
        $allowed = ['iniciar_revision', 'observar', 'aprobar', 'rechazar'];
        if (!in_array($action, $allowed, true)) {
            throw new RuntimeException('Seleccione una acción de revisión válida.');
        }
        $note = trim($note);
        if (in_array($action, ['observar', 'rechazar'], true) && $note === '') {
            throw new RuntimeException('Indique el motivo de la observación o rechazo.');
        }
        if (mb_strlen($note) > 5000) {
            throw new RuntimeException('La observación no puede superar 5.000 caracteres.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $request = $this->lockAdminRequest($pdo, $requestId);
            if ($action === 'iniciar_revision') {
                $this->assertState($request, ['enviada']);
                $this->transition($pdo, $request, 'en_revision', 'revision_iniciada', $reviewerId, $note, null, false);
            } else {
                $this->assertState($request, ['en_revision']);
                if ($action === 'aprobar') {
                    $this->assertStudentActive($pdo, (int) $request['id_estudiante']);
                    $modality = $this->validateRequestModality($pdo, (int) $request['id_estudiante'], $request);
                    $verification = $this->academicVerificationForDecision((int)$request['id_estudiante'], $request + $modality);
                    if ($verification === null) {
                        throw new RuntimeException('No se puede aprobar: falta historial académico completo o evidencia documental verificada y elegible.');
                    }
                    $this->assertMinimumInterested((int)$request['id_estudiante'], $modality);
                    $summary = $verification['resumen'];
                    $this->saveAcademicSnapshot($pdo, $requestId, $summary, $verification['fuente'], $verification['evidencia_id']);
                    $this->updateReview($pdo, $requestId, $reviewerId);
                    $summary['fuente_verificacion'] = $verification['fuente'];
                    $summary['id_evidencia'] = $verification['evidencia_id'];
                    $this->transition($pdo, $request, 'aprobada', 'aprobada', $reviewerId, $note, $summary);
                } elseif ($action === 'observar') {
                    $this->updateReview($pdo, $requestId, $reviewerId);
                    $this->transition($pdo, $request, 'observada', 'observada', $reviewerId, $note, null);
                } else {
                    $this->updateReview($pdo, $requestId, $reviewerId);
                    $this->transition($pdo, $request, 'rechazada', 'rechazada', $reviewerId, $note, null);
                }
            }
            if ($action !== 'iniciar_revision') {
                $decisionTitle = ['aprobar' => 'Solicitud de grado aprobada', 'observar' => 'Solicitud de grado observada', 'rechazar' => 'Solicitud de grado rechazada'][$action];
                $decisionMessage = $note !== '' ? $note : 'La solicitud #' . $requestId . ' fue aprobada.';
                (new Notificacion())->add(
                    $pdo, (int) $request['id_usuario_estudiante'], 'mg_solicitud_' . $action,
                    $decisionTitle, $decisionMessage,
                    'modalidades-grado/mi-solicitud.php?id=' . $requestId,
                    'mg-request:' . $requestId . ':review:' . $action . ':' . date('YmdHis')
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

    public function habilitate(int $requestId, int $adminId, string $note): void
    {
        $note = trim($note);
        if (mb_strlen($note) > 1000) {
            throw new RuntimeException('La observación no puede superar 1.000 caracteres.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $request = $this->lockAdminRequest($pdo, $requestId);
            $this->assertState($request, ['aprobada']);
            if ((int) $request['id_habilitacion'] > 0) {
                throw new RuntimeException('La solicitud ya tiene una habilitación registrada.');
            }
            $this->assertStudentActive($pdo, (int) $request['id_estudiante']);
            $modality = $this->validateRequestModality($pdo, (int) $request['id_estudiante'], $request);
            $verification = $this->academicVerificationForDecision((int)$request['id_estudiante'], $request + $modality);
            if ($verification === null) {
                throw new RuntimeException('No se puede habilitar: la verificación académica oficial o documental ya no cumple los requisitos.');
            }
            $this->assertMinimumInterested((int)$request['id_estudiante'], $modality);
            $summary = $verification['resumen'];
            $this->saveAcademicSnapshot($pdo, $requestId, $summary, $verification['fuente'], $verification['evidencia_id']);
            $insert = $pdo->prepare('INSERT INTO mg_habilitaciones (id_solicitud, habilitado_por, observacion) VALUES (:solicitud, :usuario, :observacion)');
            $insert->execute(['solicitud' => $requestId, 'usuario' => $adminId, 'observacion' => $note !== '' ? $note : null]);
            $this->event($pdo, $requestId, 'habilitada', 'aprobada', 'aprobada', $adminId, $note !== '' ? $note : null, $summary);
            (new Notificacion())->add(
                $pdo, (int) $request['id_usuario_estudiante'], 'mg_solicitud_habilitada',
                'Solicitud habilitada', 'Tu solicitud de Modalidad de Grado ya está habilitada para continuar.',
                'modalidades-grado/mi-solicitud.php?id=' . $requestId,
                'mg-request:' . $requestId . ':habilitated'
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function academicSummary(int $studentId): array
    {
        $rows = (new MgAcademico())->studentVerification($studentId);
        if (!$rows) {
            throw new RuntimeException('No hay un plan asignado con historial académico aprobado para verificar. Contacte a Administración.');
        }
        return $rows[0];
    }

    private function academicSummaryOrNull(int $studentId): ?array
    {
        try {
            return $this->academicSummary($studentId);
        } catch (RuntimeException) {
            return null;
        }
    }

    public function academicVerificationForDecision(int $studentId, array $request): ?array
    {
        $plan = (new MgAcademico())->assignedPlan($studentId);
        $student = (new Estudiante())->findById($studentId);
        if (!$student) {
            return null;
        }
        $modality = [
            'codigo' => $request['codigo_modalidad'] ?? $request['codigo'] ?? '',
            'promedio_minimo' => $request['promedio_minimo'] ?? null,
        ];
        $official = $plan ? $this->academicSummaryOrNull($studentId) : null;
        if ($official && (int)$official['id_plan_estudio'] === (int)$plan['id_plan_estudio']
            && $this->academicSummaryMeetsPolicy($official, $modality)) {
            return ['fuente' => 'historial_oficial', 'evidencia_id' => null, 'resumen' => $official];
        }

        $requestId = (int)($request['id_solicitud'] ?? 0);
        $evidence = $requestId > 0 ? $this->latestAcademicEvidence($requestId) : null;
        if (!$evidence || $evidence['estado'] !== 'verificada'
            || (int)$evidence['id_carrera'] !== (int)$student['id_carrera']) {
            return null;
        }
        $grades = $evidence['materias'] ?? [];
        if (!$grades) {
            return null;
        }
        if ($plan) {
            if ((int)$evidence['id_plan_estudio'] === (int)$plan['id_plan_estudio']) {
                $subjects = (new MgAcademico())->requiredSubjectsForPlan((int)$plan['id_plan_estudio']);
                if (!$subjects || count($subjects) !== count($grades)) {
                    return null;
                }
                $expected = array_map(static fn(array $subject): int => (int)$subject['id_materia'], $subjects);
                $verified = array_map(static fn(array $grade): int => (int)$grade['id_materia'], $grades);
                sort($expected);
                sort($verified);
                if ($expected !== $verified) {
                    return null;
                }
            } elseif ($evidence['id_plan_estudio'] !== null
                || mb_strtolower(trim((string)$evidence['plan_referencia']))
                    !== mb_strtolower(trim($plan['codigo_plan'] . ' / ' . $plan['version_plan']))) {
                return null;
            }
        } elseif (!$evidence['plan_referencia']) {
            return null;
        }
        $approved = count(array_filter($grades, static fn(array $grade): bool => $grade['estado'] === 'APROBADA'));
        $average = array_sum(array_map(static fn(array $grade): float => (float)$grade['nota'], $grades)) / count($grades);
        $summary = [
            'id_plan_estudio' => $plan ? (int)$plan['id_plan_estudio'] : null,
            'codigo_plan' => $plan['codigo_plan'] ?? $evidence['plan_referencia'],
            'version_plan' => $plan['version_plan'] ?? null,
            'materias_requeridas' => count($grades),
            'materias_aprobadas' => $approved,
            'materias_sin_registro' => 0,
            'materias_reprobadas' => count($grades) - $approved,
            'promedio_aprobadas' => $average,
        ];
        if (!$this->academicSummaryMeetsPolicy($summary, $modality)) {
            return null;
        }
        return ['fuente' => 'documento', 'evidencia_id' => (int)$evidence['id_evidencia'], 'resumen' => $summary];
    }

    public function academicEvidenceReadyToSubmit(int $studentId, int $requestId, array $request): bool
    {
        if ($this->academicVerificationForDecision($studentId, $request) !== null) {
            return true;
        }
        $plan = (new MgAcademico())->assignedPlan($studentId);
        $evidence = $this->latestAcademicEvidence($requestId);
        $student = (new Estudiante())->findById($studentId);
        return $student !== null && $evidence !== null
            && (int)$evidence['id_carrera'] === (int)$student['id_carrera']
            && ($plan ? (int)$evidence['id_plan_estudio'] === (int)$plan['id_plan_estudio'] : $evidence['id_plan_estudio'] === null)
            && in_array($evidence['estado'], ['pendiente', 'verificada'], true);
    }

    private function academicSummaryMeetsPolicy(?array $summary, array $modality): bool
    {
        if ($summary === null) {
            return false;
        }
        $required = (int)($summary['materias_requeridas'] ?? 0);
        $passed = (int)($summary['materias_aprobadas'] ?? 0);
        if ($required < 1 || $passed !== $required) {
            return false;
        }
        if (($modality['codigo'] ?? '') === 'GRADUACION_EXCELENCIA') {
            $average = $summary['promedio_aprobadas'] ?? null;
            if ($average === null || (float)$average <= 90.0) {
                return false;
            }
            if (($modality['promedio_minimo'] ?? null) !== null && (float)$average < (float)$modality['promedio_minimo']) {
                return false;
            }
        }
        return true;
    }

    private function requireAllRequiredSubjectsPassed(?array $summary): void
    {
        $configuration = new MgConfiguracion();
        $verifySubjects = (bool) $configuration->effectiveValue('verificar_materias_aprobadas', true);
        $requireCompletePlan = (bool) $configuration->effectiveValue('exigir_plan_completo', true);
        if (!$verifySubjects && !$requireCompletePlan) {
            return;
        }
        if ($summary === null) {
            throw new RuntimeException('No se puede verificar la elegibilidad académica: falta un plan e historial aprobados.');
        }
        $required = (int) $summary['materias_requeridas'];
        $passed = (int) $summary['materias_aprobadas'];
        $allowedPending = $requireCompletePlan ? 0 : max(0, (int) $configuration->effectiveValue('materias_pendientes_permitidas', 0));
        $pending = max(0, $required - $passed);
        if ($required < 1 || $pending > $allowedPending) {
            throw new RuntimeException("No se puede enviar ni aprobar: materias obligatorias {$passed}/{$required}; pendientes o sin aprobar: {$pending}; máximo permitido: {$allowedPending}.");
        }
    }

    private function lockStudent(PDO $pdo, int $studentId, int $actorId): array
    {
        $statement = $pdo->prepare(
            'SELECT e.id_estudiante, e.id_carrera, u.estado AS estado_usuario
             FROM estudiantes e INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             WHERE e.id_estudiante = :estudiante AND u.id_usuario = :usuario FOR UPDATE'
        );
        $statement->execute(['estudiante' => $studentId, 'usuario' => $actorId]);
        $student = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$student || $student['estado_usuario'] !== 'activo') {
            throw new RuntimeException('Se requiere un perfil de estudiante activo para esta operación.');
        }
        return $student;
    }

    private function availableModality(PDO $pdo, int $studentId, int $modalityId): array
    {
        $statement = $pdo->prepare(
            'SELECT m.id_modalidad, m.codigo, m.min_interesados, m.promedio_minimo,
                    m.permite_trabajo_grupal, m.max_integrantes,
                    m.requiere_tema_preliminar, m.requiere_descripcion
             FROM estudiantes e
             INNER JOIN mg_carrera_modalidades cm ON cm.id_carrera = e.id_carrera AND cm.disponible = 1
             INNER JOIN mg_modalidades m ON m.id_modalidad = cm.id_modalidad AND m.estado = "activa"
             WHERE e.id_estudiante = :estudiante AND m.id_modalidad = :modalidad LIMIT 1'
        );
        $statement->execute(['estudiante' => $studentId, 'modalidad' => $modalityId]);
        $modality = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$modality) {
            throw new RuntimeException('La modalidad no está habilitada para la carrera del estudiante.');
        }
        return $modality;
    }

    private function validateRequestModality(PDO $pdo, int $studentId, array $request): array
    {
        $modality = $this->availableModality($pdo, $studentId, (int) $request['id_modalidad']);
        if ($request['tipo_trabajo'] === 'grupal' && (int) $modality['permite_trabajo_grupal'] !== 1) {
            throw new RuntimeException('La modalidad ya no permite intención de trabajo grupal; corrija la solicitud.');
        }
        if ((int) $modality['requiere_tema_preliminar'] === 1 && trim((string) ($request['tema_preliminar'] ?? '')) === '') {
            throw new RuntimeException('La modalidad ahora requiere un tema preliminar. Corrija la solicitud.');
        }
        if ((int) $modality['requiere_descripcion'] === 1 && trim((string) ($request['descripcion'] ?? '')) === '') {
            throw new RuntimeException('La modalidad ahora requiere una descripción. Corrija la solicitud.');
        }
        return $modality;
    }

    private function assertMinimumInterested(int $studentId, array $modality): void
    {
        if ($modality['codigo'] !== 'EXAMEN_GRADO' || $modality['min_interesados'] === null) {
            return;
        }
        $career = Database::connection()->prepare('SELECT id_carrera FROM estudiantes WHERE id_estudiante=:id');
        $career->execute(['id' => $studentId]);
        $careerId = (int)$career->fetchColumn();
        $interested = Database::connection()->prepare(
            'SELECT COUNT(DISTINCT s.id_estudiante)
             FROM mg_solicitudes s
             INNER JOIN estudiantes e ON e.id_estudiante=s.id_estudiante
             WHERE s.id_modalidad=:modalidad AND e.id_carrera=:carrera
               AND s.estado IN ("enviada","en_revision","observada","aprobada")'
        );
        $interested->execute(['modalidad' => (int)$modality['id_modalidad'], 'carrera' => $careerId]);
        $count = (int)$interested->fetchColumn();
        if ($count < (int)$modality['min_interesados']) {
            throw new RuntimeException('Examen de Grado requiere ' . (int)$modality['min_interesados'] . ' interesados de la carrera; actualmente hay ' . $count . '.');
        }
    }

    private function assertStudentActive(PDO $pdo, int $studentId): void
    {
        $statement = $pdo->prepare(
            'SELECT u.estado FROM estudiantes e INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             WHERE e.id_estudiante = :estudiante LIMIT 1'
        );
        $statement->execute(['estudiante' => $studentId]);
        if ($statement->fetchColumn() !== 'activo') {
            throw new RuntimeException('El estudiante debe mantener una cuenta activa para aprobar o habilitar la solicitud.');
        }
    }

    private function lockOwnedRequest(PDO $pdo, int $requestId, int $studentId): array
    {
        $statement = $pdo->prepare('SELECT * FROM mg_solicitudes WHERE id_solicitud = :id AND id_estudiante = :estudiante FOR UPDATE');
        $statement->execute(['id' => $requestId, 'estudiante' => $studentId]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            throw new RuntimeException('La solicitud no existe o no pertenece a este estudiante.');
        }
        return $request;
    }

    private function lockAdminRequest(PDO $pdo, int $requestId): array
    {
        $statement = $pdo->prepare(
            'SELECT s.*, e.id_estudiante, e.id_usuario AS id_usuario_estudiante,
                    m.codigo AS codigo_modalidad,m.promedio_minimo,m.min_interesados
             FROM mg_solicitudes s
             INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
             INNER JOIN mg_modalidades m ON m.id_modalidad=s.id_modalidad
             WHERE s.id_solicitud = :id FOR UPDATE'
        );
        $statement->execute(['id' => $requestId]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            throw new RuntimeException('La solicitud seleccionada no existe.');
        }
        $habilitation = $pdo->prepare('SELECT id_habilitacion FROM mg_habilitaciones WHERE id_solicitud = :id FOR UPDATE');
        $habilitation->execute(['id' => $requestId]);
        $request['id_habilitacion'] = $habilitation->fetchColumn();
        return $request;
    }

    private function assertState(array $request, array $states): void
    {
        if (!in_array($request['estado'], $states, true)) {
            throw new RuntimeException('La acción no corresponde al estado actual de la solicitud.');
        }
    }

    private function updateReview(PDO $pdo, int $requestId, int $reviewerId): void
    {
        $statement = $pdo->prepare('UPDATE mg_solicitudes SET revisado_por = :usuario, revisado_en = CURRENT_TIMESTAMP WHERE id_solicitud = :id');
        $statement->execute(['usuario' => $reviewerId, 'id' => $requestId]);
    }

    private function saveAcademicSnapshot(PDO $pdo, int $requestId, array $summary, string $source, ?int $evidenceId): void
    {
        $statement = $pdo->prepare(
            'UPDATE mg_solicitudes SET id_plan_verificado = :plan,
                materias_requeridas_snapshot = :requeridas, materias_aprobadas_snapshot = :aprobadas,
                promedio_snapshot = :promedio, verificado_en = CURRENT_TIMESTAMP,
                fuente_verificacion_academica=:fuente,id_evidencia_verificada=:evidencia
             WHERE id_solicitud = :id'
        );
        $statement->execute([
            'plan' => $summary['id_plan_estudio'] ?? null, 'requeridas' => $summary['materias_requeridas'] ?? null,
            'aprobadas' => $summary['materias_aprobadas'] ?? null, 'promedio' => $summary['promedio_aprobadas'] ?? null,
            'fuente' => $source, 'evidencia' => $evidenceId, 'id' => $requestId,
        ]);
    }

    private function transition(PDO $pdo, array $request, string $newState, string $action, int $actorId, ?string $note, ?array $summary, bool $visibleToStudent = true): void
    {
        $update = $pdo->prepare('UPDATE mg_solicitudes SET estado = :estado WHERE id_solicitud = :id');
        $update->execute(['estado' => $newState, 'id' => (int) $request['id_solicitud']]);
        $this->event($pdo, (int) $request['id_solicitud'], $action, $request['estado'], $newState, $actorId, $note, $summary, $visibleToStudent);
    }

    private function event(PDO $pdo, int $requestId, string $action, ?string $oldState, string $newState, int $actorId, ?string $note, ?array $summary, bool $visibleToStudent = true): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO mg_solicitud_historial
                (id_solicitud, accion, estado_anterior, estado_nuevo, id_actor, comentario, resumen_academico, visible_estudiante)
             VALUES (:solicitud, :accion, :anterior, :nuevo, :actor, :comentario, :resumen, :visible)'
        );
        $statement->execute([
            'solicitud' => $requestId, 'accion' => $action, 'anterior' => $oldState, 'nuevo' => $newState,
            'actor' => $actorId, 'comentario' => $note,
            'resumen' => $summary !== null ? json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null,
            'visible' => $visibleToStudent ? 1 : 0,
        ]);
    }

    private function history(int $requestId, bool $studentView = false): array
    {
        $statement = Database::connection()->prepare(
            'SELECT h.accion, h.estado_anterior, h.estado_nuevo, h.comentario, h.resumen_academico, h.visible_estudiante, h.ocurrido_en,
                    CONCAT(u.nombre, " ", u.apellido) AS actor
             FROM mg_solicitud_historial h INNER JOIN usuarios u ON u.id_usuario = h.id_actor
             WHERE h.id_solicitud = :id AND (:solo_publico = 0 OR h.visible_estudiante = 1)
             ORDER BY h.ocurrido_en, h.id_evento'
        );
        $statement->execute(['id' => $requestId, 'solo_publico' => $studentView ? 1 : 0]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
