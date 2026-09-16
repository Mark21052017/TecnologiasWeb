<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Carreras';
$activePage = 'carreras';

$messages = [
    'created' => 'Carrera creada correctamente.',
    'updated' => 'Carrera actualizada correctamente.',
    'deleted' => 'Carrera eliminada correctamente.',
];
$messageCode = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$message = $messages[$messageCode] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$carreras = (new CarrerasController())->index();

require dirname(__DIR__, 2) . '/views/carreras/index.php';
