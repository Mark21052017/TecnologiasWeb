<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('tutor');
Auth::requireModule('tutorias');

$title = 'Programar sesion';
$activePage = 'tutorias';
$userId = (int) Auth::user()['id_usuario'];
$controller = new TutoriasController();
$data = [
    'id_inscripcion' => filter_var($_GET['id_inscripcion'] ?? null, FILTER_VALIDATE_INT) ?: '',
    'fecha' => '',
    'modalidad' => 'presencial',
    'lugar_o_enlace' => '',
    'observaciones' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        [$data, $errors] = $controller->schedule($_POST, $userId);
        if (!$errors) {
            header('Location: ' . app_url('tutorias/?message=scheduled'), true, 303);
            exit;
        }
    }
}

$enrollments = $controller->sessionOptions($userId);
require dirname(__DIR__, 2) . '/views/tutorias/programar.php';
