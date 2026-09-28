<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
$user = Auth::user();
$role = (string) ($user['nombre_rol'] ?? '');
if ($role === 'administrador') {
    requerirPermiso('mg.seguimiento.gestionar');
} elseif ($role === 'tutor') {
    requerirPermiso('mg.seguimiento.propios');
} else {
    http_response_code(403);
    exit('No tiene acceso al seguimiento de trabajos MG.');
}

$controller = new MgSeguimientoController();
$userId = (int) $user['id_usuario'];
$works = $controller->works($role === 'tutor' ? $userId : null);
$workId = (int) ($_GET['id_trabajo'] ?? $_POST['id_trabajo'] ?? 0);
if ($workId < 1 && $works) {
    $workId = (int) $works[0]['id_trabajo'];
}
$allowedWorkIds = array_map(static fn (array $work): int => (int) $work['id_trabajo'], $works);
if ($workId > 0 && !in_array($workId, $allowedWorkIds, true)) {
    http_response_code(403);
    exit('No tiene acceso a este trabajo.');
}

$errors = [];
$message = flash_get('mg_followup_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    try {
        $action = (string) ($_POST['accion'] ?? '');
        if (in_array($action, ['iniciar_revision', 'observar_informe', 'aprobar_informe'], true)) {
            requerirPermiso('mg.informes.revisar');
            $reviewAction = match ($action) {
                'iniciar_revision' => 'iniciar_revision',
                'observar_informe' => 'observar',
                default => 'aprobar',
            };
            $controller->reviewReport((int) ($_POST['id_version'] ?? 0), $userId, $role, $reviewAction, (string) ($_POST['observacion_tutor'] ?? ''));
            flash_set('mg_followup_message', 'La revisión del informe quedó registrada.');
        } elseif ($action === 'crear_sesion') {
            requerirPermiso('mg.asistencia.registrar');
            $sessionId = $controller->createSession($workId, $userId, $role, $_POST);
            flash_set('mg_followup_message', 'Sesión MG #' . $sessionId . ' programada. Se generaron los registros de asistencia.');
        } elseif ($action === 'guardar_asistencia') {
            requerirPermiso('mg.asistencia.registrar');
            $controller->recordAttendance((int) ($_POST['id_sesion'] ?? 0), $userId, $role,
                is_array($_POST['asistencia'] ?? null) ? $_POST['asistencia'] : [],
                is_array($_POST['observacion_asistencia'] ?? null) ? $_POST['observacion_asistencia'] : []);
            flash_set('mg_followup_message', 'La asistencia fue actualizada.');
        } elseif ($action === 'guardar_etapa') {
            requerirPermiso('mg.etapas.registrar');
            $controller->saveStageResult($workId, $userId, $role, $_POST);
            flash_set('mg_followup_message', 'El resultado de etapa y su historial fueron registrados.');
        } else {
            throw new RuntimeException('Seleccione una acción de seguimiento válida.');
        }
        header('Location: ' . app_url('modalidades-grado/seguimiento.php?id_trabajo=' . $workId), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar el seguimiento.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$detail = $workId > 0 ? $controller->detail($workId, $userId, $role) : null;
$title = 'Seguimiento MG';
$activePage = $role === 'administrador' ? 'mg-seguimiento' : 'mg-mis-trabajos';
$capacityRule = (new MgAsignacionesTutorController())->capacityRule();
require dirname(__DIR__, 2) . '/views/mg/seguimiento.php';
