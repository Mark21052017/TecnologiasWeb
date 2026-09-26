<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('periodos');

$title = 'Periodos de tutorias';
$activePage = 'periodos';
$periodos = (new PeriodosController())->index();
$message = [
    'created' => 'Periodo creado correctamente.',
    'updated' => 'Periodo actualizado correctamente.',
    'deleted' => 'Periodo eliminado correctamente.',
][(string) ($_GET['message'] ?? '')] ?? null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/periodos/index.php';
