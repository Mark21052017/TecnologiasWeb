<?php

declare(strict_types=1);

final class MgConfiguracionController
{
    private MgConfiguracion $model;

    public function __construct()
    {
        $this->model = new MgConfiguracion();
    }

    public function summary(): array
    {
        return $this->model->summary();
    }

    public function parameters(): array
    {
        return $this->model->parameters();
    }

    public function updateParameter(array $input, int $userId): ?string
    {
        $key = trim((string) ($input['clave'] ?? ''));
        $parameter = $this->model->parameter($key);
        if (!$parameter) {
            return 'El parámetro seleccionado no existe.';
        }

        $rawValue = trim((string) ($input['valor'] ?? ''));
        $value = $rawValue === '' ? null : $rawValue;
        if ($value !== null) {
            $valid = match ($parameter['tipo_dato']) {
                'entero' => preg_match('/^-?\d+$/', $value) === 1,
                'decimal' => preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1,
                default => mb_strlen($value) <= 255,
            };
            if (!$valid) {
                return 'El valor no coincide con el tipo de dato del parámetro.';
            }
        }

        $evidence = trim((string) ($input['estado_evidencia'] ?? ''));
        if (!in_array($evidence, ['confirmado', 'pendiente', 'propuesta'], true)) {
            return 'Seleccione un estado de evidencia válido.';
        }
        $source = trim((string) ($input['fuente'] ?? ''));
        if (mb_strlen($source) > 255) {
            return 'La fuente no puede superar 255 caracteres.';
        }

        try {
            $this->model->updateParameter($key, $value, $source, $evidence, $userId);
            return null;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No se pudo actualizar el parámetro.';
        }
    }

    public function modalities(): array
    {
        return $this->model->modalities();
    }

    public function saveModality(array $input): ?string
    {
        $id = filter_var($input['id_modalidad'] ?? null, FILTER_VALIDATE_INT);
        $id = $id !== false && $id > 0 ? (int) $id : null;
        $code = strtoupper(trim((string) ($input['codigo'] ?? '')));
        $name = trim((string) ($input['nombre'] ?? ''));
        $requiresTutor = (string) ($input['requiere_tutor'] ?? '0') === '1';
        if (!preg_match('/^[A-Z0-9_]{3,60}$/', $code)) {
            return 'Use un código de 3 a 60 caracteres con letras, números y guion bajo.';
        }
        if ($name === '' || mb_strlen($name) > 150) {
            return 'Ingrese un nombre de modalidad válido (máximo 150 caracteres).';
        }

        try {
            $this->model->saveModality($id, $code, $name, $requiresTutor);
            return null;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'Ya existe una modalidad con ese código o nombre.';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No se pudo guardar la modalidad.';
        }
    }

    public function setModalityActive(array $input): ?string
    {
        $id = filter_var($input['id_modalidad'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || !in_array((string) ($input['estado'] ?? ''), ['activa', 'inactiva'], true)) {
            return 'La solicitud de estado de modalidad no es válida.';
        }
        return $this->model->setModalityActive((int) $id, $input['estado'] === 'activa')
            ? null
            : 'La modalidad seleccionada no existe.';
    }

    public function cohorts(bool $activeOnly = false): array
    {
        return $this->model->cohorts($activeOnly);
    }

    public function cohort(?int $id): ?array
    {
        return $id ? $this->model->cohort($id) : null;
    }

    public function saveCohort(array $input, int $userId): ?string
    {
        $id = filter_var($input['id_cohorte'] ?? null, FILTER_VALIDATE_INT);
        $id = $id !== false && $id > 0 ? (int) $id : null;
        $code = trim((string) ($input['codigo'] ?? ''));
        $name = trim((string) ($input['nombre'] ?? ''));
        $start = trim((string) ($input['fecha_inicio'] ?? ''));
        $end = trim((string) ($input['fecha_fin'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,39}$/', $code)) {
            return 'Ingrese un código de cohorte válido (2 a 40 caracteres).';
        }
        if ($name === '' || mb_strlen($name) > 150) {
            return 'Ingrese un nombre de cohorte válido (máximo 150 caracteres).';
        }
        if (!$this->validDate($start) || !$this->validDate($end) || $end < $start) {
            return 'Ingrese un rango de fechas válido para la cohorte.';
        }

        try {
            $this->model->saveCohort($id, $code, $name, $start, $end, $userId);
            return null;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'Ya existe una cohorte con ese código.';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No se pudo guardar la cohorte.';
        }
    }

    public function setCohortActive(array $input): ?string
    {
        $id = filter_var($input['id_cohorte'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || !in_array((string) ($input['activa'] ?? ''), ['0', '1'], true)) {
            return 'La solicitud de estado de cohorte no es válida.';
        }
        return $this->model->setCohortActive((int) $id, $input['activa'] === '1')
            ? null
            : 'La cohorte seleccionada no existe.';
    }

    public function calendar(int $cohortId, bool $activeOnly = false): array
    {
        return $this->model->calendar($cohortId, $activeOnly);
    }

    public function milestone(?int $id): ?array
    {
        return $id ? $this->model->milestone($id) : null;
    }

    public function saveMilestone(array $input, int $userId): ?string
    {
        $id = filter_var($input['id_hito'] ?? null, FILTER_VALIDATE_INT);
        $id = $id !== false && $id > 0 ? (int) $id : null;
        $cohortId = filter_var($input['id_cohorte'] ?? null, FILTER_VALIDATE_INT);
        $order = filter_var($input['orden'] ?? null, FILTER_VALIDATE_INT);
        $stage = trim((string) ($input['etapa'] ?? ''));
        $type = strtolower(trim((string) ($input['tipo'] ?? '')));
        $name = trim((string) ($input['nombre'] ?? ''));
        $date = trim((string) ($input['fecha_limite'] ?? ''));
        $expected = trim((string) ($input['avance_esperado_pct'] ?? ''));
        if ($cohortId === false || $cohortId < 1 || $order === false || $order < 0) {
            return 'Seleccione una cohorte y un orden válidos.';
        }
        $cohort = $this->model->cohort((int) $cohortId);
        if (!$cohort || (int) $cohort['activa'] !== 1) {
            return 'Seleccione una cohorte activa.';
        }
        if (!$this->model->activeCohort((int) $cohortId)) {
            return 'No se pueden añadir o modificar hitos en una cohorte inactiva.';
        }
        if (!preg_match('/^[a-z][a-z0-9_]{1,39}$/', $type)) {
            return 'El tipo de hito debe usar letras minúsculas, números y guion bajo.';
        }
        if ($name === '' || mb_strlen($name) > 180) {
            return 'Ingrese un nombre de hito válido (máximo 180 caracteres).';
        }
        if ($date !== '' && !$this->validDate($date)) {
            return 'Ingrese una fecha límite válida.';
        }
        if ($date !== '' && ($date < $cohort['fecha_inicio'] || $date > $cohort['fecha_fin'])) {
            return 'La fecha límite del hito debe estar dentro del periodo de la cohorte.';
        }
        $expectedValue = null;
        if ($expected !== '') {
            if (!is_numeric($expected) || (float) $expected < 0 || (float) $expected > 100) {
                return 'El avance esperado debe estar entre 0 y 100.';
            }
            $expectedValue = number_format((float) $expected, 2, '.', '');
        }

        try {
            $this->model->saveMilestone($id, [
                'id_cohorte' => (int) $cohortId,
                'etapa' => $stage,
                'tipo' => $type,
                'nombre' => $name,
                'orden' => (int) $order,
                'fecha_limite' => $date !== '' ? $date : null,
                'avance_esperado_pct' => $expectedValue,
            ], $userId);
            return null;
        } catch (InvalidArgumentException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo guardar el hito del calendario.';
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No se pudo guardar el hito del calendario.';
        }
    }

    public function setMilestoneActive(array $input): ?string
    {
        $id = filter_var($input['id_hito'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || !in_array((string) ($input['estado'] ?? ''), ['activo', 'inactivo'], true)) {
            return 'La solicitud de estado del hito no es válida.';
        }
        return $this->model->setMilestoneActive((int) $id, $input['estado'] === 'activo')
            ? null
            : 'El hito seleccionado no existe.';
    }

    private function validDate(string $value): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
