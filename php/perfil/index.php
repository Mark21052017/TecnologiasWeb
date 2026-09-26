<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['tutor', 'estudiante']);

$user = Auth::user();
$userId = (int) $user['id_usuario'];
$role = (string) $user['nombre_rol'];
$controller = new PerfilController();
$profile = $controller->find($userId);
if (!$profile) {
    http_response_code(404);
    exit('Perfil no encontrado.');
}

$title = 'Mi perfil';
$activePage = 'mi-perfil';
$storagePath = dirname(__DIR__, 2) . '/storage/profile-images';
$data = [
    'nombre' => $profile['nombre'],
    'apellido' => $profile['apellido'],
    'correo' => $profile['correo'],
    'telefono' => $profile['telefono'] ?? '',
    'especialidad' => $profile['especialidad'] ?? '',
    'biografia' => $profile['biografia'] ?? '',
];
$errors = [];
$messages = [
    'updated' => 'Perfil actualizado correctamente.',
    'password' => 'Contrasena actualizada correctamente.',
    'photo' => 'Fotografia actualizada correctamente.',
    'photo-removed' => 'Fotografia eliminada correctamente.',
];
$messageCode = isset($_GET['message']) && is_string($_GET['message']) ? $_GET['message'] : '';
$message = $messages[$messageCode] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La sesion del formulario no es valida. Recargue la pagina.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'personal') {
            [$data, $errors] = $controller->updatePersonalData($userId, $role, $_POST);
            if (!$errors) {
                $_SESSION['user']['nombre'] = $data['nombre'];
                $_SESSION['user']['apellido'] = $data['apellido'];
                $_SESSION['user']['correo'] = $data['correo'];
                header('Location: ' . app_url('mi-perfil/?message=updated'), true, 303);
                exit;
            }
        } elseif ($action === 'password') {
            $errors = $controller->changePassword($userId, $_POST);
            if (!$errors) {
                session_regenerate_id(true);
                header('Location: ' . app_url('mi-perfil/?message=password'), true, 303);
                exit;
            }
        } elseif ($action === 'photo') {
            $photoError = $controller->uploadPhoto($userId, $_FILES['foto_perfil'] ?? [], $storagePath);
            if ($photoError === null) {
                header('Location: ' . app_url('mi-perfil/?message=photo'), true, 303);
                exit;
            }
            $errors[] = $photoError;
        } elseif ($action === 'remove-photo') {
            $photoError = $controller->removePhoto($userId, $storagePath);
            if ($photoError === null) {
                header('Location: ' . app_url('mi-perfil/?message=photo-removed'), true, 303);
                exit;
            }
            $errors[] = $photoError;
        } else {
            $errors[] = 'Accion de perfil no valida.';
        }
    }
}

$profile = $controller->find($userId) ?? $profile;
$_SESSION['user']['foto_perfil'] = $profile['foto_perfil'] ?? null;
$initials = strtoupper(substr((string) $profile['nombre'], 0, 1) . substr((string) $profile['apellido'], 0, 1));
$memberSince = !empty($profile['fecha_registro'])
    ? date('d/m/Y', strtotime((string) $profile['fecha_registro']))
    : '';

require dirname(__DIR__, 2) . '/views/perfil/index.php';
