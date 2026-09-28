<?php

require dirname(__DIR__) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
$activePage = 'usuarios';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$returnTo = in_array((string) ($_GET['return_to'] ?? ''), ['usuarios', 'estudiantes', 'tutores'], true)
    ? (string) $_GET['return_to']
    : 'usuarios';
$validReturnRoleFilters = ['estudiante', 'tutor', 'otros'];
$returnRoleFilter = $returnTo === 'usuarios'
    && isset($_GET['return_role'])
    && is_string($_GET['return_role'])
    && in_array($_GET['return_role'], $validReturnRoleFilters, true)
        ? $_GET['return_role']
        : '';
$controller = new UsuariosController();
$account = $id ? $controller->find($id) : null;

if (!$account) {
    http_response_code(404);
    exit('Usuario no encontrado.');
}

if ($controller->isProtectedAdmin((int) $account['id_usuario'])) {
    http_response_code(403);
    exit('La cuenta admin esta protegida y no puede modificarse.');
}

$roles = $controller->roles();
$careers = $controller->careers();
$targetUserId = (int) $account['id_usuario'];
$returnPath = $returnTo . '/';
if ($returnTo === 'usuarios' && $returnRoleFilter !== '') {
    $returnPath .= '?rol=' . rawurlencode($returnRoleFilter);
}
$returnUrl = app_url($returnPath);
$editUrl = app_url('usuarios/edit.php?id=' . $targetUserId . '&return_to=' . rawurlencode($returnTo)
    . ($returnRoleFilter !== '' ? '&return_role=' . rawurlencode($returnRoleFilter) : ''));
$profileController = new PerfilController();
$storagePath = dirname(__DIR__) . '/storage/profile-images';
$canManagePhoto = ($account['nombre_rol'] === 'estudiante' && !empty($account['id_estudiante']))
    || ($account['nombre_rol'] === 'tutor' && !empty($account['id_tutor']));
$data = [
    'id_usuario' => $account['id_usuario'],
    'id_rol' => (string) $account['id_rol'],
    'nombre' => $account['nombre'],
    'apellido' => $account['apellido'],
    'correo' => $account['correo'],
    'usuario' => $account['usuario'],
    'contrasena' => '',
    'telefono' => $account['telefono'] ?? '',
    'estado' => $account['estado'],
    'id_carrera' => (string) ($account['id_carrera'] ?? ''),
    'semestre' => (string) ($account['semestre'] ?? ''),
    'especialidad' => $account['especialidad'] ?? '',
    'biografia' => $account['biografia'] ?? '',
];
$errors = [];
$messages = [
    'photo' => 'Fotografía de perfil actualizada correctamente.',
    'photo-removed' => 'Fotografía de perfil eliminada correctamente.',
];
$message = $messages[(string) ($_GET['message'] ?? '')] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } elseif (($_POST['action'] ?? '') === 'photo') {
        if (!$canManagePhoto) {
            $errors[] = 'La foto solo está disponible para perfiles de estudiante y tutor.';
        } else {
            $photoError = $profileController->uploadPhoto($targetUserId, $_FILES['foto_perfil'] ?? [], $storagePath, false);
            if ($photoError === null) {
                header('Location: ' . $editUrl . '&message=photo', true, 303);
                exit;
            }
            $errors[] = $photoError;
        }
    } elseif (($_POST['action'] ?? '') === 'remove-photo') {
        if (!$canManagePhoto) {
            $errors[] = 'La foto solo está disponible para perfiles de estudiante y tutor.';
        } else {
            $photoError = $profileController->removePhoto($targetUserId, $storagePath, false);
            if ($photoError === null) {
                header('Location: ' . $editUrl . '&message=photo-removed', true, 303);
                exit;
            }
            $errors[] = $photoError;
        }
    } else {
        [$data, $errors] = $controller->update($id, $_POST);
        $data['id_usuario'] = $id;
        if (!$errors) {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';
            header('Location: ' . $returnUrl . $separator . 'message=updated', true, 303);
            exit;
        }
    }
}

$mode = 'edit';
require dirname(__DIR__) . '/views/usuarios/form.php';
