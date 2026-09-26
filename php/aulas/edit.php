<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$controller = new AulasController();
$data = $id ? $controller->find($id) : null;
if (!$data) {
    header('Location: ' . app_url('aulas/?error=' . rawurlencode('Aula no valida.')), true, 303);
    exit;
}

$title = 'Editar aula';
$activePage = 'aulas';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->update((int) $id, $_POST);
        if (!$errors) {
            header('Location: ' . app_url('aulas/?message=updated'), true, 303);
            exit;
        }
    }
}

$mode = 'edit';
require dirname(__DIR__, 2) . '/views/aulas/form.php';
