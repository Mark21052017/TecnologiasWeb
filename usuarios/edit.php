<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$activePage = 'usuarios';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$controller = new UsuariosController();
$account = $id ? $controller->find($id) : null;

if (!$account) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

if ($controller->isProtectedAdmin((int) $account['id_usuario'])) {
    http_response_code(403);
    exit('La cuenta admin esta protegida y no puede modificarse.');
}

$roles = $controller->roles();
$data = [
    'id_usuario' => $account['id_usuario'],
    'id_rol' => (string) $account['id_rol'],
    'nombre' => $account['nombre'],
    'apellido' => $account['apellido'],
    'correo' => $account['correo'],
    'usuario' => $account['usuario'],
    'contrasena' => '',
    'telefono' => $account['telefono'] ?? '',
    'estado' => $account['estado'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->update($id, $_POST);
        $data['id_usuario'] = $id;
        if (!$errors) {
            header('Location: ' . app_url('usuarios/?message=updated'));
            exit;
        }
    }
}

$mode = 'edit';
require dirname(__DIR__) . '/views/usuarios/form.php';
