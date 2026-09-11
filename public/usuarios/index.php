<?php

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole('administrador');

$messages = [
    'created' => 'Usuario creado correctamente.',
    'updated' => 'Usuario actualizado correctamente.',
    'deactivated' => 'Usuario desactivado correctamente.',
];
$message = $messages[$_GET['message'] ?? ''] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$usuarios = (new UsuariosController())->index();

require dirname(__DIR__, 2) . '/app/Views/usuarios/index.php';
