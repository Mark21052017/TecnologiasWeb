<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.inscripciones.gestionar');

$controller = new MgInscripcionesGradoController();
$errors = [];
$message = flash_get('mg_enrollment_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    try {
        $id = $controller->enroll($_POST, (int) Auth::user()['id_usuario']);
        flash_set('mg_enrollment_message', 'Inscripción formal #' . $id . ' creada y asociada a cohorte y trabajo.');
        header('Location: ' . app_url('modalidades-grado/inscripciones.php'), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo crear la inscripción formal.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$eligible = $controller->eligibleRequests();
$cohorts = $controller->cohorts();
$groups = $controller->openGroups();
$registrations = $controller->registrations();
$title = 'Inscripciones MG';
$activePage = 'mg-cohortes';
require dirname(__DIR__, 2) . '/views/mg/inscripciones.php';
