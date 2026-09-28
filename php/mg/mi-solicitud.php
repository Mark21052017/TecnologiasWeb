<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('estudiante');
requerirPermiso('mg.solicitudes.propias');

$user = Auth::user();
$controller = new MgSolicitudesController();
$studentId = $controller->studentIdForUser((int) $user['id_usuario']);
if (!$studentId) {
    http_response_code(403);
    exit('La cuenta no tiene un perfil de estudiante asociado.');
}

$errors = [];
$message = flash_get('mg_request_message');
$requestId = 0;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $requestId = (int) ($_POST['id_solicitud'] ?? 0);
    $action = (string) ($_POST['accion'] ?? 'guardar');
    try {
        if ($action === 'cancelar') {
            $controller->cancel($requestId, $studentId, (int) $user['id_usuario'], (string) ($_POST['observacion_estudiante'] ?? ''));
            flash_set('mg_request_message', 'La solicitud fue cancelada.');
        } elseif ($action === 'entregar_hito') {
            requerirPermiso('mg.hitos.entregar_propio');
            $controller->deliverMilestone((int) ($_POST['id_seguimiento'] ?? 0), $studentId, (int) $user['id_usuario'], $_POST);
            flash_set('mg_request_message', 'La entrega del hito quedó registrada.');
        } elseif ($action === 'entregar_informe') {
            requerirPermiso('mg.informes.propios');
            (new MgSeguimientoController())->uploadReport((int) ($_POST['id_seguimiento'] ?? 0), $studentId, (int) $user['id_usuario'], $_FILES['archivo'] ?? [], $_POST);
            flash_set('mg_request_message', 'La versión del informe fue cargada para revisión.');
        } else {
            $requestId = $controller->saveDraft($studentId, (int) $user['id_usuario'], $_POST);
            if ($action === 'enviar') {
                $controller->submit($requestId, $studentId, (int) $user['id_usuario']);
                flash_set('mg_request_message', 'Solicitud enviada. La verificación académica quedó registrada para revisión.');
            } else {
                flash_set('mg_request_message', 'Borrador guardado.');
            }
        }
        header('Location: ' . app_url('modalidades-grado/mi-solicitud.php' . ($requestId > 0 ? '?id=' . $requestId : '')), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar la solicitud.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$requests = $controller->studentRequests($studentId);
$selectedId = (int) ($_GET['id'] ?? 0);
if ($selectedId < 1 && $requestId > 0) {
    $selectedId = $requestId;
}
$activeRequest = null;
foreach ($requests as $request) {
    if (in_array($request['estado'], ['borrador', 'enviada', 'en_revision', 'observada', 'aprobada'], true)) {
        $activeRequest = $request;
        break;
    }
}
if ($selectedId > 0) {
    $selectedRequest = $controller->studentRequest($selectedId, $studentId);
    if (!$selectedRequest) {
        http_response_code(404);
        exit('La solicitud no existe.');
    }
} elseif ($activeRequest) {
    $selectedId = (int) $activeRequest['id_solicitud'];
    $selectedRequest = $controller->studentRequest($selectedId, $studentId);
} else {
    $selectedRequest = null;
}
$milestoneTasks = $selectedRequest && !empty($selectedRequest['id_trabajo'])
    ? (new MgSeguimientoHitosController())->tasksForStudent((int) $selectedRequest['id_trabajo'], $studentId)
    : [];
$studentSessions = $selectedRequest && !empty($selectedRequest['id_trabajo'])
    ? (new MgSeguimientoController())->studentSessions((int) $selectedRequest['id_trabajo'], $studentId)
    : [];
$studentStageResults = $selectedRequest && !empty($selectedRequest['id_trabajo'])
    ? (new MgSeguimientoController())->studentStageResults((int) $selectedRequest['id_trabajo'])
    : [];
$studentDefenseInfo = $selectedRequest && !empty($selectedRequest['id_trabajo'])
    ? (new MgDefensasController())->studentInfo((int) $selectedRequest['id_trabajo'], $studentId)
    : [];
$studentDefenseInfo = $selectedRequest && !empty($selectedRequest['id_trabajo'])
    ? (new MgDefensasController())->studentDefenseInfo((int) $selectedRequest['id_trabajo'], $studentId)
    : [];
$editing = $selectedRequest && in_array($selectedRequest['estado'], ['borrador', 'observada'], true);
$canCreate = $activeRequest === null;
$modalities = $controller->modalitiesForStudent($studentId);
$academicVerification = $controller->academicVerification($studentId);
$pendingSubjects = $controller->pendingSubjects($studentId);
$title = 'Mi Modalidad de Grado';
$activePage = 'mg-solicitud';
require dirname(__DIR__, 2) . '/views/mg/mi-solicitud.php';
