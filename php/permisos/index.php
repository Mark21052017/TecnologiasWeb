<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('permisos');
$title = 'Permisos';
$activePage = 'permisos';
$roleId = filter_input(INPUT_GET, 'rol', FILTER_VALIDATE_INT) ?: null;
$userId = filter_input(INPUT_GET, 'usuario', FILTER_VALIDATE_INT) ?: null;
$controller = new PermisosController();
$data = $controller->data($roleId, $userId);
$message = ($_GET['message'] ?? '') === 'saved' ? 'Permisos guardados correctamente.' : null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;

require dirname(__DIR__, 2) . '/views/permisos/index.php';
