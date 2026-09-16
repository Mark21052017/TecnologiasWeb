<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}

[$data, $errors] = (new TutorMateriasController())->store($_POST);
if ($errors) {
    header('Location: ' . app_url('asignaciones/?error=' . rawurlencode(implode(' ', $errors))));
    exit;
}

header('Location: ' . app_url('asignaciones/?message=created'));
exit;
