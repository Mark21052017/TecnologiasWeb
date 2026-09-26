<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('inscripciones');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
$studentId = (new Tutoria())->studentIdByUserId((int) Auth::user()['id_usuario']);
$error = !$studentId ? 'El usuario no tiene un perfil de estudiante.' : (new InscripcionesController())->store($studentId, $_POST);
header('Location: ' . app_url('materias-disponibles/' . ($error ? '?error=' . rawurlencode($error) : '?message=created')), true, 303);
exit;
