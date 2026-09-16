<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('permisos');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$roleId = filter_var($_POST['id_rol'] ?? null, FILTER_VALIDATE_INT);
if (!$roleId) {
    header('Location: ' . app_url('permisos/?error=Rol no valido.'));
    exit;
}
$error = (new PermisosController())->saveRole($roleId, $_POST);
header('Location: ' . app_url('permisos/?rol=' . $roleId . '&usuario=' . (int) ($_POST['id_usuario'] ?? 0) . ($error ? '&error=' . rawurlencode($error) : '&message=saved')));
exit;
