<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['tutor', 'estudiante']);

$profile = (new PerfilController())->find((int) Auth::user()['id_usuario']);
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
