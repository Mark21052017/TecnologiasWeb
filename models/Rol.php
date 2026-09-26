<?php

declare(strict_types=1);

final class Rol
{
    private const PROTECTED_ADMIN_ROLE_ID = 1;

    public function all(): array
    {
        return Database::connection()
            ->query('SELECT id_rol, nombre_rol FROM roles ORDER BY id_rol')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_rol, nombre_rol FROM roles WHERE id_rol = :id_rol'
        );
        $statement->execute(['id_rol' => $id]);
        $role = $statement->fetch();

        return $role ?: null;
    }

    public function create(string $name): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO roles (nombre_rol) VALUES (:nombre_rol)'
        );
        $statement->execute(['nombre_rol' => $name]);
    }

    public function update(int $id, string $name): void
    {
        if ($this->isProtectedAdminRole($id)) {
            throw new RuntimeException('El rol administrador esta protegido y no puede modificarse.');
        }

        $statement = Database::connection()->prepare(
            'UPDATE roles SET nombre_rol = :nombre_rol WHERE id_rol = :id_rol'
        );
        $statement->execute([
            'id_rol' => $id,
            'nombre_rol' => $name,
        ]);
    }

    public function delete(int $id): void
    {
        if ($this->isProtectedAdminRole($id)) {
            throw new RuntimeException('El rol administrador esta protegido y no puede eliminarse.');
        }

        $statement = Database::connection()->prepare(
            'DELETE FROM roles WHERE id_rol = :id_rol'
        );
        $statement->execute(['id_rol' => $id]);
    }

    public function isProtectedAdminRole(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM roles WHERE id_rol = :id_rol AND (id_rol = :protected_id OR nombre_rol = :admin_name) LIMIT 1'
        );
        $statement->execute([
            'id_rol' => $id,
            'protected_id' => self::PROTECTED_ADMIN_ROLE_ID,
            'admin_name' => 'administrador',
        ]);

        return (bool) $statement->fetchColumn();
    }
}
