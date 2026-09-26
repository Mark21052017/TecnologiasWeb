<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Cuentas de acceso';
$activePage = 'usuarios';

$messages = [
    'created' => 'Usuario creado correctamente.',
    'updated' => 'Usuario actualizado correctamente.',
    'deactivated' => 'Usuario desactivado correctamente.',
    'activated' => 'Cuenta aprobada y activada correctamente.',
];
$message = $messages[$_GET['message'] ?? ''] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$controller = new UsuariosController();
$usuarios = $controller->index();
$protectedAdminId = null;
foreach ($usuarios as $usuario) {
    if (!empty($usuario['es_admin_protegido'])) {
        $protectedAdminId = (int) $usuario['id_usuario'];
        break;
    }
}

require dirname(__DIR__) . '/views/usuarios/index.php';
