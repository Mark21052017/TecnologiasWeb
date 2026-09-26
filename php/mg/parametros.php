<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol(['administrador', 'coordinador_mg']);
requerirPermiso('mg.parametros.gestionar');

$controller = new MgConfiguracionController();
$errors = [];
$message = flash_get('mg_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $error = $controller->updateParameter($_POST, (int) Auth::user()['id_usuario']);
    if ($error !== null) {
        $errors[] = $error;
    } else {
        flash_set('mg_message', 'Parámetro actualizado correctamente.');
        header('Location: ' . app_url('modalidades-grado/parametros.php'), true, 303);
        exit;
    }
}

$parameters = $controller->parameters();
$title = 'Parámetros MG';
$activePage = 'modalidades-grado';
require dirname(__DIR__, 2) . '/views/mg/parametros.php';
