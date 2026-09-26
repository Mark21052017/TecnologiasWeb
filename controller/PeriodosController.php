<?php

declare(strict_types=1);

final class PeriodosController
{
    private PeriodoTutoria $model;

    public function __construct()
    {
        $this->model = new PeriodoTutoria();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function find(int $id): ?array
    {
        return $this->model->find($id);
    }

    public function options(): array
    {
        return ['tipos' => $this->model->typeOptions()];
    }

    public function store(array $input): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data);
        if (!$errors) {
            try {
                $this->model->create($data);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = 'El periodo ya existe o no se pudo guardar.';
            }
        }

        return [$data, $errors];
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data, $id);
        if (!$errors) {
            try {
                $this->model->update($id, $data);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = 'El periodo ya existe o no se pudo actualizar.';
            }
        }

        return [$data, $errors];
    }

    public function delete(int $id): ?string
    {
        try {
            $this->model->delete($id);
            return null;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se puede eliminar un periodo con ofertas asociadas.';
        }
    }

    private function normalize(array $input): array
    {
        return [
            'nombre_periodo' => trim((string) ($input['nombre_periodo'] ?? '')),
            'id_tipo_tutoria' => (int) ($input['id_tipo_tutoria'] ?? 0),
            'fecha_inicio' => trim((string) ($input['fecha_inicio'] ?? '')),
            'fecha_fin' => trim((string) ($input['fecha_fin'] ?? '')),
            'inscripcion_inicio' => trim((string) ($input['inscripcion_inicio'] ?? '')),
            'inscripcion_fin' => trim((string) ($input['inscripcion_fin'] ?? '')),
            'estado' => trim((string) ($input['estado'] ?? 'borrador')),
        ];
    }

    private function validate(array $data, ?int $id = null): array
    {
        $errors = [];
        if ($data['nombre_periodo'] === '' || strlen($data['nombre_periodo']) > 100) {
            $errors[] = 'Ingrese un nombre de periodo valido.';
        }

        if ($id !== null && $this->model->offerCount($id) > 0) {
            $period = $this->model->find($id);
            if ($period && (int) $period['id_tipo_tutoria'] !== $data['id_tipo_tutoria']) {
                $errors[] = 'El tipo de tutoria no puede cambiarse porque el periodo ya tiene ofertas.';
            }
        } elseif ($data['id_tipo_tutoria'] < 1) {
            $errors[] = 'Seleccione un tipo de tutoria.';
        } elseif (!$this->model->activeTypeExists($data['id_tipo_tutoria'])) {
            $errors[] = 'Seleccione un tipo de tutoria activo.';
        }

        $dates = [];
        foreach (['fecha_inicio', 'fecha_fin', 'inscripcion_inicio', 'inscripcion_fin'] as $field) {
            $date = DateTime::createFromFormat('!Y-m-d', $data[$field]);
            if (!$date || $date->format('Y-m-d') !== $data[$field]) {
                $errors[] = 'Todas las fechas del periodo deben ser validas.';
                break;
            }
            $dates[$field] = $data[$field];
        }
        if (isset($dates['fecha_inicio'], $dates['fecha_fin']) && $dates['fecha_fin'] < $dates['fecha_inicio']) {
            $errors[] = 'La fecha final debe ser posterior a la fecha inicial.';
        }
        if (isset($dates['inscripcion_inicio'], $dates['inscripcion_fin']) && $dates['inscripcion_fin'] < $dates['inscripcion_inicio']) {
            $errors[] = 'El cierre de inscripciones debe ser posterior a su inicio.';
        }
        if (isset($dates['inscripcion_inicio'], $dates['fecha_inicio']) && $dates['inscripcion_inicio'] > $dates['fecha_inicio']) {
            $errors[] = 'Las inscripciones deben iniciar antes del periodo.';
        }
        if (isset($dates['inscripcion_fin'], $dates['fecha_fin']) && $dates['inscripcion_fin'] > $dates['fecha_fin']) {
            $errors[] = 'Las inscripciones no pueden terminar despues del periodo.';
        }
        if (!in_array($data['estado'], ['borrador', 'publicado', 'cerrado', 'finalizado'], true)) {
            $errors[] = 'Seleccione un estado de periodo valido.';
        }

        return array_values(array_unique($errors));
    }
}
