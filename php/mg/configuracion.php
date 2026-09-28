<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol(['administrador', 'coordinador_mg']);
requerirPermiso('mg.configuracion.ver');

$role = (string) Auth::user()['nombre_rol'];
$title = 'Configuración MG';
$activePage = 'mg-configuracion';
require dirname(__DIR__, 2) . '/views/mg/configuracion.php';
