<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('ofertas');
header('Location: ' . app_url('materias-disponibles/'), true, 303);
exit;
