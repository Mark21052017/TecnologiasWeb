<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
$user = Auth::user();
$role = (string) ($user['nombre_rol'] ?? '');
if ($role === 'administrador') {
    requerirPermiso('mg.seguimiento.gestionar');
} elseif ($role === 'tutor') {
    requerirPermiso('mg.informes.revisar');
} elseif ($role === 'estudiante') {
    requerirPermiso('mg.informes.propios');
} else {
    http_response_code(403);
    exit('No tiene acceso a informes MG.');
}

$versionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($versionId === false || $versionId === null || $versionId < 1) {
    http_response_code(404);
    exit;
}
try {
    $file = (new MgSeguimientoController())->download((int) $versionId, (int) $user['id_usuario'], $role);
} catch (Throwable $exception) {
    http_response_code($exception instanceof RuntimeException ? 403 : 404);
    exit;
}
header('Content-Type: application/pdf');
header('Content-Length: ' . (string) $file['size']);
header('Content-Disposition: attachment; filename="informe.pdf"; filename*=UTF-8\'\'' . rawurlencode($file['name']));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($file['path']);
exit;
