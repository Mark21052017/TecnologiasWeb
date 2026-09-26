<?php

declare(strict_types=1);

final class TurnosController
{
    private Turno $model;

    public function __construct()
    {
        $this->model = new Turno();
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
                $errors[] = 'El turno ya existe o no se pudo guardar.';
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
            return 'No se puede eliminar un turno utilizado por una oferta.';
        }
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input);
        $data['id_turno'] = $id;
        $errors = $this->validate($data);
        if (!$errors) {
            try {
                $this->model->update($id, $data);
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = 'El turno ya existe o no se pudo actualizar.';
            }
        }

        return [$data, $errors];
    }

    private function normalize(array $input): array
    {
        return [
            'nombre_turno' => trim((string) ($input['nombre_turno'] ?? '')),
            'hora_inicio' => trim((string) ($input['hora_inicio'] ?? '')),
            'hora_fin' => trim((string) ($input['hora_fin'] ?? '')),
            'estado' => trim((string) ($input['estado'] ?? 'activo')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['nombre_turno'] === '' || strlen($data['nombre_turno']) > 80) {
            $errors[] = 'Ingrese un nombre de turno valido.';
        }
        $startError = validation_time($data['hora_inicio'], 'hora inicial');
        $endError = validation_time($data['hora_fin'], 'hora final');
        if ($startError !== null || $endError !== null) {
            $errors[] = 'Ingrese horarios validos.';
        } elseif ($data['hora_fin'] <= $data['hora_inicio']) {
            $errors[] = 'La hora final debe ser posterior a la inicial.';
        }
        if (!in_array($data['estado'], ['activo', 'inactivo'], true)) {
            $errors[] = 'Seleccione un estado valido.';
        }

        return $errors;
    }
}
