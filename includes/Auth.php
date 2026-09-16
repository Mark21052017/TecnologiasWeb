<?php

declare(strict_types=1);

final class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        unset($user['contrasena_hash']);
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parameters['path'], $parameters['domain'], $parameters['secure'], $parameters['httponly']);
        }

        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id_usuario']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: ' . app_url('login.php'));
            exit;
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        if ((self::user()['nombre_rol'] ?? null) !== $role) {
            http_response_code(403);
            exit('No tiene permisos para acceder a esta pagina.');
        }
    }

    public static function requireAnyRole(array $roles): void
    {
        self::requireLogin();

        if (!in_array(self::user()['nombre_rol'] ?? null, $roles, true)) {
            http_response_code(403);
            exit('No tiene permisos para acceder a esta pagina.');
        }
    }

    public static function can(string $module): bool
    {
        if (!self::check()) {
            return false;
        }

        if ((self::user()['nombre_rol'] ?? '') === 'administrador') {
            return true;
        }

        return (new Permiso())->can((int) self::user()['id_usuario'], $module);
    }

    public static function requireModule(string $module): void
    {
        self::requireLogin();

        if (!self::can($module)) {
            http_response_code(403);
            exit('No tiene permisos para acceder a este modulo.');
        }
    }
}
