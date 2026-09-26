<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: ' . app_url('tipos-tutoria/?error=Identificador+no+valido'), true, 303);
    exit;
}

$error = (new TiposTutoriaController())->deactivate($id);
if ($error !== null) {
    header('Location: ' . app_url('tipos-tutoria/?error=' . rawurlencode($error)), true, 303);
    exit;
}

header('Location: ' . app_url('tipos-tutoria/?message=deactivated'), true, 303);
exit;
