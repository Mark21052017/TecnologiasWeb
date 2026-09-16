<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('accesos');
$title = 'Registro de accesos';
$activePage = 'accesos';
$accesses = (new AccesosController())->index();

require dirname(__DIR__, 2) . '/views/accesos/index.php';
