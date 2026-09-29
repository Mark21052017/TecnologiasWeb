<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('inscripciones');
Auth::requireRole('administrador');

$user = Auth::user();
$controller = new PostulacionesTutoriaController();
$errors = [];
$message = match ((string) ($_GET['message'] ?? '')) {
    'approved' => 'Postulación aprobada e inscripción registrada; el tutor puede asignarse después.',
    'rejected' => 'La postulación fue rechazada y se notificó el motivo.',
    default => null,
};
$error = (string) ($_GET['error'] ?? '');
$pending = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    try {
        $controller->review($_POST, (int) $user['id_usuario']);
        $decision = (string) ($_POST['estado'] ?? '');
        header('Location: ' . app_url('postulaciones-tutoria/?message=' . ($decision === 'aprobada' ? 'approved' : 'rejected')), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar la postulación.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$pending = $controller->pending();
$title = 'Postulaciones a tutoría';
$activePage = 'postulaciones-tutoria';
require dirname(__DIR__, 2) . '/views/postulaciones-tutoria/index.php';
