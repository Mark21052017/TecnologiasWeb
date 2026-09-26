<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();
$user = Auth::user();
if (in_array($user['nombre_rol'] ?? '', ['coordinador_mg', 'auxiliar_mg'], true)) {
    header('Location: ' . app_url('modalidades-grado/'), true, 303);
    exit;
}
Auth::requireModule('dashboard');

$activePage = 'dashboard';
$stats = (new DashboardController())->summary((string) $user['nombre_rol'], (int) $user['id_usuario']);
require dirname(__DIR__) . '/views/dashboard.php';
