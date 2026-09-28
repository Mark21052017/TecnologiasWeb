<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.modalidades.carrera.gestionar');

$controller = new MgAcademicoController();
$errors = [];
$message = flash_get('mg_admin_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $error = $controller->setCareerModality($_POST, (int) Auth::user()['id_usuario']);
    if ($error !== null) {
        $errors[] = $error;
    } else {
        flash_set('mg_admin_message', 'La disponibilidad de la modalidad por carrera fue actualizada.');
        header('Location: ' . app_url('modalidades-grado/oferta-carreras.php'), true, 303);
        exit;
    }
}

$pairs = $controller->careerModalities();
$title = 'Oferta MG por carrera';
$activePage = 'mg-configuracion';
require dirname(__DIR__, 2) . '/views/mg/oferta-carreras.php';
