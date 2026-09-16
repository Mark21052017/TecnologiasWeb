<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('materias');
$title = 'Materias disponibles';
$activePage = 'materias-disponibles';
$subjects = (new CatalogoEstudianteController())->materias();

require dirname(__DIR__, 2) . '/views/catalogo/materias.php';
