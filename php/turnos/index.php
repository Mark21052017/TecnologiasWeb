<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('turnos');
$title = 'Turnos';
$activePage = 'turnos';
$turnos = (new TurnosController())->index();
$message = [
    'created' => 'Turno creado correctamente.',
    'updated' => 'Turno actualizado correctamente.',
    'deleted' => 'Turno eliminado correctamente.',
][(string) ($_GET['message'] ?? '')] ?? null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/turnos/index.php';
