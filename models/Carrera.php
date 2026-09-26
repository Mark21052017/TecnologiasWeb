<?php

declare(strict_types=1);

final class Carrera
{
    public function all(): array
    {
        return Database::connection()
            ->query('SELECT id_carrera, nombre_carrera FROM carreras ORDER BY nombre_carrera')
            ->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_carrera, nombre_carrera FROM carreras WHERE id_carrera = :id_carrera'
        );
        $statement->execute(['id_carrera' => $id]);
        $career = $statement->fetch();

        return $career ?: null;
    }

    public function create(string $name): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO carreras (nombre_carrera) VALUES (:nombre_carrera)'
        );
        $statement->execute(['nombre_carrera' => $name]);
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM carreras WHERE nombre_carrera_clave = LOWER(TRIM(:nombre_carrera))';
        $params = ['nombre_carrera' => $name];
        if ($excludeId !== null) {
            $sql .= ' AND id_carrera <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function update(int $id, string $name): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE carreras SET nombre_carrera = :nombre_carrera WHERE id_carrera = :id_carrera'
        );
        $statement->execute([
            'id_carrera' => $id,
            'nombre_carrera' => $name,
        ]);
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM carreras WHERE id_carrera = :id_carrera'
        );
        $statement->execute(['id_carrera' => $id]);
    }
}
