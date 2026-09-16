<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('disponibilidad');
$title = 'Horarios disponibles';
$activePage = 'horarios-disponibles';
$availability = (new CatalogoEstudianteController())->availability();

require dirname(__DIR__, 2) . '/views/catalogo/disponibilidad.php';
