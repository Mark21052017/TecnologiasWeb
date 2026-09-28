<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$controller = new OfertasController();
$data = $id ? $controller->find($id) : null;
if (!$data) {
    header('Location: ' . app_url('ofertas/?error=' . rawurlencode('Oferta no valida.')), true, 303);
    exit;
}
$title = 'Editar oferta academica';
$activePage = 'ofertas';
$options = $controller->options();
$message = (string) ($_GET['message'] ?? '') === 'group-created' ? 'Nuevo grupo creado como pendiente con las mismas fechas y aula del grupo anterior. Verifique cupo y aula antes de publicarlo.' : null;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->update((int) $id, $_POST);
        if (!$errors) {
            header('Location: ' . app_url('ofertas/?message=updated'), true, 303);
            exit;
        }
    }
}
$mode = 'edit';
require dirname(__DIR__, 2) . '/views/ofertas/form.php';
