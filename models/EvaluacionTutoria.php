<?php

declare(strict_types=1);

final class EvaluacionTutoria
{
    public function allForViewer(string $role, int $userId, array $filters = []): array
    {
        $sql = <<<'SQL'
            SELECT ev.id_evaluacion, ev.id_tutoria, ev.calificacion,
                   ev.comentario, ev.fecha_evaluacion, tu.fecha,
                   m.nombre_materia,
                   CONCAT(eu.nombre, ' ', eu.apellido) AS estudiante,
                   CONCAT(tu_user.nombre, ' ', tu_user.apellido) AS tutor
            FROM evaluaciones_tutoria ev
            INNER JOIN tutorias tu ON tu.id_tutoria = ev.id_tutoria
            INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante
            INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
            INNER JOIN tutores tr ON tr.id_tutor = tu.id_tutor
            INNER JOIN usuarios tu_user ON tu_user.id_usuario = tr.id_usuario
            INNER JOIN materias m ON m.id_materia = tu.id_materia
        SQL;
        $params = [];
        $where = [];
        if ($role === 'estudiante') {
            $where[] = 'eu.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        } elseif ($role === 'tutor') {
            $where[] = 'tu_user.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        }
        if (!empty($filters['id_materia'])) {
            $where[] = 'tu.id_materia = :filter_materia';
            $params['filter_materia'] = $filters['id_materia'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = 'tu.fecha >= :fecha_desde';
            $params['fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = 'tu.fecha <= :fecha_hasta';
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ev.fecha_evaluacion DESC';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function filterOptions(string $role, int $userId): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT m.id_materia, m.nombre_materia
            FROM evaluaciones_tutoria ev
            INNER JOIN tutorias tu ON tu.id_tutoria = ev.id_tutoria
            INNER JOIN materias m ON m.id_materia = tu.id_materia
        SQL;
        $params = [];
        if ($role === 'estudiante') {
            $sql .= ' INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante WHERE e.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        } elseif ($role === 'tutor') {
            $sql .= ' INNER JOIN tutores t ON t.id_tutor = tu.id_tutor WHERE t.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        }
        $sql .= ' ORDER BY m.nombre_materia';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function summaryForViewer(string $role, int $userId): array
    {
        $sql = <<<'SQL'
            SELECT COUNT(ev.id_evaluacion) AS total_evaluaciones,
                   COALESCE(ROUND(AVG(ev.calificacion), 2), 0) AS promedio_calificacion
            FROM evaluaciones_tutoria ev
            INNER JOIN tutorias tu ON tu.id_tutoria = ev.id_tutoria
            INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante
            INNER JOIN tutores tr ON tr.id_tutor = tu.id_tutor
        SQL;
        $params = [];
        if ($role === 'estudiante') {
            $sql .= ' WHERE e.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        } elseif ($role === 'tutor') {
            $sql .= ' WHERE tr.id_usuario = :viewer_id';
            $params['viewer_id'] = $userId;
        }
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return $statement->fetch() ?: ['total_evaluaciones' => 0, 'promedio_calificacion' => 0];
    }

    public function eligibleForStudent(int $userId): array
    {
        $sql = <<<'SQL'
            SELECT tu.id_tutoria, tu.fecha, m.nombre_materia,
                   CONCAT(u.nombre, ' ', u.apellido) AS tutor
            FROM tutorias tu
            INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante
            INNER JOIN tutores t ON t.id_tutor = tu.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
            INNER JOIN materias m ON m.id_materia = tu.id_materia
            LEFT JOIN evaluaciones_tutoria ev ON ev.id_tutoria = tu.id_tutoria
            WHERE e.id_usuario = :id_usuario
              AND tu.estado = 'realizada'
              AND ev.id_evaluacion IS NULL
            ORDER BY tu.fecha DESC
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id_usuario' => $userId]);

        return $statement->fetchAll();
    }

    public function create(int $tutoriaId, int $userId, int $rating, string $comment): void
    {
        $sql = <<<'SQL'
            INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
            SELECT tu.id_tutoria, :calificacion, :comentario
            FROM tutorias tu
            INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante
            WHERE tu.id_tutoria = :id_tutoria
              AND e.id_usuario = :id_usuario
              AND tu.estado = 'realizada'
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute([
            'id_tutoria' => $tutoriaId,
            'id_usuario' => $userId,
            'calificacion' => $rating,
            'comentario' => $comment !== '' ? $comment : null,
        ]);
        if ($statement->rowCount() < 1) {
            throw new RuntimeException('La tutoria no puede ser evaluada.');
        }
    }
}
