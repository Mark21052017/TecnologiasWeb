<?php

require dirname(__DIR__) . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string) ($_POST['usuario'] ?? '');
    $error = (new AuthController())->login(
        $username,
        (string) ($_POST['contrasena'] ?? '')
    );
}

require dirname(__DIR__) . '/app/Views/auth/login.php';
