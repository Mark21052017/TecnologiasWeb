<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('tutor');
Auth::requireModule('ofertas');
$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
if (preg_match('#/mis-materias/?$#', $requestPath)) {
    header('Location: ' . app_url('materias-ofertadas/'), true, 302);
    exit;
}
$title = 'Materias ofertadas';
$activePage = 'materias-ofertadas';
$user = Auth::user();
$userId = (int) $user['id_usuario'];
$controller = new OfertasController();
$subjects = $controller->tutorOffers($userId);
$availableSubjects = $controller->availableForTutor($userId);
$messages = [
    'added' => 'Materia aceptada. Confirmaste el compromiso de todos los días y horarios indicados.',
    'withdrawn' => 'La asignación se canceló y quedó registrada en el historial.',
    'withdrawal-requested' => 'Tu solicitud de baja fue enviada a Administración para revisión y coordinación del reemplazo.',
    'replacement-needed' => 'El tutor anterior solicitó la baja. Puedes aceptar esta materia como reemplazo.',
];
$message = $messages[$_GET['message'] ?? ''] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;

require dirname(__DIR__, 2) . '/views/tutor/mis-materias.php';
