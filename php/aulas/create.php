<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
$title = 'Nueva aula';
$activePage = 'aulas';
$data = ['nombre_aula' => '', 'ubicacion' => '', 'capacidad' => '', 'estado' => 'activa'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = (new AulasController())->store($_POST);
        if (!$errors) {
            header('Location: ' . app_url('aulas/?message=created'), true, 303);
            exit;
        }
    }
}
require dirname(__DIR__, 2) . '/views/aulas/form.php';
