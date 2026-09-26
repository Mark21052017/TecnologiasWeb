<?php

declare(strict_types=1);

final class DisponibilidadTutor
{
    public function all(?int $tutorId = null): array
    {
        $sql = <<<'SQL'
            SELECT d.id_disponibilidad, d.id_tutor, d.dia_semana,
                   d.id_turno, b.nombre_turno, b.hora_inicio, b.hora_fin,
                   CONCAT(u.nombre, ' ', u.apellido) AS tutor
            FROM disponibilidad_tutor d
            INNER JOIN turnos b ON b.id_turno = d.id_turno AND b.estado = 'activo'
            INNER JOIN tutores t ON t.id_tutor = d.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
        SQL;
        $params = [];
        if ($tutorId !== null) {
            $sql .= ' WHERE d.id_tutor = :id_tutor';
            $params['id_tutor'] = $tutorId;
        }
        $sql .= " ORDER BY FIELD(d.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'), b.hora_inicio";
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT d.id_disponibilidad, d.id_tutor, d.dia_semana, d.id_turno, b.nombre_turno, b.hora_inicio, b.hora_fin, CONCAT(u.nombre, \' \', u.apellido) AS tutor FROM disponibilidad_tutor d INNER JOIN turnos b ON b.id_turno = d.id_turno AND b.estado = \'activo\' INNER JOIN tutores t ON t.id_tutor = d.id_tutor INNER JOIN usuarios u ON u.id_usuario = t.id_usuario WHERE d.id_disponibilidad = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $availability = $statement->fetch();

        return $availability ?: null;
    }

    public function tutors(): array
    {
        return Database::connection()->query(
            "SELECT t.id_tutor, CONCAT(u.nombre, ' ', u.apellido) AS tutor FROM tutores t INNER JOIN usuarios u ON u.id_usuario = t.id_usuario WHERE u.estado = 'activo' ORDER BY u.apellido, u.nombre"
        )->fetchAll();
    }

    public function create(array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO disponibilidad_tutor (id_tutor, dia_semana, id_turno) VALUES (:id_tutor, :dia_semana, :id_turno)'
        );
        $statement->execute($data);
    }

    public function overlaps(array $data, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM disponibilidad_tutor WHERE id_tutor = :id_tutor AND dia_semana = :dia_semana AND id_turno = :id_turno';
        $params = [
            'id_tutor' => $data['id_tutor'],
            'dia_semana' => $data['dia_semana'],
            'id_turno' => $data['id_turno'],
        ];
        if ($excludeId !== null) {
            $sql .= ' AND id_disponibilidad <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return (bool) $statement->fetchColumn();
    }

    public function overlapsActiveTutoring(array $data): bool
    {
        $sql = <<<'SQL'
            SELECT 1
            FROM tutorias t
            INNER JOIN turnos b ON b.hora_inicio = t.hora_inicio AND b.hora_fin = t.hora_fin
            WHERE t.id_tutor = :id_tutor
              AND t.estado IN ('pendiente', 'confirmada')
              AND CASE WEEKDAY(t.fecha)
                    WHEN 0 THEN 'Lunes'
                    WHEN 1 THEN 'Martes'
                    WHEN 2 THEN 'Miercoles'
                    WHEN 3 THEN 'Jueves'
                    WHEN 4 THEN 'Viernes'
                    WHEN 5 THEN 'Sabado'
                  END = :dia_semana
              AND b.id_turno = :id_turno
            LIMIT 1
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'id_tutor' => $data['id_tutor'],
            'dia_semana' => $data['dia_semana'],
            'id_turno' => $data['id_turno'],
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE disponibilidad_tutor SET id_tutor = :id_tutor, dia_semana = :dia_semana, id_turno = :id_turno WHERE id_disponibilidad = :id_disponibilidad'
        );
        $data['id_disponibilidad'] = $id;
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM disponibilidad_tutor WHERE id_disponibilidad = :id_disponibilidad'
        );
        $statement->execute(['id_disponibilidad' => $id]);
    }
}
