<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$viewer = Auth::user();
if (!$viewer) {
    Auth::requireLogin();
}
$viewerRole = (string) ($viewer['nombre_rol'] ?? '');
if ($viewerRole === 'administrador') {
    $targetUserId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($targetUserId === false || $targetUserId === null || $targetUserId < 1) {
        http_response_code(404);
        exit;
    }
} elseif (in_array($viewerRole, ['tutor', 'estudiante'], true)) {
    $targetUserId = (int) $viewer['id_usuario'];
} else {
    http_response_code(403);
    exit;
}

$profile = (new PerfilController())->find((int) $targetUserId);
$filename = $profile['foto_perfil'] ?? null;
if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) {
    http_response_code(404);
    exit;
}

$mimeTypes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mimeType = $mimeTypes[$extension] ?? null;
$path = dirname(__DIR__, 2) . '/storage/profile-images/' . $filename;
if ($mimeType === null || !is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: inline; filename="profile.' . $extension . '"');
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
