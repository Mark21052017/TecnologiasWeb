<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Tipos de Tutoría';
$activePage = 'tipos-tutoria';

$messages = [
    'created' => 'Tipo de tutoria creado correctamente.',
    'updated' => 'Tipo de tutoria actualizado correctamente.',
    'deactivated' => 'Tipo de tutoria desactivado correctamente.',
];
$messageCode = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$message = $messages[$messageCode] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$tiposTutoria = (new TiposTutoriaController())->index();

require dirname(__DIR__, 2) . '/views/tipos-tutoria/index.php';
