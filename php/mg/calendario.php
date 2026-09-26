<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol(['administrador', 'coordinador_mg', 'auxiliar_mg']);
$role = (string) Auth::user()['nombre_rol'];
requerirPermiso('mg.calendario.ver');

$controller = new MgConfiguracionController();
$errors = [];
$message = flash_get('mg_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    requerirPermiso('mg.calendario.gestionar');
    $action = (string) ($_POST['accion'] ?? 'guardar');
    $error = $action === 'estado'
        ? $controller->setMilestoneActive($_POST)
        : $controller->saveMilestone($_POST, (int) Auth::user()['id_usuario']);
    if ($error !== null) {
        $errors[] = $error;
    } else {
        $cohortId = filter_var($_POST['id_cohorte'] ?? null, FILTER_VALIDATE_INT);
        flash_set('mg_message', $action === 'estado' ? 'Estado del hito actualizado.' : 'Hito guardado correctamente.');
        header('Location: ' . app_url('modalidades-grado/calendario.php?id_cohorte=' . max(0, (int) $cohortId)), true, 303);
        exit;
    }
}

$cohorts = $controller->cohorts();
$cohortId = filter_var($_GET['id_cohorte'] ?? null, FILTER_VALIDATE_INT);
$selectedCohortId = $cohortId !== false && $cohortId !== null ? (int) $cohortId : 0;
if (!$selectedCohortId && $cohorts) {
    foreach ($cohorts as $row) {
        if ((int) $row['activa'] === 1) {
            $selectedCohortId = (int) $row['id_cohorte'];
            break;
        }
    }
}
$selectedCohort = $selectedCohortId ? $controller->cohort($selectedCohortId) : null;
$editingId = filter_var($_GET['id_hito'] ?? null, FILTER_VALIDATE_INT);
$milestone = $editingId !== false && $editingId !== null ? $controller->milestone((int) $editingId) : null;
if ($milestone) {
    $selectedCohortId = (int) $milestone['id_cohorte'];
    $selectedCohort = $controller->cohort($selectedCohortId);
}
$readOnly = $role === 'auxiliar_mg';
$milestones = $selectedCohort ? $controller->calendar($selectedCohortId, $readOnly) : [];
$reportCount = count(array_filter($milestones, static fn (array $row): bool => $row['tipo'] === 'informe' && $row['estado'] === 'activo'));
$title = 'Calendario MG';
$activePage = 'modalidades-grado';
require dirname(__DIR__, 2) . '/views/mg/calendario.php';
