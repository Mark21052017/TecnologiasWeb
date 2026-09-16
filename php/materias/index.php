<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Materias';
$activePage = 'materias';

$messages = [
    'created' => 'Materia creada correctamente.',
    'updated' => 'Materia actualizada correctamente.',
    'deleted' => 'Materia eliminada correctamente.',
];
$messageCode = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$message = $messages[$messageCode] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$materias = (new MateriasController())->index();

require dirname(__DIR__, 2) . '/views/materias/index.php';
