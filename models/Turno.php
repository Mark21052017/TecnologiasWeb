<?php

declare(strict_types=1);

final class Turno
{
    public function all(bool $includeInactive = true): array
    {
        $sql = 'SELECT * FROM turnos';
        if (!$includeInactive) {
            $sql .= " WHERE estado = 'activo'";
        }
        $sql .= ' ORDER BY hora_inicio, hora_fin';

        return Database::connection()->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM turnos WHERE id_turno = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $block = $statement->fetch();

        return $block ?: null;
    }

    public function create(array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO turnos (nombre_turno, hora_inicio, hora_fin, estado)
             VALUES (:nombre_turno, :hora_inicio, :hora_fin, :estado)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $current = $this->find($id);
        if (!$current) {
            throw new RuntimeException('El turno no existe.');
        }
        $currentStart = substr((string) $current['hora_inicio'], 0, 5);
        $currentEnd = substr((string) $current['hora_fin'], 0, 5);
        if (($currentStart !== $data['hora_inicio'] || $currentEnd !== $data['hora_fin']) && $this->isUsed($id)) {
            throw new RuntimeException('No se pueden cambiar las horas de un turno utilizado por una oferta. Cree un turno nuevo para ese horario.');
        }

        $statement = Database::connection()->prepare(
            'UPDATE turnos SET nombre_turno = :nombre_turno,
                hora_inicio = :hora_inicio, hora_fin = :hora_fin, estado = :estado
             WHERE id_turno = :id_turno'
        );
        $data['id_turno'] = $id;
        $statement->execute($data);
    }

    public function isUsed(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM ofertas_tutoria WHERE id_turno = :id_turno'
        );
        $statement->execute(['id_turno' => $id]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM turnos WHERE id_turno = :id_turno'
        );
        $statement->execute(['id_turno' => $id]);
    }
}
