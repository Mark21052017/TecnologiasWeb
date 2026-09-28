<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.academico.asignar_plan');

$controller = new MgAcademicoController();
$errors = [];
$message = flash_get('mg_admin_message');
$search = trim((string) ($_GET['buscar'] ?? ''));
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $error = $controller->assignPlan($_POST, (int) Auth::user()['id_usuario']);
    if ($error !== null) {
        $errors[] = $error;
    } else {
        flash_set('mg_admin_message', 'Plan asignado; el cambio quedó registrado en el historial.');
        header('Location: ' . app_url('modalidades-grado/planes-estudiante.php?buscar=' . rawurlencode($search)), true, 303);
        exit;
    }
}

$students = $controller->studentsForPlanAssignment($search);
$plans = $controller->approvedPlans();
$historyStudentId = (int) ($_GET['historial'] ?? 0);
$assignmentHistory = $historyStudentId > 0 ? $controller->planAssignmentHistory($historyStudentId) : [];
$title = 'Asignación de planes MG';
$activePage = 'mg-configuracion';
require dirname(__DIR__, 2) . '/views/mg/planes-estudiante.php';
