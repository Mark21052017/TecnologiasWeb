<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('ofertas');

$tutorId = filter_input(INPUT_GET, 'id_tutor', FILTER_VALIDATE_INT);
if ($tutorId === false || $tutorId === null || $tutorId < 1) {
    http_response_code(404);
    exit('Perfil de tutor no encontrado.');
}

$profile = (new PerfilController())->publicTutorProfile((int) $tutorId);
if (!$profile) {
    http_response_code(404);
    exit('Perfil de tutor no encontrado.');
}

$title = 'Perfil del tutor';
$activePage = 'materias-disponibles';
$initials = strtoupper(substr((string) $profile['nombre'], 0, 1) . substr((string) $profile['apellido'], 0, 1));

require dirname(__DIR__, 2) . '/views/catalogo/perfil-tutor.php';
