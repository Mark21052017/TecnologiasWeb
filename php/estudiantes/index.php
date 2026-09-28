<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$query = ['rol' => 'estudiante'];
foreach (['message', 'error'] as $key) {
    if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
        $query[$key] = $_GET[$key];
    }
}
header('Location: ' . app_url('usuarios/?' . http_build_query($query)), true, 302);
exit;
