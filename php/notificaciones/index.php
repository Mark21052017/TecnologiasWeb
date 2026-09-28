<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireLogin();

$controller = new NotificacionesController();
$userId = (int) Auth::user()['id_usuario'];
$errors = [];
$message = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $action = (string) ($_POST['accion'] ?? '');
    if ($action === 'marcar_todas') {
        $controller->markAllRead($userId);
        $message = 'Todas las notificaciones quedaron marcadas como leídas.';
    } elseif ($action === 'marcar_leida') {
        $id = filter_var($_POST['id_notificacion'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || !$controller->markRead((int) $id, $userId)) {
            $errors[] = 'La notificación no existe o no pertenece a esta cuenta.';
        } else {
            $message = 'Notificación marcada como leída.';
        }
    } else {
        $errors[] = 'Seleccione una acción válida.';
    }
}

$notifications = $controller->latest($userId);
$unread = $controller->unreadCount($userId);
$title = 'Notificaciones';
$activePage = 'notificaciones';
require dirname(__DIR__, 2) . '/views/notificaciones/index.php';
