<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Roles';
$activePage = 'roles';

$messages = [
    'created' => 'Rol creado correctamente.',
    'updated' => 'Rol actualizado correctamente.',
    'deleted' => 'Rol eliminado correctamente.',
];
$messageCode = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$message = $messages[$messageCode] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$roles = (new RolesController())->index();

require dirname(__DIR__, 2) . '/views/roles/index.php';
