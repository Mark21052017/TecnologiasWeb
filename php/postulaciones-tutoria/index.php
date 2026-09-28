<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('inscripciones');
requerirRol(['administrador', 'estudiante']);

$user = Auth::user();
$role = (string) $user['nombre_rol'];
$controller = new PostulacionesTutoriaController();
$errors = [];
$message = match ((string) ($_GET['message'] ?? '')) {
    'submitted' => 'Tu postulación fue enviada. No reserva un cupo mientras espera revisión.',
    'cancelled' => 'La postulación fue cancelada.',
    'approved' => 'Postulación aprobada e inscripción registrada; el tutor puede asignarse después.',
    'rejected' => 'La postulación fue rechazada y se notificó el motivo.',
    default => null,
};
$error = (string) ($_GET['error'] ?? '');
$studentId = null;
$applications = [];
$pending = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    try {
        if ($role === 'administrador') {
            $controller->review($_POST, (int) $user['id_usuario']);
            $decision = (string) ($_POST['estado'] ?? '');
            header('Location: ' . app_url('postulaciones-tutoria/?message=' . ($decision === 'aprobada' ? 'approved' : 'rejected')), true, 303);
            exit;
        }
        $studentId = $controller->studentId((int) $user['id_usuario']);
        if (!$studentId) {
            throw new RuntimeException('Tu cuenta no tiene un perfil de estudiante activo.');
        }
        $controller->cancel((int) ($_POST['id_postulacion'] ?? 0), $studentId, (int) $user['id_usuario']);
        header('Location: ' . app_url('postulaciones-tutoria/?message=cancelled'), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar la postulación.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

if ($role === 'administrador') {
    $pending = $controller->pending();
    $title = 'Postulaciones a tutoría';
} else {
    $studentId = $studentId ?? $controller->studentId((int) $user['id_usuario']);
    $applications = $studentId ? $controller->ownApplications($studentId) : [];
    $title = 'Mis postulaciones a tutoría';
}
$activePage = 'postulaciones-tutoria';
require dirname(__DIR__, 2) . '/views/postulaciones-tutoria/index.php';
