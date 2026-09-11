<?php

require dirname(__DIR__, 2) . '/app/bootstrap.php';
Auth::requireRole('administrador');

$controller = new UsuariosController();
$roles = $controller->roles();
$data = [
    'id_rol' => '',
    'nombre' => '',
    'apellido' => '',
    'correo' => '',
    'usuario' => '',
    'contrasena' => '',
    'telefono' => '',
    'estado' => 'activo',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('usuarios/?message=created'));
            exit;
        }
    }
}

$mode = 'create';
require dirname(__DIR__, 2) . '/app/Views/usuarios/form.php';
