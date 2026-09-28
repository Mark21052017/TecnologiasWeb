<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$title = 'Cuentas de acceso';
$activePage = 'usuarios';

$messages = [
    'created' => 'Cuenta o perfil creado correctamente.',
    'updated' => 'Cuenta o perfil actualizado correctamente.',
    'deleted' => 'Perfil académico eliminado correctamente.',
    'deactivated' => 'Usuario desactivado correctamente.',
    'activated' => 'Cuenta aprobada y activada correctamente.',
];
$message = $messages[$_GET['message'] ?? ''] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;
$controller = new UsuariosController();
$validRoleFilters = ['', 'estudiante', 'tutor', 'otros'];
$selectedRoleFilter = isset($_GET['rol']) && is_string($_GET['rol']) && in_array($_GET['rol'], $validRoleFilters, true)
    ? $_GET['rol']
    : '';
$searchQuery = isset($_GET['q']) && is_string($_GET['q']) ? substr($_GET['q'], 0, 120) : '';
$usuarios = $controller->index();
if ($selectedRoleFilter === 'otros') {
    $usuarios = array_values(array_filter($usuarios, static fn (array $user): bool => !in_array($user['nombre_rol'], ['estudiante', 'tutor'], true)));
} elseif ($selectedRoleFilter !== '') {
    $usuarios = array_values(array_filter($usuarios, static fn (array $user): bool => $user['nombre_rol'] === $selectedRoleFilter));
}
$protectedAdminId = null;
foreach ($usuarios as $usuario) {
    if (!empty($usuario['es_admin_protegido'])) {
        $protectedAdminId = (int) $usuario['id_usuario'];
        break;
    }
}
$returnRoleFilter = $selectedRoleFilter;

require dirname(__DIR__) . '/views/usuarios/index.php';
