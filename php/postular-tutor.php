<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . app_url('dashboard.php'), true, 303);
    exit;
}

$title = 'Postular como tutor';
$data = [
    'nombre' => '',
    'apellido' => '',
    'correo' => '',
    'usuario' => '',
    'contrasena' => '',
    'confirmacion' => '',
    'telefono' => '',
    'especialidad' => '',
    'biografia' => '',
];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesión del formulario no es válida. Recargue la página.';
    } else {
        [$data, $errors] = (new RegistroTutorController())->register($_POST);
        if (!$errors) {
            header('Location: ' . app_url('login.php?tutor_registered=1'), true, 303);
            exit;
        }
    }
}

require dirname(__DIR__) . '/views/auth/tutor-application.php';
