<?php

declare(strict_types=1);

final class Dashboard
{
    public function summary(string $role, int $userId): array
    {
        if ($role === 'tutor') {
            $sql = <<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM tutor_materia tm INNER JOIN tutores t ON t.id_tutor = tm.id_tutor WHERE t.id_usuario = :tutor_user) AS materias_asignadas,
                    (SELECT COUNT(*) FROM tutorias tu INNER JOIN tutores t ON t.id_tutor = tu.id_tutor WHERE t.id_usuario = :tutor_pending_user AND tu.estado = 'pendiente') AS tutorias_pendientes,
                    (SELECT COUNT(*) FROM tutorias tu INNER JOIN tutores t ON t.id_tutor = tu.id_tutor WHERE t.id_usuario = :tutor_confirmed_user AND tu.estado = 'confirmada' AND tu.fecha >= CURRENT_DATE) AS tutorias_confirmadas,
                    (SELECT COALESCE(ROUND(AVG(ev.calificacion), 2), 0) FROM evaluaciones_tutoria ev INNER JOIN tutorias tu ON tu.id_tutoria = ev.id_tutoria INNER JOIN tutores t ON t.id_tutor = tu.id_tutor WHERE t.id_usuario = :tutor_rating_user) AS promedio_calificacion
            SQL;
            $params = [
                'tutor_user' => $userId,
                'tutor_pending_user' => $userId,
                'tutor_confirmed_user' => $userId,
                'tutor_rating_user' => $userId,
            ];
        } elseif ($role === 'estudiante') {
            $sql = <<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM tutorias tu INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante WHERE e.id_usuario = :student_pending_user AND tu.estado = 'pendiente') AS tutorias_pendientes,
                    (SELECT COUNT(*) FROM tutorias tu INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante WHERE e.id_usuario = :student_confirmed_user AND tu.estado = 'confirmada' AND tu.fecha >= CURRENT_DATE) AS tutorias_confirmadas,
                    (SELECT COUNT(*) FROM tutorias tu INNER JOIN estudiantes e ON e.id_estudiante = tu.id_estudiante LEFT JOIN evaluaciones_tutoria ev ON ev.id_tutoria = tu.id_tutoria WHERE e.id_usuario = :student_evaluation_user AND tu.estado = 'realizada' AND ev.id_evaluacion IS NULL) AS evaluaciones_pendientes,
                    (SELECT COUNT(DISTINCT tm.id_materia) FROM tutor_materia tm INNER JOIN tutores t ON t.id_tutor = tm.id_tutor INNER JOIN usuarios u ON u.id_usuario = t.id_usuario WHERE u.estado = 'activo') AS materias_disponibles
            SQL;
            $params = [
                'student_pending_user' => $userId,
                'student_confirmed_user' => $userId,
                'student_evaluation_user' => $userId,
            ];
        } else {
            $sql = <<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
                    (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') AS usuarios_activos,
                    (SELECT COUNT(*) FROM estudiantes) AS total_estudiantes,
                    (SELECT COUNT(*) FROM tutores) AS total_tutores,
                    (SELECT COUNT(*) FROM carreras) AS total_carreras,
                    (SELECT COUNT(*) FROM materias) AS total_materias,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'pendiente') AS tutorias_pendientes,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'confirmada') AS tutorias_confirmadas
            SQL;
            $params = [];
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $summary = $statement->fetch();

        return $summary ?: [];
    }
}
