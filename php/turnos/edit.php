<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('turnos');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$controller = new TurnosController();
$data = $id ? $controller->find($id) : null;
if (!$data) {
    header('Location: ' . app_url('turnos/?error=' . rawurlencode('Turno no valido.')), true, 303);
    exit;
}

$title = 'Editar turno';
$activePage = 'turnos';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->update((int) $id, $_POST);
        if (!$errors) {
            header('Location: ' . app_url('turnos/?message=updated'), true, 303);
            exit;
        }
    }
}

$mode = 'edit';
require dirname(__DIR__, 2) . '/views/turnos/form.php';
