<?php

declare(strict_types=1);

final class TipoTutoria
{
    public function all(): array
    {
        return Database::connection()
            ->query('SELECT id_tipo_tutoria, nombre, descripcion, estado FROM tipos_tutoria ORDER BY nombre')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_tipo_tutoria, nombre, descripcion, estado FROM tipos_tutoria WHERE id_tipo_tutoria = :id_tipo_tutoria LIMIT 1'
        );
        $statement->execute(['id_tipo_tutoria' => $id]);
        $type = $statement->fetch();

        return $type ?: null;
    }

    public function create(string $name, string $description): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO tipos_tutoria (nombre, descripcion, estado)
             VALUES (:nombre, :descripcion, \'activo\')'
        );
        $statement->execute([
            'nombre' => $name,
            'descripcion' => $description !== '' ? $description : null,
        ]);
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM tipos_tutoria WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))';
        $params = ['nombre' => $name];
        if ($excludeId !== null) {
            $sql .= ' AND id_tipo_tutoria <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function update(int $id, string $name, string $description, string $state): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE tipos_tutoria
             SET nombre = :nombre, descripcion = :descripcion, estado = :estado
             WHERE id_tipo_tutoria = :id_tipo_tutoria'
        );
        $statement->execute([
            'id_tipo_tutoria' => $id,
            'nombre' => $name,
            'descripcion' => $description !== '' ? $description : null,
            'estado' => $state,
        ]);
    }

    public function deactivate(int $id): void
    {
        $statement = Database::connection()->prepare(
            "UPDATE tipos_tutoria SET estado = 'inactivo' WHERE id_tipo_tutoria = :id_tipo_tutoria"
        );
        $statement->execute(['id_tipo_tutoria' => $id]);
    }
}
