<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('tutores');
$title = 'Tutores disponibles';
$activePage = 'tutores-disponibles';
$tutors = (new CatalogoEstudianteController())->tutores();

require dirname(__DIR__, 2) . '/views/catalogo/tutores.php';
