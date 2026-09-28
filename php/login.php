<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$error = flash_get('login_error');
$success = ($_GET['registered'] ?? '') === '1'
    ? 'Registro enviado. Un administrador debe aprobar tu cuenta antes de iniciar sesión.'
    : (($_GET['tutor_registered'] ?? '') === '1'
        ? 'Postulación enviada. Un administrador debe aprobar tu cuenta desde Cuentas de acceso antes de que puedas iniciar sesión.'
        : null);
$username = (string) flash_get('login_username', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string) ($_POST['usuario'] ?? '');
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        $error = (new AuthController())->login(
            $username,
            (string) ($_POST['contrasena'] ?? '')
        );
    }
    if ($error !== null) {
        flash_set('login_error', $error);
        flash_set('login_username', $username);
        header('Location: ' . app_url('login.php'), true, 303);
        exit;
    }
}

require dirname(__DIR__) . '/views/auth/login.php';
