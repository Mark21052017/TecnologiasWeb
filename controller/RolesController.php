<?php

declare(strict_types=1);

final class RolesController
{
    private Rol $model;

    public function __construct()
    {
        $this->model = new Rol();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function find(int $id): ?array
    {
        return $this->model->findById($id);
    }

    public function isProtectedAdminRole(int $id): bool
    {
        return $this->model->isProtectedAdminRole($id);
    }

    public function store(array $input): array
    {
        $data = ['nombre_rol' => trim((string) ($input['nombre_rol'] ?? ''))];
        $errors = $this->validate($data);

        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->create($data['nombre_rol']);
            return [$data, []];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['El nombre del rol ya existe.']];
        }
    }

    public function update(int $id, array $input): array
    {
        $data = ['nombre_rol' => trim((string) ($input['nombre_rol'] ?? ''))];
        if ($this->isProtectedAdminRole($id)) {
            return [$data, ['El rol administrador esta protegido y no puede modificarse.']];
        }
        $errors = $this->validate($data);

        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->update($id, $data['nombre_rol']);
            return [$data, []];
        } catch (RuntimeException $exception) {
            return [$data, [$exception->getMessage()]];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['El nombre del rol ya existe.']];
        }
    }

    public function delete(int $id): ?string
    {
        if ($this->isProtectedAdminRole($id)) {
            return 'El rol administrador esta protegido y no puede eliminarse.';
        }

        try {
            $this->model->delete($id);
            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se puede eliminar el rol porque tiene usuarios asociados.';
        }
    }

    private function validate(array $data): array
    {
        $error = validation_text($data['nombre_rol'], 'nombre del rol', 30);
        return $error === null ? [] : [$error];
    }
}
