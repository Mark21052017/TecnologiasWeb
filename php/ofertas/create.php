<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
$title = 'Nueva oferta academica';
$activePage = 'ofertas';
$controller = new OfertasController();
$options = $controller->options();
$data = ['id_periodo' => '', 'id_carrera' => '', 'id_materia' => '', 'id_tipo_tutoria' => '', 'id_turno' => 0, 'frecuencia_programacion' => 'semanal', 'fechas' => [], 'nombre_grupo' => 'Grupo A', 'cupo' => 20, 'descripcion' => '', 'estado' => 'pendiente', 'weekly_room' => 0, 'schedules' => []];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('ofertas/?message=created'), true, 303);
            exit;
        }
    }
}
require dirname(__DIR__, 2) . '/views/ofertas/form.php';
