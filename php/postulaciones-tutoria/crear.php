<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('inscripciones');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('La solicitud no es válida.');
}
$user = Auth::user();
$controller = new PostulacionesTutoriaController();
$studentId = $controller->studentId((int) $user['id_usuario']);
try {
    if (!$studentId) {
        throw new RuntimeException('Tu cuenta no tiene un perfil de estudiante activo.');
    }
    $controller->submit($studentId, (int) $user['id_usuario'], $_POST);
    header('Location: ' . app_url('postulaciones-tutoria/?message=submitted'), true, 303);
    exit;
} catch (Throwable $exception) {
    if (!$exception instanceof RuntimeException) {
        error_log($exception->getMessage());
    }
    header('Location: ' . app_url('materias-disponibles/?error=' . rawurlencode($exception->getMessage())), true, 303);
    exit;
}
