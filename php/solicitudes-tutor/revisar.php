<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('solicitudes_tutor');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$error = (new SolicitudesTutorController())->review($_POST, (int) Auth::user()['id_usuario']);
header('Location: ' . app_url('solicitudes-tutor/' . ($error ? '?error=' . rawurlencode($error) : '?message=reviewed')), true, 303);
exit;
