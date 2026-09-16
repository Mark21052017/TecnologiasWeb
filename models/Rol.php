<?php

declare(strict_types=1);

final class Rol
{
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
        $statement = Database::connection()->prepare(
            'DELETE FROM roles WHERE id_rol = :id_rol'
        );
        $statement->execute(['id_rol' => $id]);
    }
}
