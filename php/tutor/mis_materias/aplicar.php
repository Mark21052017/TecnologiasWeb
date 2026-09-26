<?php

require dirname(__DIR__, 3) . '/includes/bootstrap.php';
Auth::requireRole('tutor');
Auth::requireModule('ofertas');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$error = (new OfertasController())->selectAsTutor((int) Auth::user()['id_usuario'], $_POST);
header('Location: ' . app_url('materias-ofertadas/' . ($error ? '?error=' . rawurlencode($error) : '?message=added')), true, 303);
exit;
