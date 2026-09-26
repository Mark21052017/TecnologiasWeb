<?php

declare(strict_types=1);

final class PeriodoTutoria
{
    public function all(): array
    {
        return Database::connection()->query(
            "SELECT p.*, t.nombre AS nombre_tipo_tutoria, COUNT(DISTINCT o.id_oferta) AS total_ofertas
             FROM periodos_tutoria p
             LEFT JOIN tipos_tutoria t ON t.id_tipo_tutoria = p.id_tipo_tutoria
             LEFT JOIN ofertas_tutoria o ON o.id_periodo = p.id_periodo
             GROUP BY p.id_periodo, t.nombre
             ORDER BY p.fecha_inicio DESC, p.nombre_periodo"
        )->fetchAll();
    }

    public function options(): array
    {
        return Database::connection()->query(
            "SELECT p.id_periodo, p.nombre_periodo, p.fecha_inicio, p.fecha_fin,
                    p.id_tipo_tutoria, t.nombre AS nombre_tipo_tutoria
             FROM periodos_tutoria p
             LEFT JOIN tipos_tutoria t ON t.id_tipo_tutoria = p.id_tipo_tutoria
             WHERE p.estado IN ('borrador', 'publicado')
             ORDER BY p.fecha_inicio DESC, p.nombre_periodo"
        )->fetchAll();
    }

    public function typeOptions(): array
    {
        return Database::connection()->query(
            "SELECT id_tipo_tutoria, nombre, estado
             FROM tipos_tutoria
             ORDER BY FIELD(estado, 'activo', 'inactivo'), nombre"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            "SELECT p.*, t.nombre AS nombre_tipo_tutoria,
                    (SELECT COUNT(*) FROM ofertas_tutoria o WHERE o.id_periodo = p.id_periodo) AS total_ofertas
             FROM periodos_tutoria p
             LEFT JOIN tipos_tutoria t ON t.id_tipo_tutoria = p.id_tipo_tutoria
             WHERE p.id_periodo = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $period = $statement->fetch();

        return $period ?: null;
    }

    public function activeTypeExists(int $typeId): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1 FROM tipos_tutoria WHERE id_tipo_tutoria = :id_tipo_tutoria AND estado = 'activo' LIMIT 1"
        );
        $statement->execute(['id_tipo_tutoria' => $typeId]);
        return (bool) $statement->fetchColumn();
    }

    public function offerCount(int $id): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM ofertas_tutoria WHERE id_periodo = :id_periodo'
        );
        $statement->execute(['id_periodo' => $id]);
        return (int) $statement->fetchColumn();
    }

    public function create(array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO periodos_tutoria
                (nombre_periodo, id_tipo_tutoria, fecha_inicio, fecha_fin, inscripcion_inicio, inscripcion_fin, estado)
             VALUES (:nombre_periodo, :id_tipo_tutoria, :fecha_inicio, :fecha_fin, :inscripcion_inicio, :inscripcion_fin, :estado)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE periodos_tutoria SET nombre_periodo = :nombre_periodo, id_tipo_tutoria = :id_tipo_tutoria,
                fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin,
                inscripcion_inicio = :inscripcion_inicio, inscripcion_fin = :inscripcion_fin,
                estado = :estado WHERE id_periodo = :id_periodo'
        );
        $data['id_periodo'] = $id;
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM periodos_tutoria WHERE id_periodo = :id_periodo'
        );
        $statement->execute(['id_periodo' => $id]);
    }
}
