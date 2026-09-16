<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$error = null;
$success = ($_GET['registered'] ?? '') === '1'
    ? 'Registro enviado. Un administrador debe aprobar tu cuenta antes de iniciar sesion.'
    : (($_GET['tutor_registered'] ?? '') === '1' ? 'Postulacion enviada. Un administrador debe aprobar tu cuenta antes de iniciar sesion.' : null);
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string) ($_POST['usuario'] ?? '');
    $error = (new AuthController())->login(
        $username,
        (string) ($_POST['contrasena'] ?? '')
    );
}

require dirname(__DIR__) . '/views/auth/login.php';
