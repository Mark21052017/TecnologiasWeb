<?php

declare(strict_types=1);

final class Tutoria
{
    private function selectSql(): string
    {
        return <<<'SQL'
            SELECT t.id_tutoria, t.id_estudiante, t.id_tutor, t.id_materia,
                   t.fecha, t.hora_inicio, t.hora_fin, t.modalidad,
                   t.lugar_o_enlace, t.estado, t.observaciones, t.fecha_solicitud,
                   CONCAT(eu.nombre, ' ', eu.apellido) AS estudiante,
                   CONCAT(tu.nombre, ' ', tu.apellido) AS tutor,
                   m.nombre_materia
            FROM tutorias t
            INNER JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
            INNER JOIN tutores tr ON tr.id_tutor = t.id_tutor
            INNER JOIN usuarios tu ON tu.id_usuario = tr.id_usuario
            INNER JOIN materias m ON m.id_materia = t.id_materia
        SQL;
    }

    public function allForViewer(string $role, int $userId, array $filters = []): array
    {
        $sql = $this->selectSql();
        $params = [];
        $where = [];
        if ($role === 'estudiante') {
            $where[] = 'eu.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        } elseif ($role === 'tutor') {
            $where[] = 'tu.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        }
        if (!empty($filters['estado'])) {
            $where[] = 't.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['id_materia'])) {
            $where[] = 't.id_materia = :filter_materia';
            $params['filter_materia'] = $filters['id_materia'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = 't.fecha >= :fecha_desde';
            $params['fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = 't.fecha <= :fecha_hasta';
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY t.fecha DESC, t.hora_inicio DESC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function filterOptions(string $role, int $userId): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT m.id_materia, m.nombre_materia
            FROM tutorias t
            INNER JOIN materias m ON m.id_materia = t.id_materia
        SQL;
        $params = [];
        if ($role === 'estudiante') {
            $sql .= ' INNER JOIN estudiantes e ON e.id_estudiante = t.id_estudiante WHERE e.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        } elseif ($role === 'tutor') {
            $sql .= ' INNER JOIN tutores tr ON tr.id_tutor = t.id_tutor WHERE tr.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        }
        $sql .= ' ORDER BY m.nombre_materia';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function findForViewer(int $id, string $role, int $userId): ?array
    {
        $sql = $this->selectSql() . ' WHERE t.id_tutoria = :id_tutoria';
        $params = ['id_tutoria' => $id];
        if ($role === 'estudiante') {
            $sql .= ' AND eu.id_usuario = :id_usuario';
            $params['id_usuario'] = $userId;
        } elseif ($role === 'tutor') {
            $sql .= ' AND tu.id_usuario = :id_usuario';
            $params['id_usuario'] = $userId;
        }
        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        $tutoring = $statement->fetch();

        return $tutoring ?: null;
    }

    public function studentIdByUserId(int $userId): ?int
    {
        $statement = Database::connection()->prepare(
            'SELECT id_estudiante FROM estudiantes WHERE id_usuario = :id_usuario LIMIT 1'
        );
        $statement->execute(['id_usuario' => $userId]);
        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function offerings(): array
    {
        $sql = <<<'SQL'
            SELECT tm.id_tutor, tm.id_materia,
                   CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                   m.nombre_materia,
                   GROUP_CONCAT(
                       DISTINCT CONCAT(d.dia_semana, ' ', TIME_FORMAT(d.hora_inicio, '%H:%i'), '-', TIME_FORMAT(d.hora_fin, '%H:%i'))
                       ORDER BY FIELD(d.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'), d.hora_inicio
                       SEPARATOR ', '
                   ) AS disponibilidad
            FROM tutor_materia tm
            INNER JOIN tutores t ON t.id_tutor = tm.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
            INNER JOIN materias m ON m.id_materia = tm.id_materia
            INNER JOIN disponibilidad_tutor d ON d.id_tutor = tm.id_tutor
            WHERE u.estado = 'activo'
            GROUP BY tm.id_tutor, tm.id_materia, u.nombre, u.apellido, m.nombre_materia
            ORDER BY m.nombre_materia, u.apellido, u.nombre
        SQL;

        return Database::connection()->query($sql)->fetchAll();
    }

    public function create(array $data): void
    {
        $sql = <<<'SQL'
            INSERT INTO tutorias
                (id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin,
                 modalidad, lugar_o_enlace, estado, observaciones)
            SELECT :id_estudiante, tm.id_tutor, tm.id_materia, :fecha, :hora_inicio,
                   :hora_fin, :modalidad, :lugar_o_enlace, 'pendiente', :observaciones
            FROM tutor_materia tm
            INNER JOIN tutores tr ON tr.id_tutor = tm.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = tr.id_usuario
            WHERE tm.id_tutor = :id_tutor AND tm.id_materia = :id_materia
              AND u.estado = 'activo'
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'id_estudiante' => $data['id_estudiante'],
            'id_tutor' => $data['id_tutor'],
            'id_materia' => $data['id_materia'],
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'modalidad' => $data['modalidad'],
            'lugar_o_enlace' => $data['lugar_o_enlace'] !== '' ? $data['lugar_o_enlace'] : null,
            'observaciones' => $data['observaciones'] !== '' ? $data['observaciones'] : null,
        ]);
        if ($statement->rowCount() < 1) {
            throw new RuntimeException('La materia no esta asignada al tutor.');
        }
    }

    public function hasAvailability(int $tutorId, string $date, string $start, string $end): bool
    {
        $sql = <<<'SQL'
            SELECT 1
            FROM disponibilidad_tutor d
            WHERE d.id_tutor = :id_tutor
              AND d.dia_semana = CASE WEEKDAY(:fecha)
                    WHEN 0 THEN 'Lunes'
                    WHEN 1 THEN 'Martes'
                    WHEN 2 THEN 'Miercoles'
                    WHEN 3 THEN 'Jueves'
                    WHEN 4 THEN 'Viernes'
                    WHEN 5 THEN 'Sabado'
                  END
              AND d.hora_inicio <= :hora_inicio
              AND d.hora_fin >= :hora_fin
            LIMIT 1
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'id_tutor' => $tutorId,
            'fecha' => $date,
            'hora_inicio' => $start,
            'hora_fin' => $end,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function hasTutorConflict(int $tutorId, string $date, string $start, string $end): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1 FROM tutorias WHERE id_tutor = :id_tutor AND fecha = :fecha AND estado IN ('pendiente', 'confirmada') AND hora_inicio < :hora_fin AND hora_fin > :hora_inicio LIMIT 1"
        );
        $statement->execute([
            'id_tutor' => $tutorId,
            'fecha' => $date,
            'hora_inicio' => $start,
            'hora_fin' => $end,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function hasStudentConflict(int $studentId, string $date, string $start, string $end): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1 FROM tutorias WHERE id_estudiante = :id_estudiante AND fecha = :fecha AND estado IN ('pendiente', 'confirmada') AND hora_inicio < :hora_fin AND hora_fin > :hora_inicio LIMIT 1"
        );
        $statement->execute([
            'id_estudiante' => $studentId,
            'fecha' => $date,
            'hora_inicio' => $start,
            'hora_fin' => $end,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function changeStatus(int $id, string $status, string $role, int $userId, string $currentState): bool
    {
        $sql = 'UPDATE tutorias SET estado = :estado WHERE id_tutoria = :id_tutoria AND estado = :estado_actual';
        $params = ['estado' => $status, 'id_tutoria' => $id, 'estado_actual' => $currentState];
        if ($role === 'tutor') {
            $sql .= ' AND id_tutor = (SELECT id_tutor FROM tutores WHERE id_usuario = :id_usuario)';
            $params['id_usuario'] = $userId;
        } elseif ($role === 'estudiante') {
            $sql .= ' AND id_estudiante = (SELECT id_estudiante FROM estudiantes WHERE id_usuario = :id_usuario)';
            $params['id_usuario'] = $userId;
        }
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }
}
