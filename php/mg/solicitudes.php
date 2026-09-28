<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.solicitudes.revisar');

$controller = new MgSolicitudesController();
$errors = [];
$message = flash_get('mg_request_admin_message');
$state = (string) ($_GET['estado'] ?? '');
$requestId = (int) ($_GET['id'] ?? 0);
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $requestId = (int) ($_POST['id_solicitud'] ?? 0);
    try {
        if ((string) ($_POST['accion'] ?? '') === 'habilitar') {
            $controller->habilitate($requestId, (int) Auth::user()['id_usuario'], (string) ($_POST['observacion'] ?? ''));
            flash_set('mg_request_admin_message', 'Habilitación formal registrada por separado de la aprobación.');
        } else {
            $controller->review($requestId, (int) Auth::user()['id_usuario'], (string) ($_POST['accion'] ?? ''), (string) ($_POST['observacion'] ?? ''));
            flash_set('mg_request_admin_message', 'La revisión quedó registrada en el historial.');
        }
        header('Location: ' . app_url('modalidades-grado/solicitudes.php?id=' . $requestId . '&estado=' . rawurlencode($state)), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar la revisión.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$requests = $controller->adminRequests($state);
$selectedRequest = $requestId > 0 ? $controller->adminRequest($requestId) : null;
if ($requestId > 0 && !$selectedRequest) {
    http_response_code(404);
    exit('La solicitud no existe.');
}
$title = 'Solicitudes MG';
$activePage = 'mg-solicitudes';
require dirname(__DIR__, 2) . '/views/mg/solicitudes.php';
