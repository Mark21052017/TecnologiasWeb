<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['administrador', 'tutor']);
if ((Auth::user()['nombre_rol'] ?? '') === 'administrador') {
    header('Location: ' . app_url('turnos/'), true, 303);
    exit;
}
Auth::requireModule('ofertas');
$title = 'Disponibilidad';
$activePage = 'disponibilidad';
$subjects = (new OfertasController())->tutorOffers((int) Auth::user()['id_usuario']);
$message = (string) ($_GET['message'] ?? '') === 'saved' ? 'Disponibilidad actualizada correctamente.' : null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/disponibilidad/index.php';
