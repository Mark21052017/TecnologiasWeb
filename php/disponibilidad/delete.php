<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();
http_response_code(410);
exit('Los horarios son definidos por la universidad y se seleccionan desde Materias ofertadas.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Solicitud no valida.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$controller = new DisponibilidadController();
$data = $id ? $controller->find($id) : null;
$user = Auth::user();
$isAdmin = ($user['nombre_rol'] ?? '') === 'administrador';
$tutorId = $isAdmin ? null : (new Tutor())->findIdByUserId((int) $user['id_usuario']);
if (!$id || !$data || (!$isAdmin && (int) $data['id_tutor'] !== (int) $tutorId)) {
    header('Location: ' . app_url('disponibilidad/?error=' . rawurlencode('Horario no valido.')), true, 303);
    exit;
}

$error = $controller->delete($id, $data);
header('Location: ' . app_url('disponibilidad/' . ($error ? '?error=' . rawurlencode($error) : '?message=deleted')));
exit;
