<?php

declare(strict_types=1);

final class MgConfiguracion
{
    private const STAGES = ['previa', 'mg1', 'mg2', 'defensa', 'cierre', 'finalizado'];
    private const EVIDENCE_STATES = ['confirmado', 'pendiente', 'propuesta'];
    private const GENERAL_RULES = [
        'max_estudiantes_grupo' => 3,
        'tutor_max_estudiantes' => 3,
        'asistencia_minima_pct' => 80.0,
        'avance_requerido_defensa' => 100.0,
        'miembros_tribunal_predeterminado' => 3,
        'max_defensas' => 2,
    ];
    private static array $effectiveValues = [];

    public static function isGeneralRule(string $key): bool
    {
        return array_key_exists($key, self::GENERAL_RULES);
    }

    public function summary(): array
    {
        return [
            'parametros' => count($this->parameters()),
            'modalidades' => (int) Database::connection()->query("SELECT COUNT(*) FROM mg_modalidades WHERE estado = 'activa'")->fetchColumn(),
            'cohortes' => (int) Database::connection()->query('SELECT COUNT(*) FROM mg_cohortes WHERE activa = 1')->fetchColumn(),
            'hitos' => (int) Database::connection()->query("SELECT COUNT(*) FROM mg_calendario WHERE estado = 'activo'")->fetchColumn(),
        ];
    }

    public function parameters(): array
    {
        $keys = array_keys(self::GENERAL_RULES);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $statement = Database::connection()->prepare(
            'SELECT clave, valor, tipo_dato, descripcion, fuente, estado_evidencia,
                    categoria, valor_defecto, minimo, maximo, solo_lectura
             FROM mg_parametros WHERE visible=1 AND clave IN (' . $placeholders . ')
             ORDER BY FIELD(categoria, "General", "Seguimiento", "Tribunal", "Defensas"), clave'
        );
        $statement->execute($keys);
        return $statement->fetchAll();
    }

    public function parameterHistory(): array
    {
        return Database::connection()->query(
            'SELECT h.id_evento, h.valor_anterior, h.valor_nuevo,
                    h.fuente_anterior, h.fuente_nueva, h.evidencia_anterior, h.evidencia_nueva,
                    h.ocurrido_en, p.clave, CONCAT(u.nombre," ",u.apellido) AS actor
             FROM mg_parametros_historial h
             INNER JOIN mg_parametros p ON p.id_parametro=h.id_parametro
             INNER JOIN usuarios u ON u.id_usuario=h.id_actor
             ORDER BY h.ocurrido_en DESC, h.id_evento DESC LIMIT 100'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function effectiveValue(string $key, mixed $fallback = null): mixed
    {
        // Legacy/proposed settings remain in storage for audit, but never affect a process.
        if (!self::isGeneralRule($key)) {
            return $fallback;
        }
        $default = self::GENERAL_RULES[$key];
        if (array_key_exists($key, self::$effectiveValues)) {
            return self::$effectiveValues[$key];
        }
        $parameter = $this->parameter($key);
        if (!$parameter || $parameter['estado_evidencia'] !== 'confirmado' || $parameter['valor'] === null) {
            return self::$effectiveValues[$key] = $default;
        }
        $value = match ($parameter['tipo_dato']) {
            'entero' => filter_var($parameter['valor'], FILTER_VALIDATE_INT) !== false ? (int) $parameter['valor'] : $default,
            'decimal' => is_numeric($parameter['valor']) ? (float) $parameter['valor'] : $default,
            default => $default,
        };
        if (($parameter['minimo'] !== null && $value < (float) $parameter['minimo'])
            || ($parameter['maximo'] !== null && $value > (float) $parameter['maximo'])) {
            $value = $default;
        }
        return self::$effectiveValues[$key] = $value;
    }

    public function parameter(string $key): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_parametro, clave, valor, tipo_dato, descripcion, fuente, estado_evidencia,
                    categoria, valor_defecto, minimo, maximo, solo_lectura, visible
             FROM mg_parametros WHERE clave = :clave LIMIT 1'
        );
        $statement->execute(['clave' => $key]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function updateParameter(string $key, ?string $value, string $source, string $evidence, int $userId): bool
    {
        if (!self::isGeneralRule($key)) {
            throw new RuntimeException('Esta regla ya no es configurable desde Parámetros generales.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $currentQuery = $pdo->prepare(
                'SELECT id_parametro, valor, fuente, estado_evidencia, solo_lectura, visible
                 FROM mg_parametros WHERE clave = :clave FOR UPDATE'
            );
            $currentQuery->execute(['clave' => $key]);
            $current = $currentQuery->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                throw new RuntimeException('El parámetro seleccionado no existe.');
            }
            if ((int) $current['solo_lectura'] === 1 || (int) $current['visible'] !== 1) {
                throw new RuntimeException('Este parámetro es una regla fija del sistema y no se puede modificar.');
            }

            $source = $source !== '' ? $source : null;
            $changed = $current['valor'] !== $value
                || $current['fuente'] !== $source
                || $current['estado_evidencia'] !== $evidence;
            if ($changed) {
                $history = $pdo->prepare(
                    'INSERT INTO mg_parametros_historial
                        (id_parametro, valor_anterior, valor_nuevo, fuente_anterior, fuente_nueva,
                         evidencia_anterior, evidencia_nueva, id_actor)
                     VALUES (:id, :old_value, :new_value, :old_source, :new_source, :old_evidence, :new_evidence, :actor)'
                );
                $history->execute([
                    'id' => (int) $current['id_parametro'], 'old_value' => $current['valor'], 'new_value' => $value,
                    'old_source' => $current['fuente'], 'new_source' => $source,
                    'old_evidence' => $current['estado_evidencia'], 'new_evidence' => $evidence, 'actor' => $userId,
                ]);
                $update = $pdo->prepare(
                    'UPDATE mg_parametros
                     SET valor = :valor, fuente = :fuente, estado_evidencia = :estado_evidencia, actualizado_por = :actualizado_por
                     WHERE id_parametro = :id'
                );
                $update->execute([
                    'valor' => $value, 'fuente' => $source, 'estado_evidencia' => $evidence,
                    'actualizado_por' => $userId, 'id' => (int) $current['id_parametro'],
                ]);
                unset(self::$effectiveValues[$key]);
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

    public function modalities(): array
    {
        return Database::connection()->query(
            'SELECT id_modalidad, codigo, nombre, descripcion, requiere_tutor,
                    permite_trabajo_grupal, max_integrantes, requiere_tema_preliminar, requiere_descripcion,
                    requiere_informes, requiere_asistencia, asistencia_minima_pct,
                    requiere_mdg1, requiere_mdg2, requiere_informe_final, requiere_tribunal, requiere_defensa,
                    max_defensas, avance_requerido_defensa, impide_tutor_tribunal, miembros_minimos_tribunal,
                    min_interesados, promedio_minimo, estado
             FROM mg_modalidades ORDER BY nombre'
        )->fetchAll();
    }

    public function saveModality(?int $id, string $code, string $name, bool $requiresTutor, array $rules = []): void
    {
        if ($id === null) {
            $statement = Database::connection()->prepare(
                'INSERT INTO mg_modalidades
                    (codigo, nombre, descripcion, requiere_tutor, permite_trabajo_grupal, max_integrantes,
                     requiere_tema_preliminar, requiere_descripcion, requiere_informes, requiere_asistencia, asistencia_minima_pct,
                     requiere_mdg1, requiere_mdg2, requiere_informe_final, requiere_tribunal, requiere_defensa,
                     max_defensas, avance_requerido_defensa, impide_tutor_tribunal, miembros_minimos_tribunal,
                     min_interesados, promedio_minimo)
                 VALUES (:codigo, :nombre, :descripcion, :requiere_tutor, :grupal, :max_integrantes,
                     :requiere_tema, :requiere_descripcion, :requiere_informes, :requiere_asistencia, :asistencia_minima_pct,
                     :requiere_mdg1, :requiere_mdg2, :requiere_informe_final, :requiere_tribunal, :requiere_defensa,
                      :max_defensas, :avance_requerido_defensa, :impide_tutor_tribunal, :miembros_minimos_tribunal,
                      :min_interesados, :promedio_minimo)'
            );
            $statement->execute($this->modalityParameters($code, $name, $requiresTutor, $rules));
            return;
        }

        $statement = Database::connection()->prepare(
            'UPDATE mg_modalidades SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion,
                 requiere_tutor = :requiere_tutor, permite_trabajo_grupal = :grupal, max_integrantes = :max_integrantes,
                 requiere_tema_preliminar = :requiere_tema, requiere_descripcion = :requiere_descripcion,
                 requiere_informes = :requiere_informes, requiere_asistencia = :requiere_asistencia,
                 asistencia_minima_pct = :asistencia_minima_pct, requiere_mdg1 = :requiere_mdg1,
                 requiere_mdg2 = :requiere_mdg2, requiere_informe_final = :requiere_informe_final,
                 requiere_tribunal = :requiere_tribunal, requiere_defensa = :requiere_defensa,
                  max_defensas = :max_defensas, avance_requerido_defensa = :avance_requerido_defensa,
                  impide_tutor_tribunal = :impide_tutor_tribunal, miembros_minimos_tribunal = :miembros_minimos_tribunal,
                  min_interesados = :min_interesados, promedio_minimo = :promedio_minimo
             WHERE id_modalidad = :id'
        );
        $statement->execute($this->modalityParameters($code, $name, $requiresTutor, $rules) + ['id' => $id]);
        if ($statement->rowCount() === 0 && !$this->modalityExists($id)) {
            throw new RuntimeException('La modalidad seleccionada no existe.');
        }
    }

    private function modalityParameters(string $code, string $name, bool $requiresTutor, array $rules): array
    {
        return [
            'codigo' => $code,
            'nombre' => $name,
            'descripcion' => $rules['descripcion'] ?? null,
            'requiere_tutor' => $requiresTutor ? 1 : 0,
            'grupal' => !empty($rules['permite_trabajo_grupal']) ? 1 : 0,
            'max_integrantes' => $rules['max_integrantes'] ?? null,
            'requiere_tema' => !empty($rules['requiere_tema_preliminar']) ? 1 : 0,
            'requiere_descripcion' => !empty($rules['requiere_descripcion']) ? 1 : 0,
            'requiere_informes' => !empty($rules['requiere_informes']) ? 1 : 0,
            'requiere_asistencia' => !empty($rules['requiere_asistencia']) ? 1 : 0,
            'asistencia_minima_pct' => $rules['asistencia_minima_pct'] ?? null,
            'requiere_mdg1' => !empty($rules['requiere_mdg1']) ? 1 : 0,
            'requiere_mdg2' => !empty($rules['requiere_mdg2']) ? 1 : 0,
            'requiere_informe_final' => !empty($rules['requiere_informe_final']) ? 1 : 0,
            'requiere_tribunal' => !empty($rules['requiere_tribunal']) ? 1 : 0,
            'requiere_defensa' => !empty($rules['requiere_defensa']) ? 1 : 0,
            'max_defensas' => $rules['max_defensas'] ?? null,
            'avance_requerido_defensa' => $rules['avance_requerido_defensa'] ?? null,
            'impide_tutor_tribunal' => !array_key_exists('impide_tutor_tribunal', $rules) || $rules['impide_tutor_tribunal'] === null
                ? null
                : (!empty($rules['impide_tutor_tribunal']) ? 1 : 0),
            'miembros_minimos_tribunal' => $rules['miembros_minimos_tribunal'] ?? null,
            'min_interesados' => $rules['min_interesados'] ?? null,
            'promedio_minimo' => $rules['promedio_minimo'] ?? null,
        ];
    }

    public function setModalityActive(int $id, bool $active): bool
    {
        $statement = Database::connection()->prepare(
            "UPDATE mg_modalidades SET estado = :estado WHERE id_modalidad = :id"
        );
        $statement->execute(['id' => $id, 'estado' => $active ? 'activa' : 'inactiva']);
        return $statement->rowCount() > 0 || $this->modalityExists($id);
    }

    public function cohorts(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE c.activa = 1' : '';
        return Database::connection()->query(
            "SELECT c.id_cohorte, c.codigo, c.nombre, c.fecha_inicio, c.fecha_fin, c.activa,
                    COUNT(h.id_hito) AS total_hitos,
                    SUM(CASE WHEN h.tipo = 'informe' AND h.estado = 'activo' THEN 1 ELSE 0 END) AS total_informes
             FROM mg_cohortes c
             LEFT JOIN mg_calendario h ON h.id_cohorte = c.id_cohorte
             $where
             GROUP BY c.id_cohorte
             ORDER BY c.fecha_inicio DESC, c.codigo"
        )->fetchAll();
    }

    public function cohort(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_cohorte, codigo, nombre, fecha_inicio, fecha_fin, activa
             FROM mg_cohortes WHERE id_cohorte = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function saveCohort(?int $id, string $code, string $name, string $start, string $end, int $userId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $lockName = null;
        try {
            $before = null;
            if ($id !== null) {
                $currentQuery = $pdo->prepare('SELECT * FROM mg_cohortes WHERE id_cohorte=:id FOR UPDATE');
                $currentQuery->execute(['id' => $id]);
                $before = $currentQuery->fetch(PDO::FETCH_ASSOC);
                if (!$before) {
                    throw new RuntimeException('La cohorte seleccionada no existe.');
                }
                if ($before['fecha_inicio'] <= date('Y-m-d')
                    && ($before['fecha_inicio'] !== $start || $before['fecha_fin'] !== $end)
                    && !$this->effectiveValue('permitir_modificar_fechas_cohorte_iniciada', true)) {
                    throw new RuntimeException('La configuración no permite modificar fechas de una cohorte iniciada.');
                }
                $outOfRange = $pdo->prepare(
                    'SELECT COUNT(*) FROM mg_calendario
                     WHERE id_cohorte=:cohorte AND fecha_limite IS NOT NULL
                       AND (fecha_limite<:inicio OR fecha_limite>:fin)'
                );
                $outOfRange->execute(['cohorte' => $id, 'inicio' => $start, 'fin' => $end]);
                if ((int)$outOfRange->fetchColumn() > 0) {
                    throw new RuntimeException('Las nuevas fechas de cohorte dejarían hitos fuera del rango. Ajuste primero el calendario.');
                }
            }
            if ($id === null && $code === '') {
                $code = $this->nextCohortCode($pdo, $start, $lockName);
            }
            if ($id === null) {
                $statement = $pdo->prepare(
                    'INSERT INTO mg_cohortes (codigo, nombre, fecha_inicio, fecha_fin, creado_por)
                     VALUES (:codigo, :nombre, :fecha_inicio, :fecha_fin, :creado_por)'
                );
                $statement->execute([
                    'codigo' => $code, 'nombre' => $name, 'fecha_inicio' => $start, 'fecha_fin' => $end,
                    'creado_por' => $userId,
                ]);
                $id = (int)$pdo->lastInsertId();
                $this->auditCohortChange($pdo, $id, 'creada', null, [
                    'codigo' => $code, 'nombre' => $name, 'fecha_inicio' => $start, 'fecha_fin' => $end, 'activa' => 1,
                ], $userId);
            } else {
                $statement = $pdo->prepare(
                    'UPDATE mg_cohortes SET codigo = :codigo, nombre = :nombre, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin
                     WHERE id_cohorte = :id'
                );
                $statement->execute([
                    'id' => $id, 'codigo' => $code, 'nombre' => $name, 'fecha_inicio' => $start, 'fecha_fin' => $end,
                ]);
                if ($statement->rowCount() > 0) {
                    $this->auditCohortChange($pdo, $id, 'modificada', $before, [
                        'codigo' => $code, 'nombre' => $name, 'fecha_inicio' => $start, 'fecha_fin' => $end,
                        'activa' => (int)$before['activa'],
                    ], $userId);
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && (string)$exception->getCode() === '23000') {
                throw new RuntimeException('El código de cohorte ya existe. Recargue e intente otra vez.');
            }
            throw $exception;
        } finally {
            if ($lockName !== null) {
                $release = $pdo->prepare('SELECT RELEASE_LOCK(:name)');
                $release->execute(['name' => $lockName]);
            }
        }
    }

    public function setCohortActive(int $id, bool $active, int $userId): bool
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $query = $pdo->prepare('SELECT * FROM mg_cohortes WHERE id_cohorte=:id FOR UPDATE');
            $query->execute(['id' => $id]);
            $before = $query->fetch(PDO::FETCH_ASSOC);
            if (!$before) {
                $pdo->rollBack();
                return false;
            }
            $statement = $pdo->prepare('UPDATE mg_cohortes SET activa=:activa WHERE id_cohorte=:id');
            $statement->execute(['activa' => $active ? 1 : 0, 'id' => $id]);
            $after = $before;
            $after['activa'] = $active ? 1 : 0;
            if ($statement->rowCount() > 0) {
                $this->auditCohortChange($pdo, $id, $active ? 'activada' : 'desactivada', $before, $after, $userId);
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

    public function calendar(int $cohortId, bool $activeOnly = false): array
    {
        $whereStatus = $activeOnly ? " AND h.estado = 'activo'" : '';
        $statement = Database::connection()->prepare(
            "SELECT h.id_hito, h.id_cohorte, h.etapa, h.tipo, h.nombre, h.orden, h.fecha_limite,
                    h.avance_esperado_pct, h.estado, c.codigo AS codigo_cohorte, c.nombre AS nombre_cohorte
             FROM mg_calendario h
             INNER JOIN mg_cohortes c ON c.id_cohorte = h.id_cohorte
             WHERE h.id_cohorte = :id_cohorte $whereStatus
             ORDER BY h.orden, h.fecha_limite, h.nombre"
        );
        $statement->execute(['id_cohorte' => $cohortId]);

        return $statement->fetchAll();
    }

    public function calendarScope(int $cohortId, ?int $modalityId, bool $activeOnly = false): array
    {
        $whereModality = $modalityId === null ? 'h.id_modalidad IS NULL' : 'h.id_modalidad = :id_modalidad';
        $whereStatus = $activeOnly ? " AND h.estado = 'activo'" : '';
        $statement = Database::connection()->prepare(
            "SELECT h.id_hito, h.id_cohorte, h.id_modalidad, h.etapa, h.tipo, th.nombre AS tipo_nombre,
                    h.nombre, h.orden, h.fecha_limite, h.avance_esperado_pct, h.estado,
                    c.codigo AS codigo_cohorte, c.nombre AS nombre_cohorte, m.nombre AS modalidad,
                    COUNT(DISTINCT sh.id_seguimiento) AS obligaciones
             FROM mg_calendario h
             INNER JOIN mg_cohortes c ON c.id_cohorte = h.id_cohorte
             LEFT JOIN mg_modalidades m ON m.id_modalidad = h.id_modalidad
             LEFT JOIN mg_tipos_hito th ON th.codigo = h.tipo
             LEFT JOIN mg_seguimiento_hitos sh ON sh.id_hito = h.id_hito
             WHERE h.id_cohorte = :id_cohorte AND $whereModality $whereStatus
             GROUP BY h.id_hito, h.id_cohorte, h.id_modalidad, h.etapa, h.tipo, th.nombre,
                      h.nombre, h.orden, h.fecha_limite, h.avance_esperado_pct, h.estado,
                      c.codigo, c.nombre, m.nombre
             ORDER BY h.orden, h.fecha_limite, h.nombre"
        );
        $params = ['id_cohorte' => $cohortId];
        if ($modalityId !== null) {
            $params['id_modalidad'] = $modalityId;
        }
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function milestoneTypes(): array
    {
        return Database::connection()->query(
            "SELECT codigo, nombre FROM mg_tipos_hito WHERE estado = 'activo' ORDER BY orden, nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function milestoneTypeExists(string $code): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM mg_tipos_hito WHERE codigo = :codigo AND estado = "activo"');
        $statement->execute(['codigo' => $code]);
        return (bool) $statement->fetchColumn();
    }

    public function milestone(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_hito, id_cohorte, id_modalidad, etapa, tipo, nombre, orden, fecha_limite, avance_esperado_pct, estado
             FROM mg_calendario WHERE id_hito = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function activeCohort(int $id): bool
    {
        $statement = Database::connection()->prepare('SELECT activa FROM mg_cohortes WHERE id_cohorte = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    public function saveMilestone(?int $id, array $data, int $userId): void
    {
        if (!in_array($data['etapa'], self::STAGES, true)) {
            throw new InvalidArgumentException('Seleccione una etapa válida.');
        }
        if ($this->cohort((int) $data['id_cohorte']) === null) {
            throw new InvalidArgumentException('Seleccione una cohorte válida.');
        }
        $modality = Database::connection()->prepare('SELECT estado FROM mg_modalidades WHERE id_modalidad=:id');
        $modality->execute(['id' => (int) ($data['id_modalidad'] ?? 0)]);
        if ($modality->fetchColumn() !== 'activa') {
            throw new InvalidArgumentException('Seleccione una modalidad activa para el calendario.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            if ($id === null) {
                $statement = $pdo->prepare(
                    'INSERT INTO mg_calendario
                        (id_cohorte, id_modalidad, etapa, tipo, nombre, orden, fecha_limite, avance_esperado_pct, creado_por)
                     VALUES
                        (:id_cohorte, :id_modalidad, :etapa, :tipo, :nombre, :orden, :fecha_limite, :avance_esperado_pct, :creado_por)'
                );
                $statement->execute($data + ['creado_por' => $userId]);
                $id = (int) $pdo->lastInsertId();
                $this->auditCalendarChange($pdo, $id, 'creado', null, $data, $userId);
            } else {
                $scope = $pdo->prepare('SELECT * FROM mg_calendario WHERE id_hito=:id FOR UPDATE');
                $scope->execute(['id' => $id]);
                $current = $scope->fetch(PDO::FETCH_ASSOC);
                if (!$current) {
                    throw new RuntimeException('El hito seleccionado no existe.');
                }
                $obligations = $pdo->prepare('SELECT COUNT(*) FROM mg_seguimiento_hitos WHERE id_hito=:id');
                $obligations->execute(['id' => $id]);
                if ((int) $obligations->fetchColumn() > 0
                    && ((int) $current['id_cohorte'] !== (int) $data['id_cohorte']
                        || (int) ($current['id_modalidad'] ?? 0) !== (int) $data['id_modalidad'])) {
                    throw new RuntimeException('No se puede mover un hito que ya generó obligaciones; desactívelo y cree otro en la nueva cohorte/modalidad.');
                }
                $cohort = $this->cohort((int) $current['id_cohorte']);
                if ($cohort && $cohort['fecha_inicio'] <= date('Y-m-d')
                    && $current['fecha_limite'] !== $data['fecha_limite']
                    && !$this->effectiveValue('permitir_modificar_fechas_cohorte_iniciada', true)) {
                    throw new RuntimeException('La configuración no permite modificar fechas una vez iniciada la cohorte.');
                }
                $statement = $pdo->prepare(
                    'UPDATE mg_calendario
                     SET id_cohorte = :id_cohorte, id_modalidad = :id_modalidad, etapa = :etapa, tipo = :tipo, nombre = :nombre,
                         orden = :orden, fecha_limite = :fecha_limite, avance_esperado_pct = :avance_esperado_pct
                     WHERE id_hito = :id'
                );
                $statement->execute($data + ['id' => $id]);
                $changed = $statement->rowCount() > 0;
                if (!$changed && !$this->milestoneExists($id)) {
                    throw new RuntimeException('El hito seleccionado no existe.');
                }
                if ($changed) {
                    $this->auditCalendarChange($pdo, $id, 'modificado', $current, $data, $userId);
                }
            }
            $this->generateMilestoneObligations($pdo, $id);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function generateMilestoneObligations(PDO $pdo, int $milestoneId): void
    {
        $milestone = $pdo->prepare('SELECT id_cohorte,id_modalidad,fecha_limite,estado FROM mg_calendario WHERE id_hito=:id FOR UPDATE');
        $milestone->execute(['id' => $milestoneId]);
        $row = $milestone->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['estado'] !== 'activo' || $row['id_modalidad'] === null) {
            return;
        }
        $works = $pdo->prepare(
            'SELECT id_trabajo FROM mg_trabajos
             WHERE id_cohorte=:cohorte AND id_modalidad=:modalidad AND estado="activo"'
        );
        $works->execute(['cohorte' => (int) $row['id_cohorte'], 'modalidad' => (int) $row['id_modalidad']]);
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO mg_seguimiento_hitos (id_hito,id_trabajo,fecha_limite)
             VALUES (:hito,:trabajo,:limite)'
        );
        $update = $pdo->prepare(
            'UPDATE mg_seguimiento_hitos SET fecha_limite=:limite
             WHERE id_hito=:hito AND id_trabajo=:trabajo AND estado="pendiente"'
        );
        foreach ($works->fetchAll(PDO::FETCH_COLUMN) as $workId) {
            $insert->execute(['hito' => $milestoneId, 'trabajo' => (int) $workId, 'limite' => $row['fecha_limite']]);
            $update->execute(['limite' => $row['fecha_limite'], 'hito' => $milestoneId, 'trabajo' => (int) $workId]);
        }
    }

    public function templates(?int $modalityId = null): array
    {
        $sql = 'SELECT t.id_plantilla,t.id_modalidad,t.nombre,t.descripcion,t.estado,t.creado_en,m.nombre AS modalidad,
                       COUNT(ph.id_plantilla_hito) AS total_hitos
                FROM mg_plantillas_calendario t
                INNER JOIN mg_modalidades m ON m.id_modalidad=t.id_modalidad
                LEFT JOIN mg_plantilla_hitos ph ON ph.id_plantilla=t.id_plantilla AND ph.estado="activo"';
        $params = [];
        if ($modalityId !== null) {
            $sql .= ' WHERE t.id_modalidad=:modalidad';
            $params['modalidad'] = $modalityId;
        }
        $sql .= ' GROUP BY t.id_plantilla,t.id_modalidad,t.nombre,t.descripcion,t.estado,t.creado_en,m.nombre ORDER BY m.nombre,t.nombre';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createTemplateFromCalendar(int $cohortId, int $modalityId, string $name, string $description, int $userId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $cohort = $pdo->prepare('SELECT fecha_inicio,activa FROM mg_cohortes WHERE id_cohorte=:id FOR UPDATE');
            $cohort->execute(['id' => $cohortId]);
            $cohortRow = $cohort->fetch(PDO::FETCH_ASSOC);
            if (!$cohortRow || (int) $cohortRow['activa'] !== 1) {
                throw new RuntimeException('Seleccione una cohorte activa para crear la plantilla.');
            }
            $modality = $pdo->prepare('SELECT estado FROM mg_modalidades WHERE id_modalidad=:id');
            $modality->execute(['id' => $modalityId]);
            if ($modality->fetchColumn() !== 'activa') {
                throw new RuntimeException('Seleccione una modalidad activa.');
            }
            $milestones = $pdo->prepare(
                'SELECT etapa,tipo,nombre,orden,fecha_limite,avance_esperado_pct
                 FROM mg_calendario WHERE id_cohorte=:cohorte AND id_modalidad=:modalidad AND estado="activo"
                 ORDER BY orden,fecha_limite,nombre'
            );
            $milestones->execute(['cohorte' => $cohortId, 'modalidad' => $modalityId]);
            $rows = $milestones->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                throw new RuntimeException('No hay hitos activos para convertir en plantilla.');
            }
            $insert = $pdo->prepare('INSERT INTO mg_plantillas_calendario (id_modalidad,nombre,descripcion,creado_por) VALUES (:modalidad,:nombre,:descripcion,:usuario)');
            $insert->execute([
                'modalidad' => $modalityId, 'nombre' => $name, 'descripcion' => $description !== '' ? $description : null,
                'usuario' => $userId,
            ]);
            $templateId = (int) $pdo->lastInsertId();
            $save = $pdo->prepare(
                'INSERT INTO mg_plantilla_hitos (id_plantilla,etapa,tipo,nombre,orden,dias_desde_inicio,avance_esperado_pct)
                 VALUES (:plantilla,:etapa,:tipo,:nombre,:orden,:dias,:avance)'
            );
            foreach ($rows as $row) {
                $days = $row['fecha_limite'] === null
                    ? null
                    : (int) (new DateTimeImmutable($cohortRow['fecha_inicio']))->diff(new DateTimeImmutable($row['fecha_limite']))->days;
                $save->execute([
                    'plantilla' => $templateId, 'etapa' => $row['etapa'], 'tipo' => $row['tipo'],
                    'nombre' => $row['nombre'], 'orden' => $row['orden'], 'dias' => $days,
                    'avance' => $row['avance_esperado_pct'],
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof PDOException && (string) $exception->getCode() === '23000') {
                throw new RuntimeException('Ya existe una plantilla con ese nombre para esta modalidad.');
            }
            throw $exception;
        }
    }

    public function applyTemplate(int $templateId, int $cohortId, int $userId): int
    {
        if (!$this->effectiveValue('generar_hitos_desde_plantilla', true)) {
            throw new RuntimeException('La configuración actual no permite generar hitos desde plantillas.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $templateQuery = $pdo->prepare(
                'SELECT t.id_modalidad,t.estado,c.fecha_inicio,c.fecha_fin,c.activa
                 FROM mg_plantillas_calendario t CROSS JOIN mg_cohortes c
                 WHERE t.id_plantilla=:plantilla AND c.id_cohorte=:cohorte FOR UPDATE'
            );
            $templateQuery->execute(['plantilla' => $templateId, 'cohorte' => $cohortId]);
            $selection = $templateQuery->fetch(PDO::FETCH_ASSOC);
            if (!$selection || $selection['estado'] !== 'activa' || (int) $selection['activa'] !== 1) {
                throw new RuntimeException('Seleccione una plantilla y cohorte activas.');
            }
            $existing = $pdo->prepare('SELECT COUNT(*) FROM mg_calendario WHERE id_cohorte=:cohorte AND id_modalidad=:modalidad');
            $existing->execute(['cohorte' => $cohortId, 'modalidad' => (int) $selection['id_modalidad']]);
            if ((int) $existing->fetchColumn() > 0) {
                throw new RuntimeException('Ya hay un calendario creado para esta cohorte y modalidad; no se aplicó la plantilla para evitar duplicados.');
            }
            $items = $pdo->prepare('SELECT * FROM mg_plantilla_hitos WHERE id_plantilla=:id AND estado="activo" ORDER BY orden,id_plantilla_hito');
            $items->execute(['id' => $templateId]);
            $rows = $items->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) {
                throw new RuntimeException('La plantilla no contiene hitos activos.');
            }
            foreach ($rows as $row) {
                $dueDate = null;
                if ($row['dias_desde_inicio'] !== null) {
                    $dueDate = (new DateTimeImmutable($selection['fecha_inicio']))->modify('+' . (int) $row['dias_desde_inicio'] . ' days')->format('Y-m-d');
                    if ($dueDate < $selection['fecha_inicio'] || $dueDate > $selection['fecha_fin']) {
                        throw new RuntimeException('Una fecha derivada de la plantilla queda fuera del periodo de cohorte. Ajuste fechas o plantilla.');
                    }
                }
                $insert = $pdo->prepare(
                    'INSERT INTO mg_calendario
                        (id_cohorte,id_modalidad,etapa,tipo,nombre,orden,fecha_limite,avance_esperado_pct,creado_por)
                     VALUES (:cohorte,:modalidad,:etapa,:tipo,:nombre,:orden,:fecha,:avance,:usuario)'
                );
                $insert->execute([
                    'cohorte' => $cohortId, 'modalidad' => (int) $selection['id_modalidad'], 'etapa' => $row['etapa'],
                    'tipo' => $row['tipo'], 'nombre' => $row['nombre'], 'orden' => $row['orden'],
                    'fecha' => $dueDate, 'avance' => $row['avance_esperado_pct'], 'usuario' => $userId,
                ]);
                $milestoneId = (int) $pdo->lastInsertId();
                $this->auditCalendarChange($pdo, $milestoneId, 'generado_desde_plantilla', null, [
                    'id_cohorte' => $cohortId, 'id_modalidad' => (int)$selection['id_modalidad'],
                    'etapa' => $row['etapa'], 'tipo' => $row['tipo'], 'nombre' => $row['nombre'],
                    'orden' => $row['orden'], 'fecha_limite' => $dueDate,
                    'avance_esperado_pct' => $row['avance_esperado_pct'],
                ], $userId);
                $this->generateMilestoneObligations($pdo, $milestoneId);
            }
            $pdo->commit();
            return count($rows);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function setMilestoneActive(int $id, bool $active, int $userId): bool
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $beforeQuery = $pdo->prepare('SELECT * FROM mg_calendario WHERE id_hito=:id FOR UPDATE');
            $beforeQuery->execute(['id' => $id]);
            $before = $beforeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$before) {
                $pdo->rollBack();
                return false;
            }
            $statement = $pdo->prepare('UPDATE mg_calendario SET estado = :estado WHERE id_hito = :id');
            $statement->execute(['id' => $id, 'estado' => $active ? 'activo' : 'inactivo']);
            $after = $before;
            $after['estado'] = $active ? 'activo' : 'inactivo';
            if ($statement->rowCount() > 0) {
                $this->auditCalendarChange($pdo, $id, $active ? 'activado' : 'desactivado', $before, $after, $userId);
            }
            if ($active) {
                $this->generateMilestoneObligations($pdo, $id);
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

    private function modalityExists(int $id): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM mg_modalidades WHERE id_modalidad = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    private function auditCalendarChange(PDO $pdo, int $milestoneId, string $action, ?array $before, array $after, int $userId): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO mg_calendario_historial (id_hito,accion,antes,despues,id_actor)
             VALUES (:hito,:accion,:antes,:despues,:actor)'
        );
        $statement->execute([
            'hito' => $milestoneId,
            'accion' => $action,
            'antes' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'despues' => json_encode($after, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'actor' => $userId,
        ]);
    }

    private function nextCohortCode(PDO $pdo, string $startDate, ?string &$lockName): string
    {
        $prefix = 'MG';
        $year = substr($startDate, 0, 4);
        $lockName = 'mg-cohorte-' . $prefix . '-' . $year;
        $lock = $pdo->prepare('SELECT GET_LOCK(:name, 10)');
        $lock->execute(['name' => $lockName]);
        if ((int)$lock->fetchColumn() !== 1) {
            $lockName = null;
            throw new RuntimeException('No se pudo reservar el correlativo de cohorte. Intente nuevamente.');
        }

        $existing = $pdo->prepare('SELECT codigo FROM mg_cohortes WHERE codigo LIKE :like');
        $existing->execute(['like' => $prefix . '-' . $year . '-%']);
        $sequence = 0;
        $pattern = '/^' . preg_quote($prefix, '/') . '-' . preg_quote($year, '/') . '-(\d+)$/';
        foreach ($existing->fetchAll(PDO::FETCH_COLUMN) as $existingCode) {
            if (preg_match($pattern, (string)$existingCode, $match)) {
                $sequence = max($sequence, (int)$match[1]);
            }
        }
        $sequence++;
        return sprintf('%s-%s-%02d', $prefix, $year, $sequence);
    }

    private function auditCohortChange(PDO $pdo, int $cohortId, string $action, ?array $before, array $after, int $userId): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO mg_cohorte_historial (id_cohorte,accion,antes,despues,id_actor)
             VALUES (:cohorte,:accion,:antes,:despues,:actor)'
        );
        $statement->execute([
            'cohorte' => $cohortId, 'accion' => $action,
            'antes' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'despues' => json_encode($after, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'actor' => $userId,
        ]);
    }

    private function milestoneExists(int $id): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM mg_calendario WHERE id_hito = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }
}
