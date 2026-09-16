<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Nuevo estudiante';
$activePage = 'estudiantes';

$controller = new EstudiantesController();
$data = [
    'id_usuario' => (string) (filter_input(INPUT_GET, 'usuario', FILTER_VALIDATE_INT) ?: ''),
    'id_carrera' => '',
    'semestre' => '',
    'registro_universitario' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('estudiantes/?message=created'));
            exit;
        }
    }
}

$options = $controller->options();
$mode = 'create';
require dirname(__DIR__, 2) . '/views/estudiantes/form.php';
