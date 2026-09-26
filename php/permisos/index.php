<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('permisos');
$title = 'Permisos';
$activePage = 'permisos';
$roleName = isset($_GET['rol']) && is_string($_GET['rol']) ? trim($_GET['rol']) : null;
$controller = new PermisosController();
$data = $controller->data($roleName);
$message = ($_GET['message'] ?? '') === 'saved' ? 'Permisos guardados correctamente.' : null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;

require dirname(__DIR__, 2) . '/views/permisos/index.php';
