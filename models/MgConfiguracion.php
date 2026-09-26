<?php

declare(strict_types=1);

final class MgConfiguracion
{
    private const STAGES = ['previa', 'mg1', 'mg2', 'finalizado'];
    private const EVIDENCE_STATES = ['confirmado', 'pendiente', 'propuesta'];

    public function summary(): array
    {
        return [
            'parametros' => (int) Database::connection()->query('SELECT COUNT(*) FROM mg_parametros')->fetchColumn(),
            'modalidades' => (int) Database::connection()->query("SELECT COUNT(*) FROM mg_modalidades WHERE estado = 'activa'")->fetchColumn(),
            'cohortes' => (int) Database::connection()->query('SELECT COUNT(*) FROM mg_cohortes WHERE activa = 1')->fetchColumn(),
            'hitos' => (int) Database::connection()->query("SELECT COUNT(*) FROM mg_calendario WHERE estado = 'activo'")->fetchColumn(),
        ];
    }

    public function parameters(): array
    {
        return Database::connection()->query(
            'SELECT clave, valor, tipo_dato, descripcion, fuente, estado_evidencia
             FROM mg_parametros ORDER BY clave'
        )->fetchAll();
    }

    public function parameter(string $key): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT clave, valor, tipo_dato FROM mg_parametros WHERE clave = :clave LIMIT 1'
        );
        $statement->execute(['clave' => $key]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    public function updateParameter(string $key, ?string $value, string $source, string $evidence, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE mg_parametros
             SET valor = :valor, fuente = :fuente, estado_evidencia = :estado_evidencia, actualizado_por = :actualizado_por
             WHERE clave = :clave'
        );
        $statement->execute([
            'valor' => $value,
            'fuente' => $source !== '' ? $source : null,
            'estado_evidencia' => $evidence,
            'actualizado_por' => $userId,
            'clave' => $key,
        ]);

        return $statement->rowCount() > 0 || $this->parameter($key) !== null;
    }

    public function modalities(): array
    {
        return Database::connection()->query(
            'SELECT id_modalidad, codigo, nombre, requiere_tutor, estado
             FROM mg_modalidades ORDER BY nombre'
        )->fetchAll();
    }

    public function saveModality(?int $id, string $code, string $name, bool $requiresTutor): void
    {
        if ($id === null) {
            $statement = Database::connection()->prepare(
                'INSERT INTO mg_modalidades (codigo, nombre, requiere_tutor) VALUES (:codigo, :nombre, :requiere_tutor)'
            );
            $statement->execute(['codigo' => $code, 'nombre' => $name, 'requiere_tutor' => $requiresTutor ? 1 : 0]);
            return;
        }

        $statement = Database::connection()->prepare(
            'UPDATE mg_modalidades SET codigo = :codigo, nombre = :nombre, requiere_tutor = :requiere_tutor WHERE id_modalidad = :id'
        );
        $statement->execute([
            'id' => $id,
            'codigo' => $code,
            'nombre' => $name,
            'requiere_tutor' => $requiresTutor ? 1 : 0,
        ]);
        if ($statement->rowCount() === 0 && !$this->modalityExists($id)) {
            throw new RuntimeException('La modalidad seleccionada no existe.');
        }
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
        if ($id === null) {
            $statement = Database::connection()->prepare(
                'INSERT INTO mg_cohortes (codigo, nombre, fecha_inicio, fecha_fin, creado_por)
                 VALUES (:codigo, :nombre, :fecha_inicio, :fecha_fin, :creado_por)'
            );
            $statement->execute([
                'codigo' => $code,
                'nombre' => $name,
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'creado_por' => $userId,
            ]);
            return;
        }

        $statement = Database::connection()->prepare(
            'UPDATE mg_cohortes SET codigo = :codigo, nombre = :nombre, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin
             WHERE id_cohorte = :id'
        );
        $statement->execute([
            'id' => $id,
            'codigo' => $code,
            'nombre' => $name,
            'fecha_inicio' => $start,
            'fecha_fin' => $end,
        ]);
        if ($statement->rowCount() === 0 && $this->cohort($id) === null) {
            throw new RuntimeException('La cohorte seleccionada no existe.');
        }
    }

    public function setCohortActive(int $id, bool $active): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE mg_cohortes SET activa = :activa WHERE id_cohorte = :id'
        );
        $statement->execute(['id' => $id, 'activa' => $active ? 1 : 0]);
        return $statement->rowCount() > 0 || $this->cohort($id) !== null;
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

    public function milestone(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id_hito, id_cohorte, etapa, tipo, nombre, orden, fecha_limite, avance_esperado_pct, estado
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

        if ($id === null) {
            $statement = Database::connection()->prepare(
                'INSERT INTO mg_calendario
                    (id_cohorte, etapa, tipo, nombre, orden, fecha_limite, avance_esperado_pct, creado_por)
                 VALUES
                    (:id_cohorte, :etapa, :tipo, :nombre, :orden, :fecha_limite, :avance_esperado_pct, :creado_por)'
            );
            $statement->execute($data + ['creado_por' => $userId]);
            return;
        }

        $statement = Database::connection()->prepare(
            'UPDATE mg_calendario
             SET id_cohorte = :id_cohorte, etapa = :etapa, tipo = :tipo, nombre = :nombre,
                 orden = :orden, fecha_limite = :fecha_limite, avance_esperado_pct = :avance_esperado_pct
             WHERE id_hito = :id'
        );
        $statement->execute($data + ['id' => $id]);
        if ($statement->rowCount() === 0 && !$this->milestoneExists($id)) {
            throw new RuntimeException('El hito seleccionado no existe.');
        }
    }

    public function setMilestoneActive(int $id, bool $active): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE mg_calendario SET estado = :estado WHERE id_hito = :id'
        );
        $statement->execute(['id' => $id, 'estado' => $active ? 'activo' : 'inactivo']);
        return $statement->rowCount() > 0 || $this->milestoneExists($id);
    }

    private function modalityExists(int $id): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM mg_modalidades WHERE id_modalidad = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    private function milestoneExists(int $id): bool
    {
        $statement = Database::connection()->prepare('SELECT 1 FROM mg_calendario WHERE id_hito = :id');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }
}
