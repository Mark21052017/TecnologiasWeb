<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
$title = 'Ofertas academicas';
$activePage = 'ofertas';
$controller = new OfertasController();
$ofertas = $controller->index();
$message = [
    'created' => 'Oferta creada correctamente.',
    'updated' => 'Oferta actualizada correctamente.',
    'deleted' => 'Oferta eliminada correctamente.',
][(string) ($_GET['message'] ?? '')] ?? null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/ofertas/index.php';
