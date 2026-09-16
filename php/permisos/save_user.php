<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('permisos');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$userId = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
if (!$userId) {
    header('Location: ' . app_url('permisos/?error=Usuario no valido.'));
    exit;
}
$error = (new PermisosController())->saveUser($userId, $_POST);
header('Location: ' . app_url('permisos/?rol=' . (int) ($_POST['id_rol'] ?? 0) . '&usuario=' . $userId . ($error ? '&error=' . rawurlencode($error) : '&message=saved')));
exit;
