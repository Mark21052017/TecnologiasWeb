<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('turnos');
$title = 'Nuevo turno';
$activePage = 'turnos';
$data = ['nombre_turno' => '', 'hora_inicio' => '', 'hora_fin' => '', 'estado' => 'activo'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = (new TurnosController())->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('turnos/?message=created'), true, 303);
            exit;
        }
    }
}
require dirname(__DIR__, 2) . '/views/turnos/form.php';
