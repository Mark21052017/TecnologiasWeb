<?php

require dirname(__DIR__, 3) . '/includes/bootstrap.php';
Auth::requireRole('tutor');
Auth::requireModule('ofertas');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}
header('Location: ' . app_url('materias-ofertadas/?error=' . rawurlencode('El horario aceptado forma parte del compromiso y ya no se puede modificar desde esta página.')), true, 303);
exit;
