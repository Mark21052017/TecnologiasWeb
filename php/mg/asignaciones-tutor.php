<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.tutores.asignar');

$controller = new MgAsignacionesTutorController();
$errors = [];
$message = flash_get('mg_tutor_assignment_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    try {
        $controller->assign($_POST, (int) Auth::user()['id_usuario']);
        flash_set('mg_tutor_assignment_message', 'La asignación quedó registrada; las asignaciones anteriores se conservan en el historial.');
        header('Location: ' . app_url('modalidades-grado/asignaciones-tutor.php'), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo asignar el tutor.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$capacityRule = $controller->capacityRule();
$works = $controller->works();
$tutors = $controller->tutors();
$title = 'Asignación de tutores MG';
$activePage = 'mg-cohortes';
require dirname(__DIR__, 2) . '/views/mg/asignaciones-tutor.php';
