<?php

declare(strict_types=1);

final class CatalogoEstudiante
{
    public function materias(): array
    {
        $sql = <<<'SQL'
            SELECT m.id_materia, m.nombre_materia, c.nombre_carrera,
                   COUNT(DISTINCT tm.id_tutor) AS total_tutores
            FROM materias m
            LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
            INNER JOIN tutor_materia tm ON tm.id_materia = m.id_materia
            INNER JOIN tutores t ON t.id_tutor = tm.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
            GROUP BY m.id_materia, m.nombre_materia, c.nombre_carrera
            ORDER BY m.nombre_materia
        SQL;

        return Database::connection()->query($sql)->fetchAll();
    }

    public function tutores(): array
    {
        $sql = <<<'SQL'
            SELECT t.id_tutor, CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                   t.especialidad, t.biografia,
                   GROUP_CONCAT(DISTINCT m.nombre_materia ORDER BY m.nombre_materia SEPARATOR ', ') AS materias,
                    COUNT(DISTINCT d.id_disponibilidad) AS total_horarios
            FROM tutores t
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
            INNER JOIN tutor_materia tm ON tm.id_tutor = t.id_tutor
            INNER JOIN materias m ON m.id_materia = tm.id_materia
              LEFT JOIN disponibilidad_tutor d ON d.id_tutor = t.id_tutor AND d.id_turno IS NOT NULL
            GROUP BY t.id_tutor, u.nombre, u.apellido, t.especialidad, t.biografia
            ORDER BY u.apellido, u.nombre
        SQL;

        return Database::connection()->query($sql)->fetchAll();
    }

    public function availability(): array
    {
        $sql = <<<'SQL'
            SELECT d.id_disponibilidad, d.dia_semana, b.id_turno, b.nombre_turno, b.hora_inicio, b.hora_fin,
                   CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                   m.nombre_materia
            FROM disponibilidad_tutor d
            INNER JOIN turnos b ON b.id_turno = d.id_turno AND b.estado = 'activo'
            INNER JOIN tutores t ON t.id_tutor = d.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
            INNER JOIN tutor_materia tm ON tm.id_tutor = t.id_tutor
            INNER JOIN materias m ON m.id_materia = tm.id_materia
            ORDER BY FIELD(d.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'), b.hora_inicio, tutor
        SQL;

        return Database::connection()->query($sql)->fetchAll();
    }
}
