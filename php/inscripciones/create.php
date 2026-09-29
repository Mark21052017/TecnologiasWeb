<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('inscripciones');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('La solicitud no es válida.');
}

$user = Auth::user();
$studentId = (new Tutoria())->studentIdByUserId((int) $user['id_usuario']);

try {
    if (!$studentId) {
        throw new RuntimeException('Tu cuenta no tiene un perfil de estudiante activo.');
    }
    (new InscripcionesController())->register((int) $studentId, $_POST);
    header('Location: ' . app_url('materias-disponibles/?message=created'), true, 303);
    exit;
} catch (Throwable $exception) {
    if (!$exception instanceof RuntimeException) {
        error_log($exception->getMessage());
    }
    $error = $exception instanceof RuntimeException
        ? $exception->getMessage()
        : 'No se pudo registrar la materia. Inténtalo nuevamente.';
    header('Location: ' . app_url('materias-disponibles/?error=' . rawurlencode($error)), true, 303);
    exit;
}
