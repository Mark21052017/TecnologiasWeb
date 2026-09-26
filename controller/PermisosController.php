<?php

declare(strict_types=1);

final class PermisosController
{
    private const ROLES = ['administrador', 'tutor', 'estudiante'];

    private Permiso $model;

    public function __construct()
    {
        $this->model = new Permiso();
    }

    public function data(?string $roleName): array
    {
        $roles = $this->model->roles();
        $selectedRole = $this->isSupportedRole($roleName) ? $roleName : 'administrador';

        return [
            'modules' => $this->model->modules(),
            'roles' => $roles,
            'role_name' => $selectedRole,
            'role_matrix' => $this->model->roleMatrix($selectedRole),
        ];
    }

    public function isSupportedRole(?string $roleName): bool
    {
        return is_string($roleName) && in_array($roleName, self::ROLES, true);
    }

    public function saveRole(?string $roleName, array $input): ?string
    {
        if (!$this->isSupportedRole($roleName)) {
            return 'Rol no valido.';
        }
        if ($roleName === 'administrador') {
            return 'Los permisos del rol administrador estan protegidos.';
        }

        try {
            $this->model->saveRolePermissions($roleName, $input['permiso'] ?? []);
            return null;
        } catch (InvalidArgumentException | RuntimeException $exception) {
            return $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No fue posible guardar los permisos del rol.';
        }
    }
}
