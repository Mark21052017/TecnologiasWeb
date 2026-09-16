<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$activePage = 'roles';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$controller = new RolesController();
$role = $id ? $controller->find($id) : null;

if (!$role) {
    http_response_code(404);
    exit('Rol no encontrado.');
}

$data = $role;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->update($id, $_POST);
        $data['id_rol'] = $id;
        if (!$errors) {
            header('Location: ' . app_url('roles/?message=updated'));
            exit;
        }
    }
}

$mode = 'edit';
require dirname(__DIR__, 2) . '/views/roles/form.php';
