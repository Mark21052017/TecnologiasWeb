<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('tutorias');
$title = 'Nueva solicitud';
$activePage = 'tutorias';
$user = Auth::user();
$controller = new TutoriasController();
$data = ['id_oferta' => '', 'fecha' => '', 'hora_inicio' => '', 'hora_fin' => '', 'modalidad' => 'presencial', 'lugar_o_enlace' => '', 'observaciones' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->store($_POST, (new Tutoria())->studentIdByUserId((int) $user['id_usuario']));
        if (!$errors) {
            header('Location: ' . app_url('tutorias/?message=created'));
            exit;
        }
    }
}

$offerings = $controller->options();
require dirname(__DIR__, 2) . '/views/tutorias/form.php';
