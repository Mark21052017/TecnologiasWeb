<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireLogin();
Auth::requireModule('dashboard');

$user = Auth::user();
$activePage = 'dashboard';
$stats = (new DashboardController())->summary((string) $user['nombre_rol'], (int) $user['id_usuario']);
require dirname(__DIR__) . '/views/dashboard.php';
