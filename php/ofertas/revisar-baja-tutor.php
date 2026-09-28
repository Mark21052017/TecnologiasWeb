<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no válida.');
}

$error = (new OfertasController())->reviewTutorWithdrawal($_POST, (int) Auth::user()['id_usuario']);
if ($error !== null) {
    header('Location: ' . app_url('ofertas/?error=' . rawurlencode($error)), true, 303);
    exit;
}

header('Location: ' . app_url('ofertas/?message=withdrawal-reviewed'), true, 303);
exit;
