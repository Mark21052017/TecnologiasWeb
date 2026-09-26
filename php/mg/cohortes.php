<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol(['administrador', 'coordinador_mg', 'auxiliar_mg']);
requerirPermiso(in_array(Auth::user()['nombre_rol'], ['administrador', 'coordinador_mg'], true)
    ? 'mg.cohortes.gestionar'
    : 'mg.cohortes.ver');

$controller = new MgConfiguracionController();
$errors = [];
$message = flash_get('mg_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $action = (string) ($_POST['accion'] ?? 'guardar');
    if ($action === 'estado') {
        requerirPermiso('mg.cohortes.gestionar');
        $error = $controller->setCohortActive($_POST);
    } else {
        requerirPermiso('mg.cohortes.gestionar');
        $error = $controller->saveCohort($_POST, (int) Auth::user()['id_usuario']);
    }
    if ($error !== null) {
        $errors[] = $error;
    } else {
        flash_set('mg_message', $action === 'estado' ? 'Estado de cohorte actualizado.' : 'Cohorte guardada correctamente.');
        header('Location: ' . app_url('modalidades-grado/cohortes.php'), true, 303);
        exit;
    }
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$cohort = $id !== false && $id !== null ? $controller->cohort((int) $id) : null;
$cohorts = $controller->cohorts();
$canManage = in_array(Auth::user()['nombre_rol'], ['administrador', 'coordinador_mg'], true);
$title = 'Cohortes MG';
$activePage = 'modalidades-grado';
require dirname(__DIR__, 2) . '/views/mg/cohortes.php';
