<?php

declare(strict_types=1);

final class MgSolicitudes
{
    private const ACTIVE_STATES = ['borrador', 'enviada', 'en_revision', 'observada', 'aprobada'];

    public function modalitiesForStudent(int $studentId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id_modalidad, m.codigo, m.nombre, m.descripcion, m.requiere_tutor,
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
            'SELECT s.*, m.codigo AS codigo_modalidad, m.nombre AS modalidad,
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
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function requestForStudent(int $requestId, int $studentId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.*, m.codigo AS codigo_modalidad, m.nombre AS modalidad,
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
        return $request;
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
                if (!in_array($current['estado'], ['borrador', 'observada'], true)) {
                    throw new RuntimeException('Solo se puede editar un borrador o una solicitud observada.');
                }
                $update = $pdo->prepare(
                    'UPDATE mg_solicitudes SET id_modalidad = :modalidad, tipo_trabajo = :tipo,
                        tema_preliminar = :tema, descripcion = :descripcion, observaciones_estudiante = :observaciones
                     WHERE id_solicitud = :id'
                );
                $update->execute([
                    'modalidad' => $modalityId, 'tipo' => $workType,
                    'tema' => $topic !== '' ? $topic : null, 'descripcion' => $description !== '' ? $description : null,
                    'observaciones' => $studentNotes !== '' ? $studentNotes : null, 'id' => $requestId,
                ]);
                $this->event($pdo, $requestId, $current['estado'] === 'observada' ? 'corregida' : 'borrador_actualizado', $current['estado'], $current['estado'], $actorId, null, null);
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
            $this->validateRequestModality($pdo, $studentId, $request);
            $summary = $this->academicSummary($studentId);
            $this->requireAllRequiredSubjectsPassed($summary);
            $update = $pdo->prepare(
                'UPDATE mg_solicitudes SET estado = "enviada", id_plan_verificado = :plan,
                    materias_requeridas_snapshot = :requeridas, materias_aprobadas_snapshot = :aprobadas,
                    promedio_snapshot = :promedio, verificado_en = CURRENT_TIMESTAMP, enviado_en = CURRENT_TIMESTAMP
                 WHERE id_solicitud = :id'
            );
            $update->execute([
                'plan' => $summary['id_plan_estudio'], 'requeridas' => $summary['materias_requeridas'],
                'aprobadas' => $summary['materias_aprobadas'], 'promedio' => $summary['promedio_aprobadas'], 'id' => $requestId,
            ]);
            $action = $request['estado'] === 'observada' ? 'reenviada' : 'enviada';
            $this->event($pdo, $requestId, $action, $request['estado'], 'enviada', $actorId, null, $summary);
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
            if (!in_array($request['estado'], ['borrador', 'observada'], true)) {
                throw new RuntimeException('Solo se puede cancelar un borrador o una solicitud observada.');
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
                    $summary = $this->academicSummary((int) $request['id_estudiante']);
                    $this->requireAllRequiredSubjectsPassed($summary);
                    $this->validateRequestModality($pdo, (int) $request['id_estudiante'], $request);
                    $this->saveAcademicSnapshot($pdo, $requestId, $summary);
                    $this->updateReview($pdo, $requestId, $reviewerId);
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
            $summary = $this->academicSummary((int) $request['id_estudiante']);
            $this->requireAllRequiredSubjectsPassed($summary);
            $this->validateRequestModality($pdo, (int) $request['id_estudiante'], $request);
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

    private function requireAllRequiredSubjectsPassed(array $summary): void
    {
        $required = (int) $summary['materias_requeridas'];
        $passed = (int) $summary['materias_aprobadas'];
        if ($required < 1 || $passed !== $required) {
            $pending = max(0, $required - $passed);
            throw new RuntimeException("No se puede enviar ni aprobar: materias obligatorias {$passed}/{$required}; pendientes o sin aprobar: {$pending}.");
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
            'SELECT m.id_modalidad, m.permite_trabajo_grupal, m.max_integrantes,
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
            'SELECT s.*, e.id_estudiante, e.id_usuario AS id_usuario_estudiante FROM mg_solicitudes s
             INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
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

    private function saveAcademicSnapshot(PDO $pdo, int $requestId, array $summary): void
    {
        $statement = $pdo->prepare(
            'UPDATE mg_solicitudes SET id_plan_verificado = :plan,
                materias_requeridas_snapshot = :requeridas, materias_aprobadas_snapshot = :aprobadas,
                promedio_snapshot = :promedio, verificado_en = CURRENT_TIMESTAMP WHERE id_solicitud = :id'
        );
        $statement->execute([
            'plan' => $summary['id_plan_estudio'], 'requeridas' => $summary['materias_requeridas'],
            'aprobadas' => $summary['materias_aprobadas'], 'promedio' => $summary['promedio_aprobadas'], 'id' => $requestId,
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
