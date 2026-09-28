<?php

declare(strict_types=1);

final class MgAcademico
{
    private const MAX_FILE_BYTES = 5_000_000;
    private const MAX_ROWS = 10_000;

    public function importCsv(array $file, int $userId, string $kind): int
    {
        if (!in_array($kind, ['plan_estudio', 'historial'], true)) {
            throw new RuntimeException('Seleccione un tipo de importación válido.');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            throw new RuntimeException('Seleccione un archivo CSV válido.');
        }
        if (($file['size'] ?? 0) < 1 || $file['size'] > self::MAX_FILE_BYTES) {
            throw new RuntimeException('El archivo debe tener contenido y no superar 5 MB.');
        }

        $originalName = basename((string) ($file['name'] ?? 'datos.csv'));
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'csv') {
            throw new RuntimeException('El archivo debe tener extensión .csv.');
        }
        $hash = hash_file('sha256', (string) $file['tmp_name']);
        if ($hash === false) {
            throw new RuntimeException('No se pudo leer el archivo cargado.');
        }
        $this->assertHashNotImported($kind, $hash);

        $parsed = $this->parseCsv((string) $file['tmp_name'], $kind);
        $relativePath = 'storage/mg-academic-imports/' . $hash . '.csv';
        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el almacenamiento privado MG.');
        }
        if (!move_uploaded_file((string) $file['tmp_name'], $absolutePath)) {
            throw new RuntimeException('No se pudo guardar el archivo importado.');
        }

        try {
            $pdo = Database::connection();
            $pdo->beginTransaction();
            $insert = $pdo->prepare(
                'INSERT INTO mg_importaciones_academicas
                    (tipo, nombre_archivo, hash_archivo, ruta_archivo, total_filas, filas_validas, filas_con_error, importado_por)
                 VALUES (:tipo, :nombre, :hash, :ruta, :total, :validas, :errores, :usuario)'
            );
            $valid = count(array_filter($parsed['rows'], static fn (array $row): bool => $row['error'] === null));
            $insert->execute([
                'tipo' => $kind,
                'nombre' => mb_substr($originalName, 0, 255),
                'hash' => $hash,
                'ruta' => $relativePath,
                'total' => count($parsed['rows']),
                'validas' => $valid,
                'errores' => count($parsed['rows']) - $valid,
                'usuario' => $userId,
            ]);
            $importId = (int) $pdo->lastInsertId();
            $rowInsert = $pdo->prepare(
                'INSERT INTO mg_importacion_academica_filas (id_importacion, numero_fila, datos, error_validacion)
                 VALUES (:importacion, :numero, :datos, :error)'
            );
            foreach ($parsed['rows'] as $row) {
                $rowInsert->execute([
                    'importacion' => $importId,
                    'numero' => $row['number'],
                    'datos' => json_encode($row['data'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'error' => $row['error'],
                ]);
            }
            $pdo->commit();
            return $importId;
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
            if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
                throw new RuntimeException('Este archivo ya fue importado anteriormente.');
            }
            throw $exception;
        }
    }

    private function assertHashNotImported(string $kind, string $hash): void
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM mg_importaciones_academicas WHERE tipo = :tipo AND hash_archivo = :hash LIMIT 1'
        );
        $statement->execute(['tipo' => $kind, 'hash' => $hash]);
        if ($statement->fetchColumn()) {
            throw new RuntimeException('Este archivo ya fue importado anteriormente.');
        }
    }

    private function parseCsv(string $path, string $kind): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el CSV.');
        }
        try {
            $header = fgetcsv($handle, 0, ',', '"', '');
            if (!is_array($header)) {
                throw new RuntimeException('El CSV está vacío.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $header = array_map(static fn ($value): string => trim((string) $value), $header);
            $expected = $kind === 'plan_estudio'
                ? ['id_carrera', 'codigo_plan', 'version_plan', 'id_materia', 'obligatoria']
                : ['registro_universitario', 'codigo_plan', 'version_plan', 'id_materia', 'estado', 'nota', 'periodo'];
            if ($header !== $expected) {
                throw new RuntimeException('Encabezados incorrectos. Descargue y use la plantilla CSV de este tipo.');
            }

            $rows = [];
            $seen = [];
            $line = 1;
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $line++;
                if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                    continue;
                }
                if (count($rows) >= self::MAX_ROWS) {
                    throw new RuntimeException('El CSV supera el máximo de 10.000 filas.');
                }
                $values = array_map(static fn ($value): string => trim((string) $value), $values);
                foreach ($values as $value) {
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        throw new RuntimeException('El CSV debe estar codificado en UTF-8.');
                    }
                }
                $data = array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), ''));
                $error = count($values) === count($header) ? null : 'La fila no tiene la cantidad de columnas esperada.';
                if ($error === null) {
                    $error = $kind === 'plan_estudio'
                        ? $this->validatePlanRow($data, $seen)
                        : $this->validateHistoryRow($data, $seen);
                }
                $rows[] = ['number' => $line, 'data' => $data, 'error' => $error];
            }
            if (!$rows) {
                throw new RuntimeException('El CSV debe contener al menos una fila de datos.');
            }
            return ['rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    private function validatePlanRow(array &$row, array &$seen): ?string
    {
        if (!$this->positiveInt($row['id_carrera']) || !$this->positiveInt($row['id_materia'])) {
            return 'id_carrera e id_materia deben ser identificadores enteros positivos del catálogo.';
        }
        if (!$this->validPlanKey($row['codigo_plan'], $row['version_plan'])) {
            return 'codigo_plan y version_plan son obligatorios (máximo 60 y 40 caracteres).';
        }
        if (!in_array($row['obligatoria'], ['0', '1'], true)) {
            return 'obligatoria debe ser 1 (sí) o 0 (no).';
        }
        $pdo = Database::connection();
        $career = $pdo->prepare('SELECT 1 FROM carreras WHERE id_carrera = :id');
        $career->execute(['id' => (int) $row['id_carrera']]);
        if (!$career->fetchColumn()) {
            return 'La carrera indicada no existe.';
        }
        $subject = $pdo->prepare('SELECT id_carrera FROM materias WHERE id_materia = :id');
        $subject->execute(['id' => (int) $row['id_materia']]);
        $subjectCareer = $subject->fetchColumn();
        if ($subjectCareer === false || ($subjectCareer !== null && (int) $subjectCareer !== (int) $row['id_carrera'])) {
            return 'La materia no existe o no pertenece a la carrera indicada.';
        }
        $key = implode(':', [$row['id_carrera'], mb_strtolower($row['codigo_plan']), mb_strtolower($row['version_plan']), $row['id_materia']]);
        if (isset($seen[$key])) {
            return 'La materia está duplicada dentro del mismo plan y versión.';
        }
        $seen[$key] = true;
        $row['id_carrera'] = (int) $row['id_carrera'];
        $row['id_materia'] = (int) $row['id_materia'];
        $row['obligatoria'] = (int) $row['obligatoria'];
        return null;
    }

    private function validateHistoryRow(array &$row, array &$seen): ?string
    {
        foreach (['registro_universitario', 'codigo_plan', 'version_plan', 'periodo'] as $field) {
            if ($row[$field] === '' || mb_strlen($row[$field]) > ($field === 'registro_universitario' ? 30 : 60)) {
                return 'RU, código/versión del plan y periodo son obligatorios y deben tener longitud válida.';
            }
        }
        if (!$this->positiveInt($row['id_materia'])) {
            return 'id_materia debe ser un identificador entero positivo del catálogo.';
        }
        $row['estado'] = strtoupper($row['estado']);
        if (!in_array($row['estado'], ['APROBADA', 'REPROBADA'], true)) {
            return 'estado debe ser APROBADA o REPROBADA.';
        }
        if (!is_numeric($row['nota']) || (float) $row['nota'] < 0 || (float) $row['nota'] > 100) {
            return 'nota debe ser un número entre 0 y 100.';
        }
        $pdo = Database::connection();
        $student = $pdo->prepare(
            'SELECT e.id_estudiante, e.id_carrera FROM estudiantes e WHERE e.registro_universitario = :ru LIMIT 1'
        );
        $student->execute(['ru' => $row['registro_universitario']]);
        $studentData = $student->fetch(PDO::FETCH_ASSOC);
        if (!$studentData) {
            return 'El RU no corresponde a un estudiante registrado.';
        }
        $plan = $pdo->prepare(
            'SELECT p.id_plan_estudio FROM mg_planes_estudio p
             INNER JOIN mg_plan_materias pm ON pm.id_plan_estudio = p.id_plan_estudio
             WHERE p.id_carrera = :carrera AND p.codigo_plan = :codigo AND p.version_plan = :version
               AND pm.id_materia = :materia LIMIT 1'
        );
        $plan->execute([
            'carrera' => (int) $studentData['id_carrera'], 'codigo' => $row['codigo_plan'],
            'version' => $row['version_plan'], 'materia' => (int) $row['id_materia'],
        ]);
        $planId = $plan->fetchColumn();
        if (!$planId) {
            return 'No hay un plan aprobado de esa carrera, versión y materia.';
        }
        $key = implode(':', [$studentData['id_estudiante'], $planId, $row['id_materia'], mb_strtolower($row['periodo'])]);
        if (isset($seen[$key])) {
            return 'El estudiante tiene esa materia duplicada en el mismo periodo.';
        }
        $seen[$key] = true;
        $row['id_estudiante'] = (int) $studentData['id_estudiante'];
        $row['id_plan_estudio'] = (int) $planId;
        $row['id_materia'] = (int) $row['id_materia'];
        $row['nota'] = number_format((float) $row['nota'], 2, '.', '');
        return null;
    }

    private function validPlanKey(string $code, string $version): bool
    {
        return $code !== '' && mb_strlen($code) <= 60 && $version !== '' && mb_strlen($version) <= 40;
    }

    private function positiveInt(string $value): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $value) === 1;
    }

    public function imports(): array
    {
        return Database::connection()->query(
            'SELECT i.*, CONCAT(ui.nombre, " ", ui.apellido) AS importador,
                    CONCAT(ur.nombre, " ", ur.apellido) AS revisor
             FROM mg_importaciones_academicas i
             INNER JOIN usuarios ui ON ui.id_usuario = i.importado_por
             LEFT JOIN usuarios ur ON ur.id_usuario = i.revisado_por
             ORDER BY i.fecha_importacion DESC, i.id_importacion DESC LIMIT 100'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function importRows(int $importId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT numero_fila, datos, error_validacion FROM mg_importacion_academica_filas WHERE id_importacion = :id ORDER BY numero_fila LIMIT 100'
        );
        $statement->execute(['id' => $importId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function review(int $importId, int $reviewerId, string $decision, string $note): void
    {
        if (!in_array($decision, ['aprobada', 'rechazada'], true)) {
            throw new RuntimeException('Seleccione una decisión válida.');
        }
        $note = trim($note);
        if ($decision === 'rechazada' && $note === '') {
            throw new RuntimeException('Indique el motivo del rechazo.');
        }
        if (mb_strlen($note) > 1000) {
            throw new RuntimeException('La observación no puede superar 1.000 caracteres.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('SELECT * FROM mg_importaciones_academicas WHERE id_importacion = :id FOR UPDATE');
            $statement->execute(['id' => $importId]);
            $batch = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$batch || $batch['estado'] !== 'pendiente') {
                throw new RuntimeException('La importación no existe o ya fue revisada.');
            }
            if ($decision === 'aprobada') {
                if ((int) $batch['filas_con_error'] > 0 || (int) $batch['filas_validas'] === 0) {
                    throw new RuntimeException('Corrija las filas con errores y vuelva a importar antes de aprobar.');
                }
                if ($batch['tipo'] === 'plan_estudio') {
                    $this->applyPlanImport($pdo, $importId, $reviewerId);
                } else {
                    $this->applyHistoryImport($pdo, $importId);
                }
            }
            $update = $pdo->prepare(
                'UPDATE mg_importaciones_academicas
                 SET estado = :estado, revisado_por = :revisor, fecha_revision = CURRENT_TIMESTAMP,
                     observacion_revision = :nota WHERE id_importacion = :id AND estado = "pendiente"'
            );
            $update->execute(['estado' => $decision, 'revisor' => $reviewerId, 'nota' => $note !== '' ? $note : null, 'id' => $importId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function applyPlanImport(PDO $pdo, int $importId, int $reviewerId): void
    {
        $rows = $pdo->prepare('SELECT datos FROM mg_importacion_academica_filas WHERE id_importacion = :id ORDER BY numero_fila');
        $rows->execute(['id' => $importId]);
        $plans = [];
        foreach ($rows->fetchAll(PDO::FETCH_COLUMN) as $json) {
            $row = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $key = implode(':', [$row['id_carrera'], $row['codigo_plan'], $row['version_plan']]);
            $plans[$key]['career'] = $row['id_carrera'];
            $plans[$key]['code'] = $row['codigo_plan'];
            $plans[$key]['version'] = $row['version_plan'];
            $plans[$key]['subjects'][] = $row;
        }
        foreach ($plans as $plan) {
            $insert = $pdo->prepare(
                'INSERT INTO mg_planes_estudio (id_carrera, codigo_plan, version_plan, id_importacion, aprobado_por)
                 VALUES (:carrera, :codigo, :version, :importacion, :aprobador)'
            );
            $insert->execute([
                'carrera' => $plan['career'], 'codigo' => $plan['code'], 'version' => $plan['version'],
                'importacion' => $importId, 'aprobador' => $reviewerId,
            ]);
            $planId = (int) $pdo->lastInsertId();
            $subject = $pdo->prepare('INSERT INTO mg_plan_materias (id_plan_estudio, id_materia, obligatoria) VALUES (:plan, :materia, :obligatoria)');
            foreach ($plan['subjects'] as $row) {
                $subject->execute(['plan' => $planId, 'materia' => $row['id_materia'], 'obligatoria' => $row['obligatoria']]);
            }
        }
    }

    private function applyHistoryImport(PDO $pdo, int $importId): void
    {
        $rows = $pdo->prepare('SELECT datos FROM mg_importacion_academica_filas WHERE id_importacion = :id ORDER BY numero_fila');
        $rows->execute(['id' => $importId]);
        $insert = $pdo->prepare(
            'INSERT INTO mg_historial_academico
                (id_estudiante, id_plan_estudio, id_materia, estado, nota, periodo, id_importacion)
             VALUES (:estudiante, :plan, :materia, :estado, :nota, :periodo, :importacion)
             ON DUPLICATE KEY UPDATE estado = VALUES(estado), nota = VALUES(nota), id_importacion = VALUES(id_importacion), actualizado_en = CURRENT_TIMESTAMP'
        );
        foreach ($rows->fetchAll(PDO::FETCH_COLUMN) as $json) {
            $row = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $insert->execute([
                'estudiante' => $row['id_estudiante'], 'plan' => $row['id_plan_estudio'], 'materia' => $row['id_materia'],
                'estado' => $row['estado'], 'nota' => $row['nota'], 'periodo' => $row['periodo'], 'importacion' => $importId,
            ]);
        }
    }

    public function studentVerification(int $studentId): array
    {
        $pdo = Database::connection();
        $plans = $pdo->prepare(
            'SELECT p.id_plan_estudio, p.codigo_plan, p.version_plan, c.nombre_carrera,
                    COUNT(CASE WHEN pm.obligatoria = 1 THEN 1 END) AS materias_requeridas,
                    COUNT(CASE WHEN pm.obligatoria = 1 AND h.aprobada = 1 THEN 1 END) AS materias_aprobadas,
                    COUNT(CASE WHEN pm.obligatoria = 1 AND h.registrada IS NULL THEN 1 END) AS materias_sin_registro,
                    COUNT(CASE WHEN pm.obligatoria = 1 AND h.registrada = 1 AND h.aprobada = 0 THEN 1 END) AS materias_reprobadas,
                    AVG(CASE WHEN pm.obligatoria = 1 AND h.aprobada = 1 THEN h.mejor_nota_aprobada END) AS promedio_aprobadas
             FROM mg_planes_estudio p
             INNER JOIN carreras c ON c.id_carrera = p.id_carrera
             INNER JOIN estudiantes e ON e.id_estudiante = :estudiante AND e.id_carrera = p.id_carrera
             INNER JOIN mg_estudiante_plan ep ON ep.id_estudiante = e.id_estudiante AND ep.id_plan_estudio = p.id_plan_estudio
             INNER JOIN mg_plan_materias pm ON pm.id_plan_estudio = p.id_plan_estudio
             LEFT JOIN (
                 SELECT id_estudiante, id_plan_estudio, id_materia,
                        MAX(estado = "APROBADA") AS aprobada,
                        1 AS registrada,
                        MAX(CASE WHEN estado = "APROBADA" THEN nota END) AS mejor_nota_aprobada
                 FROM mg_historial_academico
                 GROUP BY id_estudiante, id_plan_estudio, id_materia
             ) h ON h.id_estudiante = e.id_estudiante AND h.id_plan_estudio = p.id_plan_estudio AND h.id_materia = pm.id_materia
             WHERE EXISTS (
                 SELECT 1 FROM mg_historial_academico hx
                 WHERE hx.id_estudiante = e.id_estudiante AND hx.id_plan_estudio = p.id_plan_estudio
             )
             GROUP BY p.id_plan_estudio, p.codigo_plan, p.version_plan, c.nombre_carrera
             ORDER BY p.aprobado_en DESC, p.id_plan_estudio DESC'
        );
        $plans->execute(['estudiante' => $studentId]);
        return $plans->fetchAll(PDO::FETCH_ASSOC);
    }

    public function studentPendingSubjects(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id_materia, m.nombre_materia,
                    COALESCE(h.aprobada, 0) AS aprobada,
                    h.registrada
             FROM mg_estudiante_plan ep
             INNER JOIN mg_planes_estudio p ON p.id_plan_estudio = ep.id_plan_estudio
             INNER JOIN mg_plan_materias pm ON pm.id_plan_estudio = p.id_plan_estudio AND pm.obligatoria = 1
             INNER JOIN materias m ON m.id_materia = pm.id_materia
             LEFT JOIN (
                 SELECT id_estudiante, id_plan_estudio, id_materia,
                        MAX(estado = "APROBADA") AS aprobada, 1 AS registrada
                 FROM mg_historial_academico WHERE id_estudiante = :estudiante
                 GROUP BY id_estudiante, id_plan_estudio, id_materia
             ) h ON h.id_estudiante = ep.id_estudiante AND h.id_plan_estudio = p.id_plan_estudio AND h.id_materia = pm.id_materia
             WHERE ep.id_estudiante = :estudiante_plan AND COALESCE(h.aprobada, 0) = 0
             ORDER BY m.nombre_materia'
        );
        $statement->execute(['estudiante' => $studentId, 'estudiante_plan' => $studentId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function administrativeSummary(): array
    {
        $pdo = Database::connection();
        return [
            'importaciones_pendientes' => (int) $pdo->query("SELECT COUNT(*) FROM mg_importaciones_academicas WHERE estado = 'pendiente'")->fetchColumn(),
            'planes_aprobados' => (int) $pdo->query('SELECT COUNT(*) FROM mg_planes_estudio')->fetchColumn(),
            'estudiantes' => (int) $pdo->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn(),
            'estudiantes_con_plan' => (int) $pdo->query('SELECT COUNT(*) FROM mg_estudiante_plan')->fetchColumn(),
            'solicitudes_por_revisar' => (int) $pdo->query("SELECT COUNT(*) FROM mg_solicitudes WHERE estado IN ('enviada', 'en_revision')")->fetchColumn(),
            'solicitudes_observadas' => (int) $pdo->query("SELECT COUNT(*) FROM mg_solicitudes WHERE estado = 'observada'")->fetchColumn(),
            'pendientes_habilitar' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_solicitudes s LEFT JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
                 WHERE s.estado = 'aprobada' AND h.id_habilitacion IS NULL"
            )->fetchColumn(),
            'pendientes_inscripcion' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_solicitudes s
                 INNER JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
                 LEFT JOIN mg_inscripciones i ON i.id_solicitud = s.id_solicitud
                 WHERE s.estado = 'aprobada' AND i.id_inscripcion IS NULL"
            )->fetchColumn(),
            'inscripciones_activas' => (int) $pdo->query("SELECT COUNT(*) FROM mg_inscripciones WHERE estado = 'activa'")->fetchColumn(),
            'trabajos_activos' => (int) $pdo->query("SELECT COUNT(*) FROM mg_trabajos WHERE estado = 'activo'")->fetchColumn(),
            'trabajos_sin_tutor' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_trabajos w
                 INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad AND m.requiere_tutor = 1
                 LEFT JOIN mg_asignaciones_tutor a ON a.id_trabajo = w.id_trabajo AND a.estado = 'activa'
                 WHERE w.estado = 'activo' AND a.id_asignacion IS NULL"
            )->fetchColumn(),
            'hitos_pendientes' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_seguimiento_hitos sh
                 INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado='activo'
                 INNER JOIN mg_trabajos w ON w.id_trabajo=sh.id_trabajo AND w.estado='activo'
                 WHERE sh.estado='pendiente'"
            )->fetchColumn(),
            'hitos_vencidos' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_seguimiento_hitos sh
                 INNER JOIN mg_calendario h ON h.id_hito=sh.id_hito AND h.estado='activo'
                 INNER JOIN mg_trabajos w ON w.id_trabajo=sh.id_trabajo AND w.estado='activo'
                 WHERE sh.estado='pendiente' AND sh.fecha_limite<CURRENT_DATE"
            )->fetchColumn(),
            'defensas_programadas' => (int) $pdo->query("SELECT COUNT(*) FROM mg_defensas WHERE estado='programada' AND fecha_hora>=CURRENT_TIMESTAMP")->fetchColumn(),
            'trabajos_pendientes_cierre' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_trabajos w LEFT JOIN mg_cierres c ON c.id_trabajo=w.id_trabajo
                 WHERE w.estado='activo' AND c.id_cierre IS NULL"
            )->fetchColumn(),
            'cierres_aprobados' => (int) $pdo->query("SELECT COUNT(*) FROM mg_cierres WHERE resultado='aprobado'")->fetchColumn(),
            'ofertas_carrera' => (int) $pdo->query(
                "SELECT COUNT(*) FROM mg_carrera_modalidades cm
                 INNER JOIN mg_modalidades m ON m.id_modalidad = cm.id_modalidad AND m.estado = 'activa'
                 WHERE cm.disponible = 1"
            )->fetchColumn(),
        ];
    }

    public function approvedPlans(): array
    {
        return Database::connection()->query(
            'SELECT p.id_plan_estudio, p.id_carrera, c.nombre_carrera, p.codigo_plan, p.version_plan,
                    COUNT(pm.id_materia) AS materias
             FROM mg_planes_estudio p
             INNER JOIN carreras c ON c.id_carrera = p.id_carrera
             LEFT JOIN mg_plan_materias pm ON pm.id_plan_estudio = p.id_plan_estudio
             GROUP BY p.id_plan_estudio, p.id_carrera, c.nombre_carrera, p.codigo_plan, p.version_plan
             ORDER BY c.nombre_carrera, p.codigo_plan, p.version_plan'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function studentsForPlanAssignment(string $search = ''): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT e.id_estudiante, e.registro_universitario, e.id_carrera,
                       CONCAT(u.nombre, " ", u.apellido) AS estudiante, c.nombre_carrera,
                       ep.id_plan_estudio, p.codigo_plan, p.version_plan, ep.asignado_en,
                       CONCAT(ua.nombre, " ", ua.apellido) AS asignado_por
                FROM estudiantes e
                INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
                INNER JOIN carreras c ON c.id_carrera = e.id_carrera
                LEFT JOIN mg_estudiante_plan ep ON ep.id_estudiante = e.id_estudiante
                LEFT JOIN mg_planes_estudio p ON p.id_plan_estudio = ep.id_plan_estudio
                LEFT JOIN usuarios ua ON ua.id_usuario = ep.asignado_por';
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE e.registro_universitario LIKE :ru OR u.nombre LIKE :nombre OR u.apellido LIKE :apellido OR c.nombre_carrera LIKE :carrera';
            $like = '%' . $search . '%';
            $params = ['ru' => $like, 'nombre' => $like, 'apellido' => $like, 'carrera' => $like];
        }
        $sql .= ' ORDER BY c.nombre_carrera, u.apellido, u.nombre LIMIT 500';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignPlanToStudent(int $studentId, int $planId, int $userId, string $note): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $studentQuery = $pdo->prepare('SELECT id_carrera FROM estudiantes WHERE id_estudiante = :id FOR UPDATE');
            $studentQuery->execute(['id' => $studentId]);
            $careerId = $studentQuery->fetchColumn();
            if ($careerId === false) {
                throw new RuntimeException('El estudiante seleccionado no existe.');
            }
            $planQuery = $pdo->prepare('SELECT id_carrera FROM mg_planes_estudio WHERE id_plan_estudio = :id FOR UPDATE');
            $planQuery->execute(['id' => $planId]);
            $planCareerId = $planQuery->fetchColumn();
            if ($planCareerId === false || (int) $planCareerId !== (int) $careerId) {
                throw new RuntimeException('El plan aprobado debe pertenecer a la carrera del estudiante.');
            }
            $currentQuery = $pdo->prepare('SELECT id_plan_estudio FROM mg_estudiante_plan WHERE id_estudiante = :id FOR UPDATE');
            $currentQuery->execute(['id' => $studentId]);
            $currentPlan = $currentQuery->fetchColumn();
            if ($currentPlan !== false && (int) $currentPlan === $planId) {
                throw new RuntimeException('Ese plan ya está asignado al estudiante.');
            }

            $history = $pdo->prepare(
                'INSERT INTO mg_estudiante_plan_historial
                    (id_estudiante, id_plan_anterior, id_plan_nuevo, asignado_por, observacion)
                 VALUES (:estudiante, :anterior, :nuevo, :usuario, :observacion)'
            );
            $history->execute([
                'estudiante' => $studentId,
                'anterior' => $currentPlan !== false ? (int) $currentPlan : null,
                'nuevo' => $planId,
                'usuario' => $userId,
                'observacion' => $note !== '' ? $note : null,
            ]);
            $save = $pdo->prepare(
                'INSERT INTO mg_estudiante_plan (id_estudiante, id_plan_estudio, asignado_por)
                 VALUES (:estudiante, :plan, :usuario)
                 ON DUPLICATE KEY UPDATE id_plan_estudio = VALUES(id_plan_estudio),
                     asignado_por = VALUES(asignado_por), asignado_en = CURRENT_TIMESTAMP'
            );
            $save->execute(['estudiante' => $studentId, 'plan' => $planId, 'usuario' => $userId]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function planAssignmentHistory(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT h.asignado_en, h.observacion,
                    CONCAT(ua.nombre, " ", ua.apellido) AS asignado_por,
                    pa.codigo_plan AS codigo_anterior, pa.version_plan AS version_anterior,
                    pn.codigo_plan AS codigo_nuevo, pn.version_plan AS version_nueva
             FROM mg_estudiante_plan_historial h
             INNER JOIN usuarios ua ON ua.id_usuario = h.asignado_por
             LEFT JOIN mg_planes_estudio pa ON pa.id_plan_estudio = h.id_plan_anterior
             INNER JOIN mg_planes_estudio pn ON pn.id_plan_estudio = h.id_plan_nuevo
             WHERE h.id_estudiante = :estudiante ORDER BY h.asignado_en DESC, h.id_asignacion DESC'
        );
        $statement->execute(['estudiante' => $studentId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function careerModalities(): array
    {
        return Database::connection()->query(
            'SELECT c.id_carrera, c.nombre_carrera, m.id_modalidad, m.nombre AS modalidad,
                    m.estado AS estado_modalidad, COALESCE(cm.disponible, 0) AS disponible,
                    cm.actualizado_en, CONCAT(u.nombre, " ", u.apellido) AS actualizado_por
             FROM carreras c CROSS JOIN mg_modalidades m
             LEFT JOIN mg_carrera_modalidades cm ON cm.id_carrera = c.id_carrera AND cm.id_modalidad = m.id_modalidad
             LEFT JOIN usuarios u ON u.id_usuario = cm.actualizado_por
             ORDER BY c.nombre_carrera, m.nombre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setCareerModality(int $careerId, int $modalityId, bool $available, int $userId): void
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'SELECT c.id_carrera, m.estado FROM carreras c CROSS JOIN mg_modalidades m
             WHERE c.id_carrera = :carrera AND m.id_modalidad = :modalidad'
        );
        $statement->execute(['carrera' => $careerId, 'modalidad' => $modalityId]);
        $pair = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$pair) {
            throw new RuntimeException('La carrera o modalidad seleccionada no existe.');
        }
        if ($available && $pair['estado'] !== 'activa') {
            throw new RuntimeException('Active la modalidad antes de ofrecerla a una carrera.');
        }
        $save = $pdo->prepare(
            'INSERT INTO mg_carrera_modalidades (id_carrera, id_modalidad, disponible, actualizado_por)
             VALUES (:carrera, :modalidad, :disponible, :usuario)
             ON DUPLICATE KEY UPDATE disponible = VALUES(disponible), actualizado_por = VALUES(actualizado_por), actualizado_en = CURRENT_TIMESTAMP'
        );
        $save->execute([
            'carrera' => $careerId, 'modalidad' => $modalityId,
            'disponible' => $available ? 1 : 0, 'usuario' => $userId,
        ]);
    }

    public function template(string $kind): never
    {
        if ($kind === 'plan_estudio') {
            $name = 'plantilla-plan-estudio.csv';
            $rows = [
                ['id_carrera', 'codigo_plan', 'version_plan', 'id_materia', 'obligatoria'],
                ['1', 'PLAN-2026', '2026', '1', '1'],
            ];
        } elseif ($kind === 'historial') {
            $name = 'plantilla-historial-academico.csv';
            $rows = [
                ['registro_universitario', 'codigo_plan', 'version_plan', 'id_materia', 'estado', 'nota', 'periodo'],
                ['RU000001', 'PLAN-2026', '2026', '1', 'APROBADA', '85', '2025-2'],
            ];
        } else {
            http_response_code(400);
            exit('Tipo de plantilla no válido.');
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($output, $row, ',', '"', '');
        }
        fclose($output);
        exit;
    }
}
