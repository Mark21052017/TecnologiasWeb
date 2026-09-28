<?php

declare(strict_types=1);

final class Tutoria
{
    private function selectSql(): string
    {
        return <<<'SQL'
            SELECT t.id_tutoria, t.id_inscripcion, t.id_estudiante, t.id_tutor, t.id_materia,
                   t.fecha, t.hora_inicio, t.hora_fin, t.modalidad,
                   t.lugar_o_enlace, t.estado, t.observaciones, t.fecha_solicitud,
                    COALESCE(oh.dia_semana, CASE DAYOFWEEK(t.fecha)
                        WHEN 2 THEN 'Lunes' WHEN 3 THEN 'Martes' WHEN 4 THEN 'Miercoles'
                        WHEN 5 THEN 'Jueves' WHEN 6 THEN 'Viernes' WHEN 7 THEN 'Sabado'
                        ELSE 'Domingo' END) AS dia_semana,
                    b.id_turno, b.nombre_turno,
                   CONCAT(eu.nombre, ' ', eu.apellido) AS estudiante,
                   CONCAT(tu.nombre, ' ', tu.apellido) AS tutor,
                   m.nombre_materia
            FROM tutorias t
            INNER JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
            INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
            INNER JOIN tutores tr ON tr.id_tutor = t.id_tutor
            INNER JOIN usuarios tu ON tu.id_usuario = tr.id_usuario
            INNER JOIN materias m ON m.id_materia = t.id_materia
             LEFT JOIN inscripciones_tutoria i ON i.id_inscripcion = t.id_inscripcion
             LEFT JOIN oferta_horarios oh ON oh.id_oferta_horario = i.id_oferta_horario
             LEFT JOIN ofertas_tutoria o ON o.id_oferta = i.id_oferta
             LEFT JOIN turnos b ON b.id_turno = o.id_turno
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

    public function sessionOptionsForTutor(int $userId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT i.id_inscripcion, i.id_estudiante, i.id_oferta, i.id_oferta_tutor,
                    oh.id_oferta_horario,
                    p.nombre_periodo, p.fecha_inicio, p.fecha_fin,
                    m.nombre_materia, o.nombre_grupo,
                    CONCAT(eu.nombre, ' ', eu.apellido) AS estudiante,
                    oh.dia_semana, b.nombre_turno, b.hora_inicio, b.hora_fin,
                    a.nombre_aula, a.ubicacion,
                    (SELECT COUNT(*) FROM tutorias ts
                     WHERE ts.id_inscripcion = i.id_inscripcion AND ts.estado <> 'cancelada') AS sesiones_programadas
             FROM inscripciones_tutoria i
             INNER JOIN ofertas_tutoria o ON o.id_oferta = i.id_oferta
             INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
             INNER JOIN materias m ON m.id_materia = o.id_materia
             INNER JOIN oferta_tutores ot ON ot.id_oferta_tutor = i.id_oferta_tutor AND ot.estado = 'confirmada'
             INNER JOIN tutores tr ON tr.id_tutor = ot.id_tutor
             INNER JOIN usuarios tu ON tu.id_usuario = tr.id_usuario
             INNER JOIN estudiantes e ON e.id_estudiante = i.id_estudiante
             INNER JOIN usuarios eu ON eu.id_usuario = e.id_usuario
             INNER JOIN oferta_tutor_horarios accepted_schedule ON accepted_schedule.id_oferta_tutor = ot.id_oferta_tutor
             INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = accepted_schedule.id_oferta_horario
                AND oh.id_oferta = o.id_oferta
                AND (i.id_oferta_horario IS NULL OR i.id_oferta_horario = oh.id_oferta_horario)
             INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
             WHERE tu.id_usuario = :id_usuario AND i.estado = 'inscrita'
             ORDER BY p.fecha_inicio DESC, m.nombre_materia, estudiante, FIELD(oh.dia_semana,'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'), b.hora_inicio"
        );
        $statement->execute(['id_usuario' => $userId]);

        return $statement->fetchAll();
    }

    public function enrollmentForTutor(int $enrollmentId, int $offerScheduleId, int $userId): ?array
    {
        $statement = Database::connection()->prepare(
            "SELECT i.id_inscripcion, i.id_estudiante, i.id_oferta, i.estado AS inscripcion_estado,
                    p.id_periodo, p.fecha_inicio, p.fecha_fin, m.id_materia, m.nombre_materia, o.id_tipo_tutoria,
                    ot.id_tutor, oh.dia_semana, b.hora_inicio, b.hora_fin,
                    a.nombre_aula, a.ubicacion
             FROM inscripciones_tutoria i
             INNER JOIN ofertas_tutoria o ON o.id_oferta = i.id_oferta
             INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
             INNER JOIN materias m ON m.id_materia = o.id_materia
             INNER JOIN oferta_tutores ot ON ot.id_oferta_tutor = i.id_oferta_tutor AND ot.estado = 'confirmada'
             INNER JOIN tutores tr ON tr.id_tutor = ot.id_tutor
             INNER JOIN usuarios tu ON tu.id_usuario = tr.id_usuario
             INNER JOIN oferta_tutor_horarios accepted_schedule
                ON accepted_schedule.id_oferta_tutor = ot.id_oferta_tutor
               AND accepted_schedule.id_oferta_horario = :id_oferta_horario
             INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = accepted_schedule.id_oferta_horario
               AND oh.id_oferta = o.id_oferta
               AND (i.id_oferta_horario IS NULL OR i.id_oferta_horario = oh.id_oferta_horario)
             INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
             WHERE i.id_inscripcion = :id_inscripcion
               AND i.estado = 'inscrita'
                AND tu.id_usuario = :id_usuario
             LIMIT 1"
        );
        $statement->execute([
            'id_inscripcion' => $enrollmentId,
            'id_oferta_horario' => $offerScheduleId,
            'id_usuario' => $userId,
        ]);
        $enrollment = $statement->fetch();

        return $enrollment ?: null;
    }

    public function createFromEnrollment(int $enrollmentId, int $offerScheduleId, int $userId, array $data): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                "SELECT i.id_inscripcion, i.id_estudiante, i.id_oferta, p.fecha_inicio, p.fecha_fin,
                        m.id_materia, m.nombre_materia, ot.id_tutor, oh.dia_semana,
                        b.hora_inicio, b.hora_fin, a.nombre_aula, a.ubicacion
                 FROM inscripciones_tutoria i
                 INNER JOIN ofertas_tutoria o ON o.id_oferta = i.id_oferta
                 INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
                 INNER JOIN materias m ON m.id_materia = o.id_materia
                 INNER JOIN oferta_tutores ot ON ot.id_oferta_tutor = i.id_oferta_tutor AND ot.estado = 'confirmada'
                 INNER JOIN tutores tr ON tr.id_tutor = ot.id_tutor
                 INNER JOIN usuarios tu ON tu.id_usuario = tr.id_usuario
                 INNER JOIN oferta_tutor_horarios accepted_schedule
                    ON accepted_schedule.id_oferta_tutor = ot.id_oferta_tutor
                   AND accepted_schedule.id_oferta_horario = :id_oferta_horario
                 INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = accepted_schedule.id_oferta_horario
                   AND oh.id_oferta = o.id_oferta
                   AND (i.id_oferta_horario IS NULL OR i.id_oferta_horario = oh.id_oferta_horario)
                 INNER JOIN turnos b ON b.id_turno = o.id_turno
                 LEFT JOIN aulas a ON a.id_aula = oh.id_aula
                 WHERE i.id_inscripcion = :id_inscripcion
                   AND i.estado = 'inscrita'
                   AND tu.id_usuario = :id_usuario
                 LIMIT 1 FOR UPDATE"
            );
            $statement->execute([
                'id_inscripcion' => $enrollmentId,
                'id_oferta_horario' => $offerScheduleId,
                'id_usuario' => $userId,
            ]);
            $enrollment = $statement->fetch();
            if (!$enrollment) {
                throw new RuntimeException('La inscripcion no existe, no esta activa o no pertenece al tutor.');
            }

            if ($data['fecha'] < $enrollment['fecha_inicio'] || $data['fecha'] > $enrollment['fecha_fin']) {
                throw new RuntimeException('La fecha debe estar dentro del periodo de la inscripcion.');
            }
            $allowedDate = $pdo->prepare(
                "SELECT 1 FROM oferta_tutoria_fechas
                 WHERE id_oferta = :id_oferta AND fecha = :fecha AND estado = 'activa' LIMIT 1"
            );
            $allowedDate->execute(['id_oferta' => $enrollment['id_oferta'], 'fecha' => $data['fecha']]);
            if (!$allowedDate->fetchColumn()) {
                throw new RuntimeException('La fecha no esta habilitada en el calendario de esta oferta.');
            }
            $date = DateTime::createFromFormat('!Y-m-d', $data['fecha']);
            $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
            if (!$date || $dayNames[(int) $date->format('w')] !== $enrollment['dia_semana']) {
                throw new RuntimeException('La fecha debe corresponder al dia asignado en la inscripcion.');
            }

            $conflict = $pdo->prepare(
                "SELECT 1
                 FROM tutorias
                 WHERE (id_tutor = :id_tutor OR id_estudiante = :id_estudiante)
                   AND fecha = :fecha
                   AND estado IN ('pendiente', 'confirmada')
                   AND hora_inicio < :hora_fin
                   AND hora_fin > :hora_inicio
                 LIMIT 1"
            );
            $conflict->execute([
                'id_tutor' => $enrollment['id_tutor'],
                'id_estudiante' => $enrollment['id_estudiante'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $enrollment['hora_inicio'],
                'hora_fin' => $enrollment['hora_fin'],
            ]);
            if ($conflict->fetchColumn()) {
                throw new RuntimeException('El tutor o el estudiante ya tiene otra sesion en ese horario.');
            }

            if ($data['modalidad'] === 'virtual') {
                $place = $data['lugar_o_enlace'];
            } else {
                $room = trim((string) ($enrollment['nombre_aula'] ?? ''));
                $location = trim((string) ($enrollment['ubicacion'] ?? ''));
                $place = trim($room . ($room !== '' && $location !== '' ? ' - ' : '') . $location);
            }

            $insert = $pdo->prepare(
                "INSERT INTO tutorias
                    (id_inscripcion, id_estudiante, id_tutor, id_materia, fecha,
                     hora_inicio, hora_fin, modalidad, lugar_o_enlace, estado, observaciones)
                 VALUES (:id_inscripcion, :id_estudiante, :id_tutor, :id_materia, :fecha,
                         :hora_inicio, :hora_fin, :modalidad, :lugar_o_enlace, 'pendiente', :observaciones)"
            );
            $insert->execute([
                'id_inscripcion' => $enrollment['id_inscripcion'],
                'id_estudiante' => $enrollment['id_estudiante'],
                'id_tutor' => $enrollment['id_tutor'],
                'id_materia' => $enrollment['id_materia'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $enrollment['hora_inicio'],
                'hora_fin' => $enrollment['hora_fin'],
                'modalidad' => $data['modalidad'],
                'lugar_o_enlace' => $place !== '' ? $place : null,
                'observaciones' => $data['observaciones'] !== '' ? $data['observaciones'] : null,
            ]);
            $tutoriaId = (int) $pdo->lastInsertId();
            $studentUser = $pdo->prepare('SELECT id_usuario FROM estudiantes WHERE id_estudiante = :id LIMIT 1');
            $studentUser->execute(['id' => (int) $enrollment['id_estudiante']]);
            (new Notificacion())->add(
                $pdo, (int) $studentUser->fetchColumn(), 'sesion_propuesta', 'Nueva sesión propuesta',
                'Tu tutor propuso una sesión de ' . $enrollment['nombre_materia'] . ' para el ' . $data['fecha'] . '. Confirma o responde desde tus tutorías.',
                'tutorias/', 'tutoria-session-proposed:' . $tutoriaId
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
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
                   COALESCE(GROUP_CONCAT(
                         DISTINCT CONCAT(d.dia_semana, ' ', b.nombre_turno)
                        ORDER BY FIELD(d.dia_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'), b.hora_inicio
                       SEPARATOR ', '
                   ), '') AS disponibilidad
            FROM tutor_materia tm
            INNER JOIN tutores t ON t.id_tutor = tm.id_tutor
            INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
            INNER JOIN materias m ON m.id_materia = tm.id_materia
              LEFT JOIN disponibilidad_tutor d ON d.id_tutor = tm.id_tutor AND d.id_turno IS NOT NULL
              LEFT JOIN turnos b ON b.id_turno = d.id_turno AND b.estado = 'activo'
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
             INNER JOIN turnos b ON b.id_turno = d.id_turno AND b.estado = 'activo'
            WHERE d.id_tutor = :id_tutor
              AND d.dia_semana = CASE WEEKDAY(:fecha)
                    WHEN 0 THEN 'Lunes'
                    WHEN 1 THEN 'Martes'
                    WHEN 2 THEN 'Miercoles'
                    WHEN 3 THEN 'Jueves'
                    WHEN 4 THEN 'Viernes'
                    WHEN 5 THEN 'Sabado'
                  END
              AND b.hora_inicio <= :hora_inicio
              AND b.hora_fin >= :hora_fin
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

    public function dateAllowedForOffer(int $offerId, string $date): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1 FROM oferta_tutoria_fechas
             WHERE id_oferta = :id_oferta AND fecha = :fecha AND estado = 'activa' LIMIT 1"
        );
        $statement->execute(['id_oferta' => $offerId, 'fecha' => $date]);

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
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $sql = 'UPDATE tutorias SET estado = :estado WHERE id_tutoria = :id_tutoria AND estado = :estado_actual';
            $params = ['estado' => $status, 'id_tutoria' => $id, 'estado_actual' => $currentState];
            if ($role === 'tutor') {
                $sql .= ' AND id_tutor = (SELECT id_tutor FROM tutores WHERE id_usuario = :id_usuario)';
                $params['id_usuario'] = $userId;
            } elseif ($role === 'estudiante') {
                $sql .= ' AND id_estudiante = (SELECT id_estudiante FROM estudiantes WHERE id_usuario = :id_usuario)';
                $params['id_usuario'] = $userId;
            }
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
            if ($statement->rowCount() < 1) {
                $pdo->rollBack();
                return false;
            }
            $participants = $pdo->prepare(
                'SELECT e.id_usuario AS student_user_id, tr.id_usuario AS tutor_user_id, m.nombre_materia
                 FROM tutorias t
                 INNER JOIN estudiantes e ON e.id_estudiante = t.id_estudiante
                 INNER JOIN tutores tr ON tr.id_tutor = t.id_tutor
                 INNER JOIN materias m ON m.id_materia = t.id_materia
                 WHERE t.id_tutoria = :id LIMIT 1'
            );
            $participants->execute(['id' => $id]);
            $row = $participants->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                return false;
            }
            $recipients = $role === 'estudiante'
                ? [(int) $row['tutor_user_id']]
                : ($role === 'tutor' ? [(int) $row['student_user_id']] : [(int) $row['student_user_id'], (int) $row['tutor_user_id']]);
            $titles = ['confirmada' => 'Sesión confirmada', 'cancelada' => 'Sesión cancelada', 'realizada' => 'Sesión completada'];
            $title = $titles[$status] ?? 'Actualización de sesión';
            foreach (array_unique($recipients) as $recipientId) {
                (new Notificacion())->add(
                    $pdo, $recipientId, 'estado_sesion', $title,
                    'La sesión de ' . $row['nombre_materia'] . ' cambió de ' . $currentState . ' a ' . $status . '.',
                    'tutorias/', 'tutoria-status:' . $id . ':' . $currentState . ':' . $status . ':' . $recipientId
                );
            }
            $pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
