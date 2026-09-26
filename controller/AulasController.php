<?php

declare(strict_types=1);

final class AulasController
{
    private Aula $model;

    public function __construct()
    {
        $this->model = new Aula();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function find(int $id): ?array
    {
        return $this->model->find($id);
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
                $errors[] = 'El aula ya existe o no se pudo guardar.';
            }
        }

        return [$data, $errors];
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input);
        $data['id_aula'] = $id;
        $errors = $this->validate($data);
        if (!$errors) {
            try {
                $this->model->update($id, $data);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = 'El aula ya existe o no se pudo actualizar.';
            }
        }

        return [$data, $errors];
    }

    private function normalize(array $input): array
    {
        $capacityInput = trim((string) ($input['capacidad'] ?? ''));

        return [
            'nombre_aula' => trim((string) ($input['nombre_aula'] ?? '')),
            'ubicacion' => trim((string) ($input['ubicacion'] ?? '')),
            'capacidad' => $capacityInput === '' ? null : filter_var($capacityInput, FILTER_VALIDATE_INT),
            'estado' => trim((string) ($input['estado'] ?? 'activa')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['nombre_aula'] === '' || strlen($data['nombre_aula']) > 100) {
            $errors[] = 'Ingrese un nombre de aula valido.';
        }
        if ($data['capacidad'] === false || ($data['capacidad'] !== null && $data['capacidad'] < 1)) {
            $errors[] = 'La capacidad debe ser mayor a cero.';
        }
        if (!in_array($data['estado'], ['activa', 'inactiva'], true)) {
            $errors[] = 'Seleccione un estado valido.';
        }

        return $errors;
    }
}
