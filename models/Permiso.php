<?php

declare(strict_types=1);

final class Permiso
{
    public function can(int $userId, string $module): bool
    {
        $sql = <<<'SQL'
            SELECT COALESCE(pr.permitido, 0) AS permitido
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            INNER JOIN modulos_sistema m ON m.clave = :clave AND m.estado = 'activo'
            LEFT JOIN permisos_rol pr ON pr.id_rol = r.id_rol AND pr.id_modulo = m.id_modulo
            WHERE u.id_usuario = :id_usuario AND u.estado = 'activo'
            LIMIT 1
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['clave' => $module, 'id_usuario' => $userId]);

        return (bool) $statement->fetchColumn();
    }

    public function forUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.clave, COALESCE(pr.permitido, 0) AS permitido
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             INNER JOIN modulos_sistema m ON m.estado = \'activo\'
             LEFT JOIN permisos_rol pr ON pr.id_rol = r.id_rol AND pr.id_modulo = m.id_modulo
             WHERE u.id_usuario = :id_usuario AND u.estado = \'activo\''
        );
        $statement->execute(['id_usuario' => $userId]);

        $permissions = [];
        foreach ($statement->fetchAll() as $row) {
            $permissions[(string) $row['clave']] = (bool) $row['permitido'];
        }

        return $permissions;
    }

    public function modules(): array
    {
        return Database::connection()->query(
            'SELECT id_modulo, clave, nombre, descripcion, orden FROM modulos_sistema WHERE estado = \'activo\' ORDER BY orden, nombre'
        )->fetchAll();
    }

    public function roles(): array
    {
        return Database::connection()->query(
            "SELECT id_rol, nombre_rol FROM roles
             WHERE nombre_rol IN ('administrador', 'tutor', 'estudiante')
             ORDER BY FIELD(nombre_rol, 'administrador', 'tutor', 'estudiante')"
        )->fetchAll();
    }

    public function roleMatrix(string $roleName): array
    {
        $role = $this->findRole($roleName);
        if (!$role) {
            throw new InvalidArgumentException('Rol no valido.');
        }

        $modules = $this->modules();
        if ($roleName === 'administrador') {
            foreach ($modules as &$module) {
                $module['permitido'] = 1;
            }
            unset($module);

            return $modules;
        }

        $statement = Database::connection()->prepare(
            'SELECT m.id_modulo, m.clave, m.nombre, m.descripcion, COALESCE(pr.permitido, 0) AS permitido
             FROM modulos_sistema m
             LEFT JOIN permisos_rol pr ON pr.id_modulo = m.id_modulo AND pr.id_rol = :id_rol
             WHERE m.estado = \'activo\'
             ORDER BY m.orden, m.nombre'
        );
        $statement->execute(['id_rol' => (int) $role['id_rol']]);

        return $statement->fetchAll();
    }

    public function saveRolePermissions(string $roleName, mixed $permissions): void
    {
        if (!in_array($roleName, ['tutor', 'estudiante'], true)) {
            throw new RuntimeException('Los permisos del rol administrador estan protegidos.');
        }

        $role = $this->findRole($roleName);
        if (!$role) {
            throw new RuntimeException('El rol no existe.');
        }
        if (!is_array($permissions)) {
            throw new InvalidArgumentException('Los permisos enviados no son validos.');
        }

        $modules = $this->modules();
        $moduleIds = [];
        foreach ($modules as $module) {
            $moduleIds[(string) $module['clave']] = (int) $module['id_modulo'];
        }

        $selected = [];
        foreach ($permissions as $permission) {
            if (!is_string($permission) || !array_key_exists($permission, $moduleIds) || isset($selected[$permission])) {
                throw new InvalidArgumentException('La lista de permisos contiene un modulo no valido.');
            }
            $selected[$permission] = true;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM permisos_rol WHERE id_rol = :id_rol');
            $delete->execute(['id_rol' => (int) $role['id_rol']]);
            $insert = $pdo->prepare(
                'INSERT INTO permisos_rol (id_rol, id_modulo, permitido)
                 VALUES (:id_rol, :id_modulo, :permitido)'
            );
            foreach ($moduleIds as $permission => $moduleId) {
                $insert->execute([
                    'id_rol' => (int) $role['id_rol'],
                    'id_modulo' => $moduleId,
                    'permitido' => isset($selected[$permission]) ? 1 : 0,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function findRole(string $roleName): ?array
    {
        $statement = Database::connection()->prepare(
            "SELECT id_rol, nombre_rol FROM roles
             WHERE nombre_rol IN ('administrador', 'tutor', 'estudiante')
               AND nombre_rol = :nombre_rol
             LIMIT 1"
        );
        $statement->execute(['nombre_rol' => $roleName]);
        $role = $statement->fetch();

        return $role ?: null;
    }
}
