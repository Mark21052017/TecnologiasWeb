<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

$user = Auth::user();
$destination = 'login.php';
if ($user) {
    $destination = in_array($user['nombre_rol'] ?? '', ['coordinador_mg', 'auxiliar_mg'], true)
        ? 'modalidades-grado/'
        : 'dashboard.php';
}
header('Location: ' . app_url($destination), true, 303);
exit;
