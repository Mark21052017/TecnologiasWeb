<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('ofertas');
$title = 'Materias disponibles';
$activePage = 'materias-disponibles';
$user = Auth::user();
$studentId = (new Tutoria())->studentIdByUserId((int) $user['id_usuario']);
$subjects = (new OfertasController())->publicOffers($studentId);
$tutorsByOffer = (new OfertaTutoria())->confirmedTutorsForOffers(array_column($subjects, 'id_oferta'));
foreach ($subjects as &$subject) {
    $subject['tutores_asignados'] = $tutorsByOffer[(int) $subject['id_oferta']] ?? [];
}
unset($subject);
$message = (string) ($_GET['message'] ?? '') === 'created' ? 'Inscripcion registrada correctamente.' : null;
$error = (string) ($_GET['error'] ?? '');

require dirname(__DIR__, 2) . '/views/catalogo/materias.php';
