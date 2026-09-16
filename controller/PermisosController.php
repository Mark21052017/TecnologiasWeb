<?php

declare(strict_types=1);

final class PermisosController
{
    private Permiso $model;

    public function __construct()
    {
        $this->model = new Permiso();
    }

    public function data(?int $roleId, ?int $userId): array
    {
        $roles = $this->model->roles();
        $users = $this->model->users();
        $roleId = $roleId ?: (int) ($roles[0]['id_rol'] ?? 0);
        $userId = $userId ?: (int) ($users[0]['id_usuario'] ?? 0);

        return [
            'modules' => $this->model->modules(),
            'roles' => $roles,
            'users' => $users,
            'role_id' => $roleId,
            'user_id' => $userId,
            'role_matrix' => $roleId ? $this->model->roleMatrix($roleId) : [],
            'user_matrix' => $userId ? $this->model->userMatrix($userId) : [],
        ];
    }

    public function saveRole(int $roleId, array $input): ?string
    {
        try {
            (new Permiso())->saveRolePermissions($roleId, $input['permiso'] ?? []);
            return null;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No fue posible guardar los permisos del rol.';
        }
    }

    public function saveUser(int $userId, array $input): ?string
    {
        try {
            (new Permiso())->saveUserPermissions($userId, $input['permiso'] ?? []);
            return null;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No fue posible guardar los permisos del usuario.';
        }
    }
}
