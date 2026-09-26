<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('solicitudes_tutor');
$title = 'Solicitudes de tutor';
$activePage = 'solicitudes-tutor';
$solicitudes = (new SolicitudesTutorController())->index();
$message = (string) ($_GET['message'] ?? '') === 'reviewed' ? 'Solicitud actualizada correctamente.' : null;
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/solicitudes-tutor/index.php';
