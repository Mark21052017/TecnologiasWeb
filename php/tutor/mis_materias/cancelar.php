<?php

require dirname(__DIR__, 3) . '/includes/bootstrap.php';
Auth::requireRole('tutor');
Auth::requireModule('ofertas');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no válida.');
}

[$state, $error] = (new OfertasController())->requestTutorWithdrawal((int) Auth::user()['id_usuario'], $_POST);
if ($error !== null) {
    header('Location: ' . app_url('materias-ofertadas/?error=' . rawurlencode($error)), true, 303);
    exit;
}

$message = $state === 'cancelada' ? 'withdrawn' : 'withdrawal-requested';
header('Location: ' . app_url('materias-ofertadas/?message=' . $message), true, 303);
exit;
