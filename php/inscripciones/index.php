<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['administrador', 'tutor', 'estudiante']);
Auth::requireModule('inscripciones');
$title = 'Inscripciones';
$activePage = 'inscripciones';
$user = Auth::user();
$role = (string) $user['nombre_rol'];
$controller = new InscripcionesController();
$inscripciones = $controller->index($role, (int) $user['id_usuario']);
$tutorOptionsByOffer = $role === 'administrador'
    ? $controller->confirmedTutorsForOffers(array_column($inscripciones, 'id_oferta'))
    : [];
$message = [
    'created' => 'Inscripcion registrada correctamente.',
    'cancelled' => 'Inscripcion cancelada correctamente.',
    'assigned' => 'Tutor asignado a la inscripción correctamente.',
][(string) ($_GET['message'] ?? '')] ?? null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/inscripciones/index.php';
