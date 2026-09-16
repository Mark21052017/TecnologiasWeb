<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['administrador', 'tutor']);
Auth::requireModule('disponibilidad');
$title = 'Disponibilidad';
$activePage = 'disponibilidad';
$user = Auth::user();
$controller = new DisponibilidadController();
$isAdmin = ($user['nombre_rol'] ?? '') === 'administrador';
$tutorId = $isAdmin ? null : (new Tutor())->findIdByUserId((int) $user['id_usuario']);
$availability = $controller->index($tutorId);
$tutors = $isAdmin ? $controller->tutors() : [];
$messages = ['created' => 'Horario creado correctamente.', 'updated' => 'Horario actualizado correctamente.', 'deleted' => 'Horario eliminado correctamente.'];
$message = $messages[$_GET['message'] ?? ''] ?? null;
$error = isset($_GET['error']) && is_string($_GET['error']) ? $_GET['error'] : null;

require dirname(__DIR__, 2) . '/views/disponibilidad/index.php';
