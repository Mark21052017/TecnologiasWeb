<?php

declare(strict_types=1);

final class Aula
{
    public function all(bool $includeInactive = true): array
    {
        $sql = 'SELECT * FROM aulas';
        if (!$includeInactive) {
            $sql .= " WHERE estado = 'activa'";
        }
        $sql .= ' ORDER BY nombre_aula';

        return Database::connection()->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM aulas WHERE id_aula = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $room = $statement->fetch();

        return $room ?: null;
    }

    public function create(array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO aulas (nombre_aula, ubicacion, capacidad, estado)
             VALUES (:nombre_aula, :ubicacion, :capacidad, :estado)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE aulas SET nombre_aula = :nombre_aula,
                ubicacion = :ubicacion, capacidad = :capacidad, estado = :estado
             WHERE id_aula = :id_aula'
        );
        $data['id_aula'] = $id;
        $statement->execute($data);
    }
}
