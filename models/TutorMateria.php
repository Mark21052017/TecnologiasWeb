<?php

declare(strict_types=1);

final class TutorMateria
{
    public function all(): array
    {
        $sql = <<<'SQL'
            SELECT tm.id_tutor, tm.id_materia,
                   CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                   m.nombre_materia, c.nombre_carrera
            FROM tutor_materia tm
            INNER JOIN tutores t ON t.id_tutor = tm.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
            INNER JOIN materias m ON m.id_materia = tm.id_materia
            LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
            ORDER BY u.apellido, u.nombre, m.nombre_materia
        SQL;

        return Database::connection()->query($sql)->fetchAll();
    }

    public function tutors(): array
    {
        return Database::connection()->query(
            "SELECT t.id_tutor, CONCAT(u.nombre, ' ', u.apellido) AS tutor FROM tutores t INNER JOIN usuarios u ON u.id_usuario = t.id_usuario WHERE u.estado = 'activo' ORDER BY u.apellido, u.nombre"
        )->fetchAll();
    }

    public function subjects(): array
    {
        return Database::connection()->query(
            'SELECT id_materia, nombre_materia FROM materias ORDER BY nombre_materia'
        )->fetchAll();
    }

    public function create(int $tutorId, int $subjectId): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO tutor_materia (id_tutor, id_materia) VALUES (:id_tutor, :id_materia)'
        );
        $statement->execute(['id_tutor' => $tutorId, 'id_materia' => $subjectId]);
    }

    public function delete(int $tutorId, int $subjectId): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM tutor_materia WHERE id_tutor = :id_tutor AND id_materia = :id_materia'
        );
        $statement->execute(['id_tutor' => $tutorId, 'id_materia' => $subjectId]);
    }

    public function hasActiveTutorings(int $tutorId, int $subjectId): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1 FROM tutorias WHERE id_tutor = :id_tutor AND id_materia = :id_materia AND estado IN ('pendiente', 'confirmada') LIMIT 1"
        );
        $statement->execute(['id_tutor' => $tutorId, 'id_materia' => $subjectId]);

        return (bool) $statement->fetchColumn();
    }
}
