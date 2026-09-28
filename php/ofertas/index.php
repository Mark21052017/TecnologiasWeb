<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
$title = 'Ofertas academicas';
$activePage = 'ofertas';
$controller = new OfertasController();
$ofertas = $controller->index();
$bajasTutor = $controller->pendingTutorWithdrawals();
$historialBajasTutor = $controller->recentTutorWithdrawals();
$message = [
    'created' => 'Oferta creada correctamente.',
    'updated' => 'Oferta actualizada correctamente.',
    'deleted' => 'Oferta eliminada correctamente.',
    'withdrawal-reviewed' => 'La solicitud de baja del tutor fue resuelta.',
][(string) ($_GET['message'] ?? '')] ?? null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/ofertas/index.php';
