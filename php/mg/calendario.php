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
        $modalityId = filter_var($_POST['id_modalidad'] ?? null, FILTER_VALIDATE_INT);
        flash_set('mg_message', $action === 'estado' ? 'Estado del hito actualizado.' : 'Hito guardado correctamente.');
        header('Location: ' . app_url('modalidades-grado/calendario.php?id_cohorte=' . max(0, (int) $cohortId) . '&id_modalidad=' . max(0, (int) $modalityId)), true, 303);
        exit;
    }
}

$cohorts = $controller->cohorts();
$modalities = $controller->modalities();
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
$rawModality = $_GET['id_modalidad'] ?? null;
$legacyCalendar = $rawModality === 'legacy';
$selectedModalityId = 0;
if (!$legacyCalendar) {
    $modalityFilter = filter_var($rawModality, FILTER_VALIDATE_INT);
    if ($modalityFilter !== false && $modalityFilter !== null && $modalityFilter > 0) {
        $selectedModalityId = (int) $modalityFilter;
    } else {
        foreach ($modalities as $option) {
            if ($option['estado'] === 'activa') {
                $selectedModalityId = (int) $option['id_modalidad'];
                break;
            }
        }
    }
}
$editingId = filter_var($_GET['id_hito'] ?? null, FILTER_VALIDATE_INT);
$milestone = $editingId !== false && $editingId !== null ? $controller->milestone((int) $editingId) : null;
if ($milestone) {
    $selectedCohortId = (int) $milestone['id_cohorte'];
    $selectedCohort = $controller->cohort($selectedCohortId);
    if ($milestone['id_modalidad'] === null) {
        $legacyCalendar = true;
        $selectedModalityId = 0;
    } else {
        $selectedModalityId = (int) $milestone['id_modalidad'];
        $legacyCalendar = false;
    }
}
$selectedModality = null;
foreach ($modalities as $option) {
    if ((int) $option['id_modalidad'] === $selectedModalityId) {
        $selectedModality = $option;
        break;
    }
}
$readOnly = $role === 'auxiliar_mg' || $legacyCalendar || !$selectedModality || $selectedModality['estado'] !== 'activa';
$milestones = $selectedCohort ? $controller->calendarScope($selectedCohortId, $legacyCalendar ? null : $selectedModalityId, $role === 'auxiliar_mg') : [];
$reportCount = count(array_filter($milestones, static fn (array $row): bool => $row['tipo'] === 'informe' && $row['estado'] === 'activo'));
$milestoneTypes = $controller->milestoneTypes();
$templates = $controller->templates($selectedModalityId > 0 ? $selectedModalityId : null);
$canManage = in_array($role, ['administrador', 'coordinador_mg'], true) && !$readOnly
    && $selectedCohort && (int) $selectedCohort['activa'] === 1 && $selectedModality && $selectedModality['estado'] === 'activa';
$title = 'Calendario MG';
$activePage = 'mg-cohortes';
require dirname(__DIR__, 2) . '/views/mg/calendario.php';
