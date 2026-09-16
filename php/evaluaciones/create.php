<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('evaluaciones');
$title = 'Nueva evaluacion';
$activePage = 'evaluaciones';
$user = Auth::user();
$controller = new EvaluacionesController();
$data = ['id_tutoria' => '', 'calificacion' => '', 'comentario' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->store($_POST, (int) $user['id_usuario']);
        if (!$errors) {
            header('Location: ' . app_url('evaluaciones/?message=created'));
            exit;
        }
    }
}

$options = $controller->options((int) $user['id_usuario']);
require dirname(__DIR__, 2) . '/views/evaluaciones/form.php';
