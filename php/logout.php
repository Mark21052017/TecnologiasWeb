<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}

Auth::logout();
header('Location: ' . app_url('login.php'), true, 303);
exit;
