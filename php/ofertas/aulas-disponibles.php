<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    (new OfertasController())->roomAvailability($_GET),
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
