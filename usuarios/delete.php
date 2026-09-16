<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    header('Location: ' . app_url('usuarios/?error=Identificador+no+valido'));
    exit;
}

$error = (new UsuariosController())->deactivate($id);
if ($error !== null) {
    header('Location: ' . app_url('usuarios/?error=' . rawurlencode($error)));
    exit;
}

header('Location: ' . app_url('usuarios/?message=deactivated'));
exit;
