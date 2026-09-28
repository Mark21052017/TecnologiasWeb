<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol(['administrador', 'coordinador_mg']);
requerirPermiso('mg.calendario.gestionar');

$controller = new MgConfiguracionController();
$errors = [];
$message = flash_get('mg_template_message');
$modalityFilter = filter_var($_GET['id_modalidad'] ?? null, FILTER_VALIDATE_INT);
$selectedModalityId = $modalityFilter !== false && $modalityFilter !== null && $modalityFilter > 0 ? (int) $modalityFilter : 0;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $action = (string) ($_POST['accion'] ?? '');
    $error = $action === 'crear'
        ? $controller->createTemplateFromCalendar($_POST, (int) Auth::user()['id_usuario'])
        : ($action === 'aplicar'
            ? $controller->applyTemplate($_POST, (int) Auth::user()['id_usuario'])
            : 'Seleccione una acción válida.');
    if ($error !== null) {
        $errors[] = $error;
    } else {
        flash_set('mg_template_message', $action === 'crear'
            ? 'Plantilla creada desde los hitos activos del calendario.'
            : 'Plantilla aplicada; los hitos y obligaciones se generaron.');
        header('Location: ' . app_url('modalidades-grado/plantillas-calendario.php?id_modalidad=' . max(0, (int) ($_POST['id_modalidad'] ?? $selectedModalityId))), true, 303);
        exit;
    }
}

$cohorts = $controller->cohorts();
$modalities = array_values(array_filter($controller->modalities(), static fn (array $row): bool => $row['estado'] === 'activa'));
$templates = $controller->templates($selectedModalityId > 0 ? $selectedModalityId : null);
$title = 'Plantillas de calendario MG';
$activePage = 'mg-cohortes';
require dirname(__DIR__, 2) . '/views/mg/plantillas-calendario.php';
