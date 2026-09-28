<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');

$user = Auth::user();
$role = (string) ($user['nombre_rol'] ?? '');
$controller = new MgConfiguracionController();
$mode = 'own';
$summary = [];
$adminSummary = [];
$cohorts = [];

if ($role === 'administrador') {
    requerirPermiso('mg.configuracion.ver');
    $mode = 'admin';
    $summary = $controller->summary();
    $adminSummary = (new MgAcademicoController())->administrativeSummary();
} elseif ($role === 'coordinador_mg') {
    requerirRol(['coordinador_mg']);
    requerirPermiso('mg.configuracion.ver');
    $mode = 'coordinator';
    $summary = $controller->summary();
} elseif ($role === 'auxiliar_mg') {
    requerirRol(['auxiliar_mg']);
    requerirPermiso('mg.cohortes.ver');
    $mode = 'assistant';
    $cohorts = $controller->cohorts(true);
} elseif ($role === 'tutor' || $role === 'estudiante') {
    requerirRol([$role]);
    requerirPermiso('mg.calendario.ver_propio');
    if ($role === 'estudiante') {
        requerirPermiso('mg.academico.verificar');
    }
} else {
    http_response_code(403);
    exit('El rol actual no tiene acceso a Modalidades de Grado.');
}

$title = 'Modalidades de Grado';
$activePage = 'mg-resumen';
require dirname(__DIR__, 2) . '/views/mg/index.php';
