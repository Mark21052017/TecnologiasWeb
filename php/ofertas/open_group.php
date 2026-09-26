<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id === false || $id < 1) {
    header('Location: ' . app_url('ofertas/?error=' . rawurlencode('Oferta no valida.')), true, 303);
    exit;
}
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    header('Location: ' . app_url('ofertas/?error=' . rawurlencode('La sesion del formulario no es valida.')), true, 303);
    exit;
}

[$newOfferId, $error] = (new OfertasController())->openNextGroup((int) $id);
if ($error !== null) {
    header('Location: ' . app_url('ofertas/?error=' . rawurlencode($error)), true, 303);
    exit;
}

header('Location: ' . app_url('ofertas/edit.php?id=' . (int) $newOfferId . '&message=group-created'), true, 303);
