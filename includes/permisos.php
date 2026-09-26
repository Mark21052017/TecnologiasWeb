<?php

declare(strict_types=1);

function requerirRol(string|array $roles): void
{
    Auth::requireAnyRole(is_array($roles) ? $roles : [$roles]);
}

function requerirPermiso(string $permission): void
{
    Auth::requireLogin();
    $user = Auth::user();
    if (!$user || !Auth::can('modalidades-grado')) {
        http_response_code(403);
        exit('No tiene permisos para acceder a Modalidades de Grado.');
    }

    if (($user['nombre_rol'] ?? '') === 'administrador') {
        return;
    }

    if (!(new MgPermiso())->userHasPermission((int) $user['id_usuario'], $permission)) {
        http_response_code(403);
        exit('No tiene el permiso requerido para esta operación de Modalidades de Grado.');
    }
}

function csrf_validar(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('La solicitud no es válida o la sesión del formulario expiró.');
    }
}
