<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol(['administrador', 'coordinador_mg']);
requerirPermiso('mg.modalidades.gestionar');

$controller = new MgConfiguracionController();
$errors = [];
$message = flash_get('mg_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $action = (string) ($_POST['accion'] ?? 'guardar');
    $error = $action === 'estado'
        ? $controller->setModalityActive($_POST)
        : $controller->saveModality($_POST);
    if ($error !== null) {
        $errors[] = $error;
    } else {
        flash_set('mg_message', $action === 'estado' ? 'Estado de modalidad actualizado.' : 'Modalidad guardada correctamente.');
        header('Location: ' . app_url('modalidades-grado/modalidades.php'), true, 303);
        exit;
    }
}

$modalities = $controller->modalities();
$title = 'Modalidades MG';
$activePage = 'modalidades-grado';
require dirname(__DIR__, 2) . '/views/mg/modalidades.php';
