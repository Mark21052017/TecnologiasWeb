<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$activePage = 'tipos-tutoria';

$controller = new TiposTutoriaController();
$data = ['nombre' => '', 'descripcion' => '', 'estado' => 'activo'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('tipos-tutoria/?message=created'), true, 303);
            exit;
        }
    }
}

$mode = 'create';
require dirname(__DIR__, 2) . '/views/tipos-tutoria/form.php';
