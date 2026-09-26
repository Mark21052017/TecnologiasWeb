<?php

declare(strict_types=1);

final class OfertaTutoria
{
    private const DAYS = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

    private function baseSelect(): string
    {
        return <<<'SQL'
            SELECT o.id_oferta, o.id_periodo, o.id_materia, o.id_tipo_tutoria, o.id_turno,
                   t.nombre_turno AS turno, t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin,
                   o.nombre_grupo,
                   o.cupo, o.descripcion, o.estado,
                    p.nombre_periodo, p.fecha_inicio, p.fecha_fin, p.estado AS periodo_estado,
                    o.frecuencia_programacion,
                    p.inscripcion_inicio, p.inscripcion_fin,
                     m.nombre_materia, m.id_carrera, c.nombre_carrera, tt.nombre AS nombre_tipo_tutoria,
                    (SELECT COUNT(*) FROM oferta_tutoria_fechas otf_count
                     WHERE otf_count.id_oferta = o.id_oferta AND otf_count.estado = 'activa') AS total_fechas,
                   COUNT(DISTINCT CASE WHEN i.estado = 'inscrita' THEN i.id_inscripcion END) AS inscritos
            FROM ofertas_tutoria o
            INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
            INNER JOIN materias m ON m.id_materia = o.id_materia
            INNER JOIN tipos_tutoria tt ON tt.id_tipo_tutoria = o.id_tipo_tutoria
            INNER JOIN turnos t ON t.id_turno = o.id_turno
            LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
            LEFT JOIN inscripciones_tutoria i ON i.id_oferta = o.id_oferta
        SQL;
    }

    public function all(): array
    {
        $sql = $this->baseSelect() . ' GROUP BY o.id_oferta ORDER BY p.fecha_inicio DESC, m.nombre_materia, t.hora_inicio, o.nombre_grupo';
        $offers = Database::connection()->query($sql)->fetchAll();
        $offerIds = array_map('intval', array_column($offers, 'id_oferta'));
        $schedulesByOffer = $this->scheduleRowsForOffers($offerIds);

        foreach ($offers as &$offer) {
            $offer['horarios_oferta'] = $schedulesByOffer[(int) $offer['id_oferta']] ?? [];
        }
        unset($offer);

        return $offers;
    }

    public function publicOffers(?int $studentId = null): array
    {
        $sql = $this->baseSelect();
        $sql .= " WHERE o.estado = 'publicada'
                  AND p.estado = 'publicado'
                  AND CURRENT_DATE BETWEEN p.inscripcion_inicio AND p.inscripcion_fin
                  AND EXISTS (
                      SELECT 1 FROM oferta_tutoria_fechas otf
                      WHERE otf.id_oferta = o.id_oferta
                        AND otf.estado = 'activa'
                  )";
        $sql .= ' GROUP BY o.id_oferta ORDER BY m.nombre_materia, t.hora_inicio, o.nombre_grupo';
        $offers = Database::connection()->query($sql)->fetchAll();
        $offerIds = array_map('intval', array_column($offers, 'id_oferta'));
        $enrollableByOffer = $this->enrollableRowsForOffers($offerIds);
        $enrolledOfferIds = $studentId !== null ? $this->enrolledOfferIds($studentId, $offerIds) : [];

        foreach ($offers as &$offer) {
            $offerId = (int) $offer['id_oferta'];
            $offer['inscribibles'] = $enrollableByOffer[$offerId] ?? [];
            $offer['inscrito'] = in_array($offerId, $enrolledOfferIds, true);
        }
        unset($offer);

        return $offers;
    }

    public function find(int $id): ?array
    {
        $statement = Database::connection()->prepare($this->baseSelect() . ' WHERE o.id_oferta = :id GROUP BY o.id_oferta LIMIT 1');
        $statement->execute(['id' => $id]);
        $offer = $statement->fetch();
        if (!$offer) {
            return null;
        }

        $offer['horarios'] = $this->scheduleRows($id);
        $offer['fechas'] = $this->dateRows($id);
        $offer['tutores'] = $this->confirmedTutors($id);
        return $offer;
    }

    public function optionsForForm(): array
    {
        return [
            'periodos' => (new PeriodoTutoria())->options(),
            'carreras' => Database::connection()->query('SELECT id_carrera, nombre_carrera FROM carreras ORDER BY nombre_carrera')->fetchAll(),
            'materias' => Database::connection()->query('SELECT id_materia, id_carrera, nombre_materia FROM materias ORDER BY nombre_materia')->fetchAll(),
            'tipos_tutoria' => Database::connection()->query("SELECT id_tipo_tutoria, nombre, estado FROM tipos_tutoria ORDER BY FIELD(estado, 'activo', 'inactivo'), nombre")->fetchAll(),
            'turnos' => (new Turno())->all(false),
            'aulas' => (new Aula())->all(false),
        ];
    }

    public function subjectBelongsToCareer(int $subjectId, int $careerId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM materias WHERE id_materia = :id_materia AND id_carrera = :id_carrera LIMIT 1'
        );
        $statement->execute(['id_materia' => $subjectId, 'id_carrera' => $careerId]);
        return (bool) $statement->fetchColumn();
    }

    public function roomAvailability(int $periodId, string $day, int $turnId, ?int $excludeOfferId = null): array
    {
        $rooms = (new Aula())->all(false);
        if ($periodId < 1 || $turnId < 1 || !in_array($day, self::DAYS, true)) {
            return array_map(static function (array $room): array {
                $room['bloqueada'] = false;
                $room['materia_bloqueante'] = null;
                return $room;
            }, $rooms);
        }

        $sql = 'SELECT oh.id_aula, m.nombre_materia
                FROM oferta_horarios oh
                INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta
                INNER JOIN materias m ON m.id_materia = o.id_materia
                WHERE o.id_periodo = :id_periodo
                  AND oh.dia_semana = :dia_semana
                  AND o.id_turno = :id_turno
                  AND oh.id_aula IS NOT NULL';
        $params = [
            'id_periodo' => $periodId,
            'dia_semana' => $day,
            'id_turno' => $turnId,
        ];
        if ($excludeOfferId !== null) {
            $sql .= ' AND o.id_oferta <> :exclude_id';
            $params['exclude_id'] = $excludeOfferId;
        }
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $occupied = [];
        foreach ($statement->fetchAll() as $row) {
            $occupied[(int) $row['id_aula']] = (string) $row['nombre_materia'];
        }

        foreach ($this->tutoriaRoomRows($periodId, $day, $turnId, null, $excludeOfferId) as $row) {
            $occupied[(int) $row['id_aula']] = 'Tutoría: ' . (string) $row['nombre_materia'];
        }

        foreach ($rooms as &$room) {
            $roomId = (int) $room['id_aula'];
            $room['bloqueada'] = array_key_exists($roomId, $occupied);
            $room['materia_bloqueante'] = $occupied[$roomId] ?? null;
        }
        unset($room);

        return $rooms;
    }

    private function tutoriaRoomRows(int $periodId, string $day, int $turnId, ?int $roomId, ?int $excludeOfferId): array
    {
        $sql = <<<'SQL'
            SELECT DISTINCT oh.id_aula, m.nombre_materia
            FROM tutorias t
            INNER JOIN inscripciones_tutoria i ON i.id_inscripcion = t.id_inscripcion AND i.estado = 'inscrita'
            INNER JOIN ofertas_tutoria o ON o.id_oferta = i.id_oferta
            INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
            INNER JOIN turnos tr ON tr.id_turno = o.id_turno
            INNER JOIN materias m ON m.id_materia = o.id_materia
            INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = i.id_oferta_horario
                                            AND oh.id_oferta = o.id_oferta
            WHERE p.id_periodo = :id_periodo
              AND o.id_turno = :id_turno
              AND oh.dia_semana = :dia_semana
              AND oh.id_aula IS NOT NULL
              AND t.estado IN ('pendiente', 'confirmada')
              AND (t.fecha > CURRENT_DATE OR (t.fecha = CURRENT_DATE AND t.hora_fin > CURRENT_TIME))
              AND t.fecha BETWEEN p.fecha_inicio AND p.fecha_fin
              AND CASE DAYOFWEEK(t.fecha)
                    WHEN 2 THEN 'Lunes'
                    WHEN 3 THEN 'Martes'
                    WHEN 4 THEN 'Miercoles'
                    WHEN 5 THEN 'Jueves'
                    WHEN 6 THEN 'Viernes'
                    WHEN 7 THEN 'Sabado'
                    ELSE 'Domingo'
                  END = :session_day
              AND t.hora_inicio < tr.hora_fin
              AND t.hora_fin > tr.hora_inicio
        SQL;
        $params = [
            'id_periodo' => $periodId,
            'id_turno' => $turnId,
            'dia_semana' => $day,
            'session_day' => $day,
        ];
        if ($roomId !== null) {
            $sql .= ' AND oh.id_aula = :id_aula';
            $params['id_aula'] = $roomId;
        }
        if ($excludeOfferId !== null) {
            $sql .= ' AND o.id_oferta <> :exclude_id';
            $params['exclude_id'] = $excludeOfferId;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function activeTurnExists(int $turnId): bool
    {
        $statement = Database::connection()->prepare(
            "SELECT 1 FROM turnos WHERE id_turno = :id_turno AND estado = 'activo' LIMIT 1"
        );
        $statement->execute(['id_turno' => $turnId]);
        return (bool) $statement->fetchColumn();
    }

    public function periodBounds(int $periodId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT fecha_inicio, fecha_fin, id_tipo_tutoria FROM periodos_tutoria WHERE id_periodo = :id_periodo LIMIT 1'
        );
        $statement->execute(['id_periodo' => $periodId]);
        $period = $statement->fetch();
        return $period ?: null;
    }

    public function create(array $data, array $schedules, array $dates): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'INSERT INTO ofertas_tutoria (id_periodo, id_materia, id_tipo_tutoria, id_turno, frecuencia_programacion, nombre_grupo, cupo, descripcion, estado)
                 VALUES (:id_periodo, :id_materia, :id_tipo_tutoria, :id_turno, :frecuencia_programacion, :nombre_grupo, :cupo, :descripcion, :estado)'
            );
            $statement->execute($data);
            $offerId = (int) $pdo->lastInsertId();
            $this->replaceSchedules($offerId, $schedules, $pdo);
            $this->replaceDates($offerId, $dates, $pdo);
            $pdo->commit();
            return $offerId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function update(int $id, array $data, array $schedules, array $dates): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $enrollments = $pdo->prepare("SELECT COUNT(*) FROM inscripciones_tutoria WHERE id_oferta = :id AND estado = 'inscrita'");
            $enrollments->execute(['id' => $id]);
            if ((int) $enrollments->fetchColumn() > 0) {
                throw new RuntimeException('No se puede modificar una oferta con inscripciones activas.');
            }
            $statement = $pdo->prepare(
                'UPDATE ofertas_tutoria SET id_periodo = :id_periodo, id_materia = :id_materia, id_tipo_tutoria = :id_tipo_tutoria,
                    id_turno = :id_turno, frecuencia_programacion = :frecuencia_programacion,
                    nombre_grupo = :nombre_grupo, cupo = :cupo, descripcion = :descripcion, estado = :estado
                 WHERE id_oferta = :id_oferta'
            );
            $data['id_oferta'] = $id;
            $statement->execute($data);
            $this->replaceSchedules($id, $schedules, $pdo);
            $this->replaceDates($id, $dates, $pdo);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function openNextGroup(int $offerId): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $offer = $this->lockedOfferForGroup($pdo, $offerId);
            if (!$offer) {
                throw new RuntimeException('La oferta no existe.');
            }
            if ((int) $offer['inscritos'] < (int) $offer['cupo']) {
                throw new RuntimeException('El grupo aun tiene cupos disponibles.');
            }
            if (in_array($offer['estado'], ['cancelada', 'finalizada'], true)) {
                throw new RuntimeException('No se puede abrir un grupo desde una oferta cerrada.');
            }

            $nextGroup = $this->nextGroupAfterCurrent($offer['nombre_grupo'], $this->existingGroups($pdo, $offer));
            if ($nextGroup === null) {
                throw new RuntimeException('El siguiente grupo ya existe.');
            }
            $newOfferId = $this->insertNextGroup($pdo, $offer, $nextGroup);
            $pdo->commit();

            return $newOfferId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function ensureNextGroupForFullOffer(PDO $pdo, int $offerId): ?int
    {
        $startedTransaction = !$pdo->inTransaction();
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }
        try {
            $offer = $this->lockedOfferForGroup($pdo, $offerId);
            if (!$offer || in_array($offer['estado'], ['cancelada', 'finalizada'], true)) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return null;
            }

            $nextGroup = $this->nextGroupAfterCurrent($offer['nombre_grupo'], $this->existingGroups($pdo, $offer));
            if ($nextGroup === null) {
                if ($startedTransaction) {
                    $pdo->commit();
                }
                return null;
            }

            $newOfferId = $this->insertNextGroup($pdo, $offer, $nextGroup);
            if ($startedTransaction) {
                $pdo->commit();
            }
            return $newOfferId;
        } catch (Throwable $exception) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM ofertas_tutoria WHERE id_oferta = :id');
        $statement->execute(['id' => $id]);
    }

    public function validateBeforeSave(array $data, array $schedules, array $dates, ?int $excludeId = null): array
    {
        if ((int) ($data['id_turno'] ?? 0) < 1) {
            return ['Seleccione un turno valido.'];
        }

        $errors = [];
        $pdo = Database::connection();

        $sql = 'SELECT o.id_oferta FROM ofertas_tutoria o
                WHERE o.id_periodo = :id_periodo AND o.id_materia = :id_materia
                   AND o.id_turno = :id_turno AND LOWER(o.nombre_grupo) = :nombre_grupo';
        $params = [
            'id_periodo' => $data['id_periodo'],
            'id_materia' => $data['id_materia'],
            'id_turno' => $data['id_turno'],
            'nombre_grupo' => mb_strtolower($data['nombre_grupo']),
        ];
        if ($excludeId !== null) {
            $sql .= ' AND o.id_oferta <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        if ($statement->fetchColumn()) {
            $errors[] = sprintf(
                'Ya existe una oferta de esta materia en el turno seleccionado con el paralelo "%s". Use otro paralelo o elija otro turno.',
                $data['nombre_grupo']
            );
        }

        if ($data['estado'] === 'publicada' && !$dates) {
            $errors[] = 'Una oferta publicada debe tener al menos una fecha habilitada.';
            return array_values(array_unique($errors));
        }

        return array_merge($errors, $this->scheduleErrors($data, $schedules, $excludeId), $this->dateErrors($data, $schedules, $dates));
    }

    private function dateErrors(array $data, array $schedules, array $dates): array
    {
        foreach ($dates as $date) {
            if ((int) date('w', strtotime($date)) === 0) {
                return ['El calendario no habilita domingos; seleccione otra fecha.'];
            }
        }
        if ($data['estado'] === 'publicada' && !$dates) {
            return ['Una oferta publicada debe tener al menos una fecha habilitada.'];
        }

        return [];
    }

    private function scheduleErrors(array $data, array $schedules, ?int $excludeId): array
    {
        $errors = [];
        $pdo = Database::connection();
        $checkedRooms = [];
        $capacityErrors = [];
        $completeScheduleCount = 0;
        $missingRoomCount = 0;
        foreach ($schedules as $schedule) {
            $day = trim((string) ($schedule['dia_semana'] ?? ''));
            if (!in_array($day, self::DAYS, true)) {
                continue;
            }
            $completeScheduleCount++;

            $roomId = filter_var($schedule['id_aula'] ?? null, FILTER_VALIDATE_INT);
            if ($roomId === false || $roomId < 1) {
                if ($data['estado'] === 'publicada') {
                    $missingRoomCount++;
                }
                continue;
            }
            $roomStatement = $pdo->prepare(
                "SELECT nombre_aula, capacidad FROM aulas WHERE id_aula = :id_aula AND estado = 'activa' LIMIT 1"
            );
            $roomStatement->execute(['id_aula' => $roomId]);
            $room = $roomStatement->fetch();
            if (!$room) {
                $errors[] = 'Seleccione un aula activa y valida.';
                continue;
            }
            $roomName = (string) $room['nombre_aula'];
            if ($room['capacidad'] !== null && (int) $data['cupo'] > (int) $room['capacidad'] && !isset($capacityErrors[$roomId])) {
                $errors[] = sprintf(
                    'El cupo (%d) supera la capacidad del aula %s (%d).',
                    (int) $data['cupo'],
                    $roomName,
                    (int) $room['capacidad']
                );
                $capacityErrors[$roomId] = true;
            }
            $roomKey = $day . '|' . $roomId;
            if (isset($checkedRooms[$roomKey])) {
                $errors[] = sprintf('El aula %s se repite en el mismo dia de esta oferta.', $roomName);
                continue;
            }
            $checkedRooms[$roomKey] = true;

            $sql = 'SELECT a.nombre_aula, m.nombre_materia
                    FROM oferta_horarios oh
                    INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta
                    INNER JOIN aulas a ON a.id_aula = oh.id_aula
                    INNER JOIN materias m ON m.id_materia = o.id_materia
                    WHERE o.id_periodo = :id_periodo AND oh.dia_semana = :dia_semana
                      AND o.id_turno = :id_turno AND oh.id_aula = :id_aula';
            $params = [
                'id_periodo' => $data['id_periodo'],
                'dia_semana' => $day,
                'id_turno' => $data['id_turno'],
                'id_aula' => $roomId,
            ];
            if ($excludeId !== null) {
                $sql .= ' AND o.id_oferta <> :exclude_id';
                $params['exclude_id'] = $excludeId;
            }
            $sql .= ' LIMIT 1';
            $statement = Database::connection()->prepare($sql);
            $statement->execute($params);
            $conflict = $statement->fetch();
            if ($conflict) {
                $errors[] = sprintf(
                    'El aula %s ya esta asignada ese dia y turno en el periodo seleccionado (la usa la materia %s). Elija otra aula u horario.',
                    $conflict['nombre_aula'],
                    $conflict['nombre_materia']
                );
            }

            $tutoriaConflicts = $this->tutoriaRoomRows(
                (int) $data['id_periodo'],
                $day,
                (int) $data['id_turno'],
                (int) $roomId,
                $excludeId
            );
            if ($tutoriaConflicts) {
                $errors[] = sprintf(
                    'El aula %s tiene una tutoria pendiente o confirmada de la materia %s en ese periodo, dia y turno.',
                    $roomName,
                    $tutoriaConflicts[0]['nombre_materia']
                );
            }
        }

        if ($data['estado'] === 'publicada' && $completeScheduleCount === 0) {
            $errors[] = 'Una oferta publicada debe tener al menos un dia y aula definidos.';
        }
        if ($data['estado'] === 'publicada' && $missingRoomCount > 0) {
            $errors[] = 'Una oferta publicada debe tener un aula activa en cada horario definido.';
        }

        return array_values(array_unique($errors));
    }

    private function nextGroupName(array $existingGroups): string
    {
        for ($index = 0; $index < 26; $index++) {
            $candidate = 'grupo ' . chr(65 + $index);
            if (!in_array($candidate, $existingGroups, true)) {
                return 'Grupo ' . chr(65 + $index);
            }
        }

        $number = 27;
        while (in_array('grupo ' . $number, $existingGroups, true)) {
            $number++;
        }

        return 'Grupo ' . $number;
    }

    private function lockedOfferForGroup(PDO $pdo, int $offerId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT o.id_oferta, o.id_periodo, o.id_materia, o.id_tipo_tutoria, o.id_turno,
                    tr.nombre_turno AS turno, o.frecuencia_programacion, o.nombre_grupo,
                    o.cupo, o.descripcion, o.estado,
                    COUNT(CASE WHEN i.estado = \'inscrita\' THEN i.id_inscripcion END) AS inscritos
             FROM ofertas_tutoria o
             INNER JOIN turnos tr ON tr.id_turno = o.id_turno
             LEFT JOIN inscripciones_tutoria i ON i.id_oferta = o.id_oferta
             WHERE o.id_oferta = :id_oferta
             GROUP BY o.id_oferta, o.id_periodo, o.id_materia, o.id_tipo_tutoria,
                     o.id_turno, tr.nombre_turno, o.frecuencia_programacion,
                     o.nombre_grupo, o.cupo, o.descripcion, o.estado
             FOR UPDATE'
        );
        $statement->execute(['id_oferta' => $offerId]);
        $offer = $statement->fetch();
        return $offer ?: null;
    }

    private function existingGroups(PDO $pdo, array $offer): array
    {
        $groups = $pdo->prepare(
            'SELECT nombre_grupo FROM ofertas_tutoria
             WHERE id_periodo = :id_periodo AND id_materia = :id_materia AND id_turno = :id_turno'
        );
        $groups->execute([
            'id_periodo' => $offer['id_periodo'],
            'id_materia' => $offer['id_materia'],
            'id_turno' => $offer['id_turno'],
        ]);
        return array_map(
            static fn (array $row): string => mb_strtolower(trim((string) $row['nombre_grupo'])),
            $groups->fetchAll()
        );
    }

    private function nextGroupAfterCurrent(string $currentGroup, array $existingGroups): ?string
    {
        $current = mb_strtolower(trim($currentGroup));
        if (preg_match('/^grupo ([a-z])$/', $current, $matches) === 1) {
            $nextCode = ord(strtoupper($matches[1])) + 1;
            if ($nextCode <= ord('Z')) {
                $candidate = 'grupo ' . chr($nextCode);
                return in_array($candidate, $existingGroups, true) ? null : 'Grupo ' . chr($nextCode);
            }
        }

        return $this->nextGroupName($existingGroups);
    }

    private function insertNextGroup(PDO $pdo, array $offer, string $nextGroup): int
    {
        $insert = $pdo->prepare(
            'INSERT INTO ofertas_tutoria
                (id_periodo, id_materia, id_tipo_tutoria, id_turno, frecuencia_programacion, nombre_grupo, cupo, descripcion, estado)
             VALUES (:id_periodo, :id_materia, :id_tipo_tutoria, :id_turno, :frecuencia_programacion, :nombre_grupo, :cupo, :descripcion, \'borrador\')'
        );
        $insert->execute([
            'id_periodo' => $offer['id_periodo'],
            'id_materia' => $offer['id_materia'],
            'id_tipo_tutoria' => $offer['id_tipo_tutoria'],
            'id_turno' => $offer['id_turno'],
            'frecuencia_programacion' => $offer['frecuencia_programacion'],
            'nombre_grupo' => $nextGroup,
            'cupo' => $offer['cupo'],
            'descripcion' => $offer['descripcion'],
        ]);
        $newOfferId = (int) $pdo->lastInsertId();
        $copyDates = $pdo->prepare(
            'INSERT INTO oferta_tutoria_fechas (id_oferta, fecha, estado)
             SELECT :new_offer, fecha, estado FROM oferta_tutoria_fechas WHERE id_oferta = :source_offer'
        );
        $copyDates->execute(['new_offer' => $newOfferId, 'source_offer' => $offer['id_oferta']]);
        $copySchedules = $pdo->prepare(
            'INSERT INTO oferta_horarios (id_oferta, dia_semana, id_aula)
             SELECT :new_offer, dia_semana, id_aula FROM oferta_horarios WHERE id_oferta = :source_offer'
        );
        $copySchedules->execute(['new_offer' => $newOfferId, 'source_offer' => $offer['id_oferta']]);
        return $newOfferId;
    }

    public function tutorOffers(int $userId): array
    {
        $sql = <<<'SQL'
            SELECT o.id_oferta, o.id_periodo, o.id_turno, trn.nombre_turno AS turno, o.nombre_grupo, o.cupo, o.descripcion, o.estado AS oferta_estado,
                   ot.id_oferta_tutor, ot.estado AS tutor_estado,
                     p.nombre_periodo, p.fecha_inicio, p.fecha_fin, p.estado AS periodo_estado,
                     o.frecuencia_programacion,
                   m.nombre_materia, c.nombre_carrera, tt.nombre AS nombre_tipo_tutoria,
                   COUNT(DISTINCT CASE WHEN i.estado = 'inscrita' THEN i.id_inscripcion END) AS inscritos
            FROM oferta_tutores ot
            INNER JOIN tutores tut ON tut.id_tutor = ot.id_tutor
            INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
            INNER JOIN turnos trn ON trn.id_turno = o.id_turno
            INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
            INNER JOIN materias m ON m.id_materia = o.id_materia
            INNER JOIN tipos_tutoria tt ON tt.id_tipo_tutoria = o.id_tipo_tutoria
            LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
            LEFT JOIN inscripciones_tutoria i ON i.id_oferta_tutor = ot.id_oferta_tutor
            WHERE tut.id_usuario = :id_usuario
            GROUP BY ot.id_oferta_tutor
            ORDER BY p.fecha_inicio DESC, m.nombre_materia
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id_usuario' => $userId]);
        $rows = $statement->fetchAll();
        $offerTutorIds = array_map('intval', array_column($rows, 'id_oferta_tutor'));
        $offerIds = array_map('intval', array_column($rows, 'id_oferta'));
        $tutorSchedulesByOfferTutor = $this->tutorScheduleRowsForOffers($offerTutorIds);
        $offerSchedulesByOffer = $this->scheduleRowsForOffers($offerIds);
        foreach ($rows as &$row) {
            $row['horarios'] = $tutorSchedulesByOfferTutor[(int) $row['id_oferta_tutor']] ?? [];
            $row['horarios_oferta'] = $offerSchedulesByOffer[(int) $row['id_oferta']] ?? [];
        }
        unset($row);

        return $rows;
    }

    public function availableForTutor(int $userId): array
    {
        $sql = <<<'SQL'
            SELECT o.id_oferta, o.id_turno, trn.nombre_turno AS turno, o.nombre_grupo, o.cupo, o.descripcion, o.estado AS oferta_estado,
                     p.nombre_periodo, p.fecha_inicio, p.fecha_fin,
                     o.frecuencia_programacion,
                   m.nombre_materia, c.nombre_carrera, tt.nombre AS nombre_tipo_tutoria,
                   (SELECT COUNT(*) FROM inscripciones_tutoria i WHERE i.id_oferta = o.id_oferta AND i.estado = 'inscrita') AS inscritos
            FROM ofertas_tutoria o
            INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
            INNER JOIN turnos trn ON trn.id_turno = o.id_turno
            INNER JOIN materias m ON m.id_materia = o.id_materia
            INNER JOIN tipos_tutoria tt ON tt.id_tipo_tutoria = o.id_tipo_tutoria
            LEFT JOIN carreras c ON c.id_carrera = m.id_carrera
            INNER JOIN tutores tut ON tut.id_usuario = :id_usuario
            WHERE o.estado = 'publicada'
              AND p.estado = 'publicado'
              AND CURRENT_DATE < p.fecha_inicio
              AND EXISTS (
                  SELECT 1 FROM oferta_tutoria_fechas otf
                  WHERE otf.id_oferta = o.id_oferta AND otf.estado = 'activa'
              )
              AND NOT EXISTS (
                  SELECT 1 FROM oferta_tutores ot
                   WHERE ot.id_oferta = o.id_oferta AND ot.id_tutor = tut.id_tutor
              )
            ORDER BY p.fecha_inicio DESC, m.nombre_materia
        SQL;
        $statement = Database::connection()->prepare($sql);
        $statement->execute(['id_usuario' => $userId]);

        $offers = $statement->fetchAll();
        $schedulesByOffer = $this->scheduleRowsForOffers(array_map('intval', array_column($offers, 'id_oferta')));
        foreach ($offers as &$offer) {
            $offer['horarios_oferta'] = $schedulesByOffer[(int) $offer['id_oferta']] ?? [];
        }
        unset($offer);

        return $offers;
    }

    public function selectAsTutor(int $userId, int $offerId): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO oferta_tutores (id_oferta, id_tutor, estado, fecha_revision, revisado_por)
             SELECT :id_oferta, t.id_tutor, \'confirmada\', CURRENT_TIMESTAMP, NULL FROM tutores t
             INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
             INNER JOIN ofertas_tutoria o ON o.id_oferta = :id_oferta_again
             INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
             WHERE t.id_usuario = :id_usuario AND u.estado = \'activo\' AND o.estado = \'publicada\'
                AND p.estado = \'publicado\' AND CURRENT_DATE < p.fecha_inicio
                AND EXISTS (
                    SELECT 1 FROM oferta_tutoria_fechas otf
                    WHERE otf.id_oferta = o.id_oferta AND otf.estado = \'activa\'
                )'
        );
        $statement->execute(['id_oferta' => $offerId, 'id_oferta_again' => $offerId, 'id_usuario' => $userId]);
        if ($statement->rowCount() < 1) {
            throw new RuntimeException('La materia ofertada ya no esta disponible para seleccionarse.');
        }
    }

    public function setTutorSchedules(int $userId, int $offerTutorId, array $scheduleIds): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $owner = $pdo->prepare(
                'SELECT ot.id_oferta_tutor, ot.id_oferta, ot.id_tutor FROM oferta_tutores ot
                 INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
                 WHERE ot.id_oferta_tutor = :id AND t.id_usuario = :id_usuario AND ot.estado = \'confirmada\''
            );
            $owner->execute(['id' => $offerTutorId, 'id_usuario' => $userId]);
            $offerTutor = $owner->fetch();
            if (!$offerTutor) {
                throw new RuntimeException('La asignacion no existe o aun no esta confirmada.');
            }

            $delete = $pdo->prepare('DELETE FROM oferta_tutor_horarios WHERE id_oferta_tutor = :id');
            $delete->execute(['id' => $offerTutorId]);
            $insert = $pdo->prepare(
                'INSERT INTO oferta_tutor_horarios (id_oferta_tutor, id_oferta_horario)
                 SELECT :id_oferta_tutor, id_oferta_horario FROM oferta_horarios
                 WHERE id_oferta_horario = :id_oferta_horario AND id_oferta = :id_oferta'
            );
            $conflict = $pdo->prepare(
                'SELECT 1
                 FROM oferta_tutor_horarios existing_schedule
                 INNER JOIN oferta_tutores existing_tutor ON existing_tutor.id_oferta_tutor = existing_schedule.id_oferta_tutor
                 INNER JOIN oferta_horarios existing_offer_schedule ON existing_offer_schedule.id_oferta_horario = existing_schedule.id_oferta_horario
                 INNER JOIN oferta_horarios selected_schedule ON selected_schedule.id_oferta_horario = :id_oferta_horario
                 INNER JOIN ofertas_tutoria existing_offer ON existing_offer.id_oferta = existing_offer_schedule.id_oferta
                 INNER JOIN periodos_tutoria existing_period ON existing_period.id_periodo = existing_offer.id_periodo
                 INNER JOIN ofertas_tutoria selected_offer ON selected_offer.id_oferta = selected_schedule.id_oferta
                 INNER JOIN periodos_tutoria selected_period ON selected_period.id_periodo = selected_offer.id_periodo
                 WHERE existing_tutor.id_tutor = :id_tutor
                   AND existing_tutor.id_oferta_tutor <> :id_oferta_tutor
                   AND existing_tutor.estado = \'confirmada\'
                   AND existing_offer_schedule.dia_semana = selected_schedule.dia_semana
                    AND existing_offer.id_turno = selected_offer.id_turno
                   AND existing_period.fecha_inicio <= selected_period.fecha_fin
                   AND existing_period.fecha_fin >= selected_period.fecha_inicio
                 LIMIT 1'
            );
            foreach ($scheduleIds as $scheduleId) {
                $conflict->execute([
                    'id_oferta_horario' => (int) $scheduleId,
                    'id_tutor' => (int) $offerTutor['id_tutor'],
                    'id_oferta_tutor' => $offerTutorId,
                ]);
                if ($conflict->fetchColumn()) {
                    throw new RuntimeException('El tutor ya tiene ese dia y turno horario asignado en otra oferta.');
                }
                $insert->execute([
                    'id_oferta_tutor' => $offerTutorId,
                    'id_oferta_horario' => (int) $scheduleId,
                    'id_oferta' => (int) $offerTutor['id_oferta'],
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }


    public function enrollableRows(int $offerId): array
    {
        return $this->enrollableRowsForOffers([$offerId])[$offerId] ?? [];
    }

    private function enrollableRowsForOffers(array $offerIds): array
    {
        $offerIds = array_values(array_unique(array_filter(array_map('intval', $offerIds))));
        if (!$offerIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($offerIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT ot.id_oferta_tutor, ot.id_oferta, CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                    t.especialidad, t.biografia, oth.id_oferta_tutor_horario,
                   oh.id_oferta_horario, oh.dia_semana, b.nombre_turno,
                    b.hora_inicio, b.hora_fin, a.nombre_aula, a.ubicacion
             FROM oferta_tutores ot
             INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
             INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
             INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
             INNER JOIN oferta_tutor_horarios oth ON oth.id_oferta_tutor = ot.id_oferta_tutor
             INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = oth.id_oferta_horario
              INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
              WHERE ot.id_oferta IN ($placeholders) AND ot.estado = 'confirmada'
              ORDER BY tutor, FIELD(oh.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'), b.hora_inicio"
        );
        $statement->execute($offerIds);

        $rowsByOffer = [];
        foreach ($statement->fetchAll() as $row) {
            $rowsByOffer[(int) $row['id_oferta']][] = $row;
        }

        return $rowsByOffer;
    }

    private function scheduleRows(int $offerId): array
    {
        return $this->scheduleRowsForOffers([$offerId])[$offerId] ?? [];
    }

    private function dateRows(int $offerId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT fecha FROM oferta_tutoria_fechas
             WHERE id_oferta = :id_oferta AND estado = 'activa'
             ORDER BY fecha"
        );
        $statement->execute(['id_oferta' => $offerId]);
        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function scheduleRowsForOffers(array $offerIds): array
    {
        $offerIds = array_values(array_unique(array_filter(array_map('intval', $offerIds))));
        if (!$offerIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($offerIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT oh.id_oferta, oh.id_oferta_horario, oh.dia_semana, oh.id_aula, b.nombre_turno,
                    b.hora_inicio, b.hora_fin, a.nombre_aula, a.ubicacion
             FROM oferta_horarios oh
             INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta
             INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
              WHERE oh.id_oferta IN ($placeholders)
              ORDER BY oh.id_oferta, FIELD(oh.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'), b.hora_inicio"
        );
        $statement->execute($offerIds);

        $rowsByOffer = [];
        foreach ($statement->fetchAll() as $row) {
            $rowsByOffer[(int) $row['id_oferta']][] = $row;
        }

        return $rowsByOffer;
    }

    private function tutorScheduleRows(int $offerTutorId): array
    {
        return $this->tutorScheduleRowsForOffers([$offerTutorId])[$offerTutorId] ?? [];
    }

    private function tutorScheduleRowsForOffers(array $offerTutorIds): array
    {
        $offerTutorIds = array_values(array_unique(array_filter(array_map('intval', $offerTutorIds))));
        if (!$offerTutorIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($offerTutorIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT oth.id_oferta_tutor, oth.id_oferta_horario, oh.dia_semana, b.nombre_turno, b.hora_inicio, b.hora_fin,
                    a.nombre_aula, a.ubicacion
             FROM oferta_tutor_horarios oth
             INNER JOIN oferta_horarios oh ON oh.id_oferta_horario = oth.id_oferta_horario
              INNER JOIN ofertas_tutoria o ON o.id_oferta = oh.id_oferta
              INNER JOIN turnos b ON b.id_turno = o.id_turno
             LEFT JOIN aulas a ON a.id_aula = oh.id_aula
              WHERE oth.id_oferta_tutor IN ($placeholders)
              ORDER BY oth.id_oferta_tutor, FIELD(oh.dia_semana, 'Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'), b.hora_inicio"
        );
        $statement->execute($offerTutorIds);

        $rowsByOfferTutor = [];
        foreach ($statement->fetchAll() as $row) {
            $rowsByOfferTutor[(int) $row['id_oferta_tutor']][] = $row;
        }

        return $rowsByOfferTutor;
    }

    private function enrolledOfferIds(int $studentId, array $offerIds): array
    {
        $offerIds = array_values(array_unique(array_filter(array_map('intval', $offerIds))));
        if (!$offerIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($offerIds), '?'));
        $statement = Database::connection()->prepare(
            "SELECT DISTINCT id_oferta
             FROM inscripciones_tutoria
             WHERE id_estudiante = ? AND estado = 'inscrita' AND id_oferta IN ($placeholders)"
        );
        $statement->execute(array_merge([$studentId], $offerIds));

        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    private function confirmedTutors(int $offerId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT ot.id_oferta_tutor, CONCAT(u.nombre, ' ', u.apellido) AS tutor,
                    t.especialidad, t.biografia, ot.estado
             FROM oferta_tutores ot
             INNER JOIN tutores t ON t.id_tutor = ot.id_tutor
             INNER JOIN usuarios u ON u.id_usuario = t.id_usuario AND u.estado = 'activo'
             WHERE ot.id_oferta = :id_oferta AND ot.estado = 'confirmada'
             ORDER BY u.apellido, u.nombre"
        );
        $statement->execute(['id_oferta' => $offerId]);

        return $statement->fetchAll();
    }

    private function replaceSchedules(int $offerId, array $schedules, PDO $pdo): void
    {
        $delete = $pdo->prepare('DELETE FROM oferta_horarios WHERE id_oferta = :id_oferta');
        $delete->execute(['id_oferta' => $offerId]);
        $insert = $pdo->prepare(
            'INSERT INTO oferta_horarios (id_oferta, dia_semana, id_aula)
             VALUES (:id_oferta, :dia_semana, :id_aula)'
        );
        foreach ($schedules as $schedule) {
            $day = trim((string) ($schedule['dia_semana'] ?? ''));
            if (!in_array($day, self::DAYS, true)) {
                continue;
            }
            $roomId = filter_var($schedule['id_aula'] ?? null, FILTER_VALIDATE_INT);
            $insert->execute([
                'id_oferta' => $offerId,
                'dia_semana' => $day,
                'id_aula' => $roomId !== false ? $roomId : null,
            ]);
        }
    }

    private function replaceDates(int $offerId, array $dates, PDO $pdo): void
    {
        $delete = $pdo->prepare('DELETE FROM oferta_tutoria_fechas WHERE id_oferta = :id_oferta');
        $delete->execute(['id_oferta' => $offerId]);
        $insert = $pdo->prepare(
            'INSERT INTO oferta_tutoria_fechas (id_oferta, fecha) VALUES (:id_oferta, :fecha)'
        );
        foreach ($dates as $date) {
            $insert->execute(['id_oferta' => $offerId, 'fecha' => $date]);
        }
    }
}
