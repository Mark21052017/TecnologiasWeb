<?php

declare(strict_types=1);

final class MgInscripcionesGrado
{
    public function eligibleRequests(): array
    {
        return Database::connection()->query(
            'SELECT s.id_solicitud, s.id_estudiante, s.id_modalidad, s.tipo_trabajo, s.tema_preliminar,
                    s.descripcion, s.revisado_en AS aprobada_en, s.promedio_snapshot,
                    e.id_carrera, e.registro_universitario, CONCAT(u.nombre, " ", u.apellido) AS estudiante,
                    u.estado AS estado_usuario, c.nombre_carrera, m.nombre AS modalidad,
                    m.codigo AS codigo_modalidad, m.permite_trabajo_grupal, m.max_integrantes,
                    h.habilitado_en
             FROM mg_solicitudes s
             INNER JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
             INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             INNER JOIN carreras c ON c.id_carrera = e.id_carrera
             INNER JOIN mg_modalidades m ON m.id_modalidad = s.id_modalidad
             LEFT JOIN mg_inscripciones i ON i.id_solicitud = s.id_solicitud
             WHERE s.estado = "aprobada" AND i.id_inscripcion IS NULL
             ORDER BY h.habilitado_en, s.id_solicitud LIMIT 500'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function activeCohorts(): array
    {
        return Database::connection()->query(
            'SELECT id_cohorte, codigo, nombre, fecha_inicio, fecha_fin
             FROM mg_cohortes WHERE activa = 1 ORDER BY fecha_inicio DESC, codigo'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function openGroups(): array
    {
        return Database::connection()->query(
            'SELECT w.id_trabajo, w.codigo, w.id_cohorte, w.id_modalidad, w.tema,
                    c.codigo AS codigo_cohorte, m.nombre AS modalidad, m.max_integrantes,
                    COUNT(ti.id_integrante) AS integrantes, MIN(e.id_carrera) AS id_carrera,
                    COUNT(DISTINCT e.id_carrera) AS carreras_distintas
             FROM mg_trabajos w
             INNER JOIN mg_cohortes c ON c.id_cohorte = w.id_cohorte AND c.activa = 1
             INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo = w.id_trabajo AND ti.estado = "activo"
             LEFT JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion
             LEFT JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
             WHERE w.estado = "activo" AND w.tipo_trabajo = "grupal"
               AND m.estado = "activa" AND m.permite_trabajo_grupal = 1 AND m.max_integrantes IS NOT NULL
             GROUP BY w.id_trabajo, w.codigo, w.id_cohorte, w.id_modalidad, w.tema,
                       c.codigo, c.fecha_inicio, m.nombre, m.max_integrantes
             HAVING COUNT(ti.id_integrante) < m.max_integrantes
                AND COUNT(DISTINCT e.id_carrera) = 1
             ORDER BY c.fecha_inicio DESC, w.codigo'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function enroll(array $input, int $adminId): int
    {
        $requestId = filter_var($input['id_solicitud'] ?? null, FILTER_VALIDATE_INT);
        $cohortId = filter_var($input['id_cohorte'] ?? null, FILTER_VALIDATE_INT);
        $existingWorkId = filter_var($input['id_trabajo_existente'] ?? null, FILTER_VALIDATE_INT);
        $requestId = $requestId !== false && $requestId > 0 ? (int) $requestId : 0;
        $cohortId = $cohortId !== false && $cohortId > 0 ? (int) $cohortId : 0;
        $existingWorkId = $existingWorkId !== false && $existingWorkId > 0 ? (int) $existingWorkId : null;
        $assignment = (string) ($input['tipo_asignacion'] ?? '');
        $note = trim((string) ($input['observacion'] ?? ''));
        if ($requestId < 1 || $cohortId < 1 || !in_array($assignment, ['individual', 'nuevo_grupo', 'grupo_existente'], true)) {
            throw new RuntimeException('Seleccione solicitud, cohorte y tipo de trabajo válidos.');
        }
        if (($assignment === 'grupo_existente') !== ($existingWorkId !== null)) {
            throw new RuntimeException('Seleccione un grupo existente solo cuando esa sea la asignación elegida.');
        }
        if (mb_strlen($note) > 1000) {
            throw new RuntimeException('La observación no puede superar 1.000 caracteres.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $requestQuery = $pdo->prepare(
                'SELECT s.*, e.id_carrera, u.estado AS estado_usuario, m.codigo AS codigo_modalidad,
                        m.nombre AS modalidad, m.permite_trabajo_grupal, m.max_integrantes,
                        m.requiere_tema_preliminar, m.requiere_descripcion, h.id_habilitacion
                 FROM mg_solicitudes s
                 INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
                 INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
                 INNER JOIN mg_modalidades m ON m.id_modalidad = s.id_modalidad
                 LEFT JOIN mg_habilitaciones h ON h.id_solicitud = s.id_solicitud
                 WHERE s.id_solicitud = :id FOR UPDATE'
            );
            $requestQuery->execute(['id' => $requestId]);
            $request = $requestQuery->fetch(PDO::FETCH_ASSOC);
            if (!$request || $request['estado'] !== 'aprobada' || !$request['id_habilitacion']) {
                throw new RuntimeException('Solo se puede inscribir una solicitud aprobada y habilitada.');
            }
            if ($request['estado_usuario'] !== 'activo') {
                throw new RuntimeException('El estudiante debe tener una cuenta activa para inscribirse.');
            }

            $cohortQuery = $pdo->prepare('SELECT id_cohorte, codigo, fecha_inicio FROM mg_cohortes WHERE id_cohorte = :id AND activa = 1 FOR UPDATE');
            $cohortQuery->execute(['id' => $cohortId]);
            $cohort = $cohortQuery->fetch(PDO::FETCH_ASSOC);
            if (!$cohort) {
                throw new RuntimeException('Seleccione una cohorte activa.');
            }

            $existingEnrollment = $pdo->prepare('SELECT id_inscripcion FROM mg_inscripciones WHERE id_solicitud = :solicitud FOR UPDATE');
            $existingEnrollment->execute(['solicitud' => $requestId]);
            if ($existingEnrollment->fetchColumn()) {
                throw new RuntimeException('La solicitud ya tiene una inscripción formal.');
            }
            $activeStudentEnrollment = $pdo->prepare('SELECT id_inscripcion FROM mg_inscripciones WHERE id_estudiante = :estudiante AND estado = "activa" LIMIT 1 FOR UPDATE');
            $activeStudentEnrollment->execute(['estudiante' => (int) $request['id_estudiante']]);
            if ($activeStudentEnrollment->fetchColumn()) {
                throw new RuntimeException('El estudiante ya tiene una inscripción MG activa.');
            }

            $this->assertCurrentAcademicEligibility($pdo, (int) $request['id_estudiante']);
            $this->assertRequestContentStillValid($request);
            if (in_array($assignment, ['nuevo_grupo', 'grupo_existente'], true)) {
                $this->assertGroupAllowed($request);
            }

            if ($assignment === 'grupo_existente') {
                $work = $this->lockExistingGroup($pdo, (int) $existingWorkId, $cohortId, $request);
                $workId = (int) $work['id_trabajo'];
            } else {
                $workType = $assignment === 'nuevo_grupo' ? 'grupal' : 'individual';
                $workId = $this->createWork($pdo, $request, $cohort, $workType, $adminId);
            }

            $insert = $pdo->prepare(
                'INSERT INTO mg_inscripciones (id_solicitud, id_estudiante, id_cohorte, inscrito_por, observacion)
                 VALUES (:solicitud, :estudiante, :cohorte, :usuario, :observacion)'
            );
            $insert->execute([
                'solicitud' => $requestId, 'estudiante' => (int) $request['id_estudiante'], 'cohorte' => $cohortId,
                'usuario' => $adminId, 'observacion' => $note !== '' ? $note : null,
            ]);
            $enrollmentId = (int) $pdo->lastInsertId();
            $member = $pdo->prepare(
                'INSERT INTO mg_trabajo_integrantes (id_trabajo, id_inscripcion, id_estudiante, agregado_por)
                 VALUES (:trabajo, :inscripcion, :estudiante, :usuario)'
            );
            $member->execute([
                'trabajo' => $workId, 'inscripcion' => $enrollmentId,
                'estudiante' => (int) $request['id_estudiante'], 'usuario' => $adminId,
            ]);
            (new MgSeguimientoHitos())->generateForWork($pdo, $workId);
            $workCode = $pdo->prepare('SELECT codigo FROM mg_trabajos WHERE id_trabajo = :id');
            $workCode->execute(['id' => $workId]);
            $code = (string) $workCode->fetchColumn();
            $this->auditEnrollment($pdo, $requestId, $adminId, $cohort['codigo'], $code, $note);
            $pdo->commit();
            return $enrollmentId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
                throw new RuntimeException('La solicitud o el estudiante ya tiene una inscripción activa.');
            }
            throw $exception;
        }
    }

    private function assertCurrentAcademicEligibility(PDO $pdo, int $studentId): void
    {
        $rows = (new MgAcademico())->studentVerification($studentId);
        if (!$rows) {
            throw new RuntimeException('El estudiante ya no tiene un plan e historial aprobados vigentes.');
        }
        $summary = $rows[0];
        if ((int) $summary['materias_requeridas'] < 1
            || (int) $summary['materias_aprobadas'] !== (int) $summary['materias_requeridas']) {
            throw new RuntimeException('La inscripción requiere todas las materias obligatorias aprobadas.');
        }
    }

    private function assertRequestContentStillValid(array $request): void
    {
        if ((int) $request['requiere_tema_preliminar'] === 1 && trim((string) $request['tema_preliminar']) === '') {
            throw new RuntimeException('La solicitud aprobada no contiene el tema requerido por la modalidad.');
        }
        if ((int) $request['requiere_descripcion'] === 1 && trim((string) $request['descripcion']) === '') {
            throw new RuntimeException('La solicitud aprobada no contiene la descripción requerida por la modalidad.');
        }
    }

    private function assertGroupAllowed(array $request): void
    {
        if ((int) $request['permite_trabajo_grupal'] !== 1 || (int) $request['max_integrantes'] < 2) {
            throw new RuntimeException('La modalidad no está configurada para trabajo grupal con un máximo de integrantes.');
        }
    }

    private function lockExistingGroup(PDO $pdo, int $workId, int $cohortId, array $request): array
    {
        $workQuery = $pdo->prepare(
            'SELECT w.*, c.activa FROM mg_trabajos w
             INNER JOIN mg_cohortes c ON c.id_cohorte = w.id_cohorte
             WHERE w.id_trabajo = :id FOR UPDATE'
        );
        $workQuery->execute(['id' => $workId]);
        $work = $workQuery->fetch(PDO::FETCH_ASSOC);
        if (!$work || $work['estado'] !== 'activo' || (int) $work['activa'] !== 1
            || $work['tipo_trabajo'] !== 'grupal'
            || (int) $work['id_cohorte'] !== $cohortId
            || (int) $work['id_modalidad'] !== (int) $request['id_modalidad']) {
            throw new RuntimeException('El grupo debe estar activo y coincidir con cohorte y modalidad.');
        }
        $currentTopic = mb_strtolower(trim((string) ($work['tema'] ?? '')));
        $requestTopic = mb_strtolower(trim((string) ($request['tema_preliminar'] ?? '')));
        if ($currentTopic !== $requestTopic) {
            throw new RuntimeException('Para agrupar solicitudes, el tema preliminar debe coincidir con el tema del trabajo.');
        }
        $count = $pdo->prepare('SELECT COUNT(*) FROM mg_trabajo_integrantes WHERE id_trabajo = :id AND estado = "activo"');
        $count->execute(['id' => $workId]);
        if ((int) $count->fetchColumn() >= (int) $request['max_integrantes']) {
            throw new RuntimeException('El grupo alcanzó el máximo de integrantes configurado en la modalidad.');
        }
        $career = $pdo->prepare(
            'SELECT MIN(e.id_carrera), COUNT(DISTINCT e.id_carrera)
             FROM mg_trabajo_integrantes ti
             INNER JOIN mg_inscripciones i ON i.id_inscripcion = ti.id_inscripcion
             INNER JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
             WHERE ti.id_trabajo = :id AND ti.estado = "activo"'
        );
        $career->execute(['id' => $workId]);
        [$groupCareerId, $careerCount] = $career->fetch(PDO::FETCH_NUM);
        if ((int) $careerCount > 0 && (int) $groupCareerId !== (int) $request['id_carrera']) {
            throw new RuntimeException('Solo se pueden agrupar estudiantes de la misma carrera.');
        }
        return $work;
    }

    private function createWork(PDO $pdo, array $request, array $cohort, string $workType, int $adminId): int
    {
        $insert = $pdo->prepare(
            'INSERT INTO mg_trabajos (id_cohorte, id_modalidad, tipo_trabajo, tema, descripcion, creado_por)
             VALUES (:cohorte, :modalidad, :tipo, :tema, :descripcion, :usuario)'
        );
        $insert->execute([
            'cohorte' => (int) $cohort['id_cohorte'], 'modalidad' => (int) $request['id_modalidad'],
            'tipo' => $workType, 'tema' => $request['tema_preliminar'],
            'descripcion' => $request['descripcion'], 'usuario' => $adminId,
        ]);
        $workId = (int) $pdo->lastInsertId();
        $prefixes = [
            'GRADUACION_EXCELENCIA' => 'GE', 'PROYECTO_GRADO' => 'PG', 'TESIS' => 'TES',
            'EXAMEN_GRADO' => 'EG', 'TRABAJO_DIRIGIDO' => 'TD',
        ];
        $prefix = $prefixes[strtoupper((string) $request['codigo_modalidad'])] ?? 'MG';
        $year = substr((string) $cohort['fecha_inicio'], 0, 4);
        $code = sprintf('%s-%s-%05d', $prefix, $year, $workId);
        $update = $pdo->prepare('UPDATE mg_trabajos SET codigo = :codigo WHERE id_trabajo = :id');
        $update->execute(['codigo' => $code, 'id' => $workId]);
        return $workId;
    }

    private function auditEnrollment(PDO $pdo, int $requestId, int $adminId, string $cohortCode, string $workCode, string $note): void
    {
        $comment = 'Inscripción formal creada · Cohorte ' . $cohortCode . ' · Trabajo ' . $workCode;
        if ($note !== '') {
            $comment .= ' · ' . $note;
        }
        $statement = $pdo->prepare(
            'INSERT INTO mg_solicitud_historial
                (id_solicitud, accion, estado_anterior, estado_nuevo, id_actor, comentario, visible_estudiante)
             VALUES (:solicitud, "inscripcion_creada", "aprobada", "aprobada", :actor, :comentario, 1)'
        );
        $statement->execute(['solicitud' => $requestId, 'actor' => $adminId, 'comentario' => $comment]);
    }

    public function registrations(): array
    {
        return Database::connection()->query(
            'SELECT i.id_inscripcion, i.estado, i.inscrito_en, s.id_solicitud,
                    e.id_estudiante, e.registro_universitario, CONCAT(u.nombre, " ", u.apellido) AS estudiante,
                    c.codigo AS codigo_cohorte, c.nombre AS cohorte, m.nombre AS modalidad,
                    w.id_trabajo, w.codigo AS codigo_trabajo, w.tema, w.tipo_trabajo,
                    COUNT(ti.id_integrante) AS integrantes
             FROM mg_inscripciones i
             INNER JOIN mg_solicitudes s ON s.id_solicitud = i.id_solicitud
             INNER JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             INNER JOIN mg_cohortes c ON c.id_cohorte = i.id_cohorte
             INNER JOIN mg_trabajo_integrantes own ON own.id_inscripcion = i.id_inscripcion AND own.estado = "activo"
             INNER JOIN mg_trabajos w ON w.id_trabajo = own.id_trabajo
             INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad
             LEFT JOIN mg_trabajo_integrantes ti ON ti.id_trabajo = w.id_trabajo AND ti.estado = "activo"
             GROUP BY i.id_inscripcion, i.estado, i.inscrito_en, s.id_solicitud, e.id_estudiante,
                      e.registro_universitario, u.nombre, u.apellido, c.codigo, c.nombre, m.nombre,
                      w.id_trabajo, w.codigo, w.tema, w.tipo_trabajo
             ORDER BY i.inscrito_en DESC, i.id_inscripcion DESC LIMIT 1000'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function studentRegistration(int $studentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT i.id_inscripcion, i.estado AS estado_inscripcion, i.inscrito_en,
                    c.codigo AS codigo_cohorte, c.nombre AS cohorte, c.fecha_inicio, c.fecha_fin,
                    w.id_trabajo, w.codigo AS codigo_trabajo, w.tema, w.descripcion AS descripcion_trabajo,
                    w.tipo_trabajo, m.nombre AS modalidad,
                    COUNT(ti2.id_integrante) AS integrantes
             FROM mg_inscripciones i
             INNER JOIN mg_cohortes c ON c.id_cohorte = i.id_cohorte
             INNER JOIN mg_trabajo_integrantes ti ON ti.id_inscripcion = i.id_inscripcion AND ti.estado = "activo"
             INNER JOIN mg_trabajos w ON w.id_trabajo = ti.id_trabajo
             INNER JOIN mg_modalidades m ON m.id_modalidad = w.id_modalidad
             LEFT JOIN mg_trabajo_integrantes ti2 ON ti2.id_trabajo = w.id_trabajo AND ti2.estado = "activo"
             WHERE i.id_estudiante = :estudiante AND i.estado = "activa"
             GROUP BY i.id_inscripcion, i.estado, i.inscrito_en, c.codigo, c.nombre, c.fecha_inicio, c.fecha_fin,
                      w.id_trabajo, w.codigo, w.tema, w.descripcion, w.tipo_trabajo, m.nombre LIMIT 1'
        );
        $statement->execute(['estudiante' => $studentId]);
        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
