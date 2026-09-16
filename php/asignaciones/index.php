<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Asignaciones';
$activePage = 'asignaciones';

$messages = ['created' => 'Asignacion creada correctamente.', 'deleted' => 'Asignacion eliminada correctamente.'];
$message = $messages[$_GET['message'] ?? ''] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$controller = new TutorMateriasController();
$assignments = $controller->index();
$options = $controller->options();

require dirname(__DIR__, 2) . '/views/asignaciones/index.php';
