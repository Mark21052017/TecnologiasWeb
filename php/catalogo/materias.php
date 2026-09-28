<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('estudiante');
Auth::requireModule('ofertas');
$title = 'Materias disponibles';
$activePage = 'materias-disponibles';
$user = Auth::user();
$studentId = (new Tutoria())->studentIdByUserId((int) $user['id_usuario']);
$subjects = (new OfertasController())->publicOffers($studentId);
$postulationsByOffer = $studentId ? (new PostulacionesTutoriaController())->latestStatusByOffer($studentId) : [];
$message = (string) ($_GET['message'] ?? '') === 'created' ? 'Inscripcion registrada correctamente.' : null;
$error = (string) ($_GET['error'] ?? '');

require dirname(__DIR__, 2) . '/views/catalogo/materias.php';
