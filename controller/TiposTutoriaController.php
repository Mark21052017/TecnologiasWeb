<?php

declare(strict_types=1);

final class TiposTutoriaController
{
    private TipoTutoria $model;

    public function __construct()
    {
        $this->model = new TipoTutoria();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function find(int $id): ?array
    {
        return $this->model->findById($id);
    }

    public function store(array $input): array
    {
        $data = $this->normalize($input, false);
        $errors = $this->validate($data);
        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->create($data['nombre'], $data['descripcion']);
            return [$data, []];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['No se pudo guardar el tipo de tutoria.']];
        }
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input, true);
        $errors = $this->validate($data, $id);
        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->update($id, $data['nombre'], $data['descripcion'], $data['estado']);
            return [$data, []];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['No se pudo actualizar el tipo de tutoria.']];
        }
    }

    public function deactivate(int $id): ?string
    {
        if (!$this->model->findById($id)) {
            return 'Tipo de tutoria no encontrado.';
        }

        try {
            $this->model->deactivate($id);
            return null;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo desactivar el tipo de tutoria.';
        }
    }

    private function normalize(array $input, bool $editing): array
    {
        $name = trim((string) ($input['nombre'] ?? ''));
        $description = trim((string) ($input['descripcion'] ?? ''));
        $state = $editing ? trim((string) ($input['estado'] ?? '')) : 'activo';

        return [
            'nombre' => preg_replace('/\s+/u', ' ', $name) ?? $name,
            'descripcion' => preg_replace('/\s+/u', ' ', $description) ?? $description,
            'estado' => $state,
        ];
    }

    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        $nameError = validation_name($data['nombre'], 'nombre del tipo de tutoria', 100);
        if ($nameError !== null) {
            $errors[] = $nameError;
        }
        $descriptionError = $data['descripcion'] === ''
            ? null
            : validation_text($data['descripcion'], 'descripcion', 500);
        if ($descriptionError !== null) {
            $errors[] = $descriptionError;
        }
        if (!in_array($data['estado'], ['activo', 'inactivo'], true)) {
            $errors[] = 'Seleccione un estado valido.';
        }
        if (!$errors && $this->model->nameExists($data['nombre'], $excludeId)) {
            $errors[] = 'Ya existe un tipo de tutoria con ese nombre.';
        }

        return $errors;
    }
}
