<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('periodos');

$title = 'Nuevo periodo';
$activePage = 'periodos';
$options = (new PeriodosController())->options();
$data = [
    'nombre_periodo' => '', 'id_tipo_tutoria' => '', 'fecha_inicio' => '', 'fecha_fin' => '',
    'inscripcion_inicio' => '', 'inscripcion_fin' => '', 'estado' => 'borrador',
];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = (new PeriodosController())->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('periodos/?message=created'), true, 303);
            exit;
        }
    }
}
require dirname(__DIR__, 2) . '/views/periodos/form.php';
