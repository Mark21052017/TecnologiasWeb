<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('periodos');

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$controller = new PeriodosController();
$data = $id ? $controller->find($id) : null;
if (!$data) {
    header('Location: ' . app_url('periodos/?error=' . rawurlencode('Periodo no valido.')), true, 303);
    exit;
}
$title = 'Editar periodo';
$activePage = 'periodos';
$options = $controller->options();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->update((int) $id, $_POST);
        if (!$errors) {
            header('Location: ' . app_url('periodos/?message=updated'), true, 303);
            exit;
        }
        $fresh = $controller->find((int) $id);
        if ($fresh) {
            $data += array_diff_key($fresh, $data);
        }
    }
}
$mode = 'edit';
require dirname(__DIR__, 2) . '/views/periodos/form.php';
