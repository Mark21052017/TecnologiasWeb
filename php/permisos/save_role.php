<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('permisos');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$roleName = isset($_POST['rol']) && is_string($_POST['rol']) ? trim($_POST['rol']) : null;
if (!$roleName) {
    header('Location: ' . app_url('permisos/?error=' . rawurlencode('Rol no valido.')), true, 303);
    exit;
}
$error = (new PermisosController())->saveRole($roleName, $_POST);
$query = 'rol=' . rawurlencode($roleName) . ($error ? '&error=' . rawurlencode($error) : '&message=saved');
header('Location: ' . app_url('permisos/?' . $query), true, 303);
exit;
