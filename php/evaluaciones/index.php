<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['administrador', 'tutor', 'estudiante']);
Auth::requireModule('evaluaciones');
$user = Auth::user();
$role = (string) $user['nombre_rol'];
$title = 'Evaluaciones';
$activePage = 'evaluaciones';
$controller = new EvaluacionesController();
$userId = (int) $user['id_usuario'];
$filters = [
    'id_materia' => filter_var($_GET['id_materia'] ?? null, FILTER_VALIDATE_INT) ?: '',
    'fecha_desde' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['fecha_desde'] ?? '')) ? $_GET['fecha_desde'] : '',
    'fecha_hasta' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['fecha_hasta'] ?? '')) ? $_GET['fecha_hasta'] : '',
];
$evaluations = $controller->index($role, $userId, $filters);
$filterOptions = $controller->filterOptions($role, $userId);
$evaluationSummary = $controller->summary($role, $userId);
$message = ($_GET['message'] ?? '') === 'created' ? 'Evaluacion registrada correctamente.' : null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;

require dirname(__DIR__, 2) . '/views/evaluaciones/index.php';
