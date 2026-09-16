<?php

declare(strict_types=1);

final class Permiso
{
    public function can(int $userId, string $module): bool
    {
        $sql = <<<'SQL'
            SELECT COALESCE(pu.permitido, pr.permitido, 0) AS permitido
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            INNER JOIN modulos_sistema m ON m.clave = :clave AND m.estado = 'activo'
            LEFT JOIN permisos_rol pr ON pr.id_rol = r.id_rol AND pr.id_modulo = m.id_modulo
            LEFT JOIN permisos_usuario pu ON pu.id_usuario = u.id_usuario AND pu.id_modulo = m.id_modulo
            WHERE u.id_usuario = :id_usuario AND u.estado = 'activo'
            LIMIT 1
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['clave' => $module, 'id_usuario' => $userId]);

        return (bool) $statement->fetchColumn();
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
            'SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol'
        )->fetchAll();
    }

    public function users(): array
    {
        return Database::connection()->query(
            'SELECT id_usuario, nombre, apellido, usuario, nombre_rol FROM usuarios INNER JOIN roles USING (id_rol) ORDER BY apellido, nombre'
        )->fetchAll();
    }

    public function roleMatrix(int $roleId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id_modulo, m.clave, m.nombre, m.descripcion, COALESCE(pr.permitido, 0) AS permitido FROM modulos_sistema m LEFT JOIN permisos_rol pr ON pr.id_modulo = m.id_modulo AND pr.id_rol = :id_rol WHERE m.estado = \'activo\' ORDER BY m.orden, m.nombre'
        );
        $statement->execute(['id_rol' => $roleId]);

        return $statement->fetchAll();
    }

    public function userMatrix(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.id_modulo, m.clave, m.nombre, m.descripcion, COALESCE(pr.permitido, 0) AS rol_permitido, pu.permitido AS usuario_permitido FROM modulos_sistema m INNER JOIN usuarios u ON u.id_usuario = :id_usuario LEFT JOIN permisos_rol pr ON pr.id_rol = u.id_rol AND pr.id_modulo = m.id_modulo LEFT JOIN permisos_usuario pu ON pu.id_usuario = u.id_usuario AND pu.id_modulo = m.id_modulo WHERE m.estado = \'activo\' ORDER BY m.orden, m.nombre'
        );
        $statement->execute(['id_usuario' => $userId]);

        return $statement->fetchAll();
    }

    public function saveRolePermissions(int $roleId, array $permissions): void
    {
        $this->savePermissions('permisos_rol', 'id_rol', $roleId, $permissions);
    }

    public function saveUserPermissions(int $userId, array $permissions): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM permisos_usuario WHERE id_usuario = :id_usuario');
            $delete->execute(['id_usuario' => $userId]);
            $insert = $pdo->prepare('INSERT INTO permisos_usuario (id_usuario, id_modulo, permitido) VALUES (:id_usuario, :id_modulo, :permitido)');
            foreach ($permissions as $moduleId => $permission) {
                if (!in_array($permission, ['permitir', 'denegar'], true)) {
                    continue;
                }
                $insert->execute([
                    'id_usuario' => $userId,
                    'id_modulo' => (int) $moduleId,
                    'permitido' => $permission === 'permitir' ? 1 : 0,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function savePermissions(string $table, string $key, int $keyValue, array $permissions): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare("DELETE FROM {$table} WHERE {$key} = :key_value");
            $delete->execute(['key_value' => $keyValue]);
            $insert = $pdo->prepare("INSERT INTO {$table} ({$key}, id_modulo, permitido) VALUES (:key_value, :id_modulo, :permitido)");
            foreach ($permissions as $moduleId => $allowed) {
                $insert->execute([
                    'key_value' => $keyValue,
                    'id_modulo' => (int) $moduleId,
                    'permitido' => $allowed === '1' ? 1 : 0,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
