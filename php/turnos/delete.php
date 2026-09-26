<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('turnos');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$error = !$id ? 'Turno no valido.' : (new TurnosController())->delete($id);
header('Location: ' . app_url('turnos/' . ($error ? '?error=' . rawurlencode($error) : '?message=deleted')), true, 303);
exit;
