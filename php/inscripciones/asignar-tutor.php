<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('inscripciones');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no válida.');
}

$error = (new InscripcionesController())->assignTutor($_POST);
if ($error !== null) {
    header('Location: ' . app_url('inscripciones/?error=' . rawurlencode($error)), true, 303);
    exit;
}

header('Location: ' . app_url('inscripciones/?message=assigned'), true, 303);
exit;
