<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('tutor');
requerirPermiso('mg.trabajos.propios');

$user = Auth::user();
$works = (new MgAsignacionesTutorController())->tutorWorks((int) $user['id_usuario']);
$title = 'Mis trabajos de grado';
$activePage = 'mg-mis-trabajos';
require dirname(__DIR__, 2) . '/views/mg/mis-trabajos.php';
