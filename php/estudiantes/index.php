<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Estudiantes';
$activePage = 'estudiantes';

$messages = [
    'created' => 'Estudiante creado correctamente.',
    'updated' => 'Estudiante actualizado correctamente.',
    'deleted' => 'Perfil de estudiante eliminado correctamente.',
];
$messageCode = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$message = $messages[$messageCode] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$students = (new EstudiantesController())->index();

require dirname(__DIR__, 2) . '/views/estudiantes/index.php';
