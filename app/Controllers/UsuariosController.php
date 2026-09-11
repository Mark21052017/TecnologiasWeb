<?php

declare(strict_types=1);

final class UsuariosController
{
    private Usuario $model;

    public function __construct()
    {
        $this->model = new Usuario();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function roles(): array
    {
        return $this->model->roles();
    }

    public function find(int $id): ?array
    {
        return $this->model->findById($id);
    }

    public function store(array $input): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data, false);

        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->create($data);
            return [$data, []];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['El correo o el usuario ya pueden estar registrados.']];
        }
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input);
        $errors = $this->validate($data, true);

        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->update($id, $data);
            return [$data, []];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['El correo o el usuario ya pueden estar registrados.']];
        }
    }

    public function deactivate(int $id): ?string
    {
        $currentUser = Auth::user();
        if ((int) ($currentUser['id_usuario'] ?? 0) === $id) {
            return 'No puede desactivar su propia cuenta.';
        }

        try {
            return $this->model->deactivate($id) ? null : 'El usuario ya estaba inactivo o no existe.';
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No fue posible desactivar el usuario.';
        }
    }

    private function normalize(array $input): array
    {
        return [
            'id_rol' => trim((string) ($input['id_rol'] ?? '')),
            'nombre' => trim((string) ($input['nombre'] ?? '')),
            'apellido' => trim((string) ($input['apellido'] ?? '')),
            'correo' => trim((string) ($input['correo'] ?? '')),
            'usuario' => trim((string) ($input['usuario'] ?? '')),
            'contrasena' => (string) ($input['contrasena'] ?? ''),
            'telefono' => trim((string) ($input['telefono'] ?? '')),
            'estado' => trim((string) ($input['estado'] ?? 'activo')),
        ];
    }

    private function validate(array $data, bool $editing): array
    {
        $errors = [];
        $roleId = filter_var($data['id_rol'], FILTER_VALIDATE_INT);

        if ($roleId === false || $roleId < 1) {
            $errors[] = 'Seleccione un rol valido.';
        }
        if ($data['nombre'] === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($data['apellido'] === '') {
            $errors[] = 'El apellido es obligatorio.';
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingrese un correo valido.';
        }
        if ($data['usuario'] === '') {
            $errors[] = 'El usuario es obligatorio.';
        }
        if (!$editing && $data['contrasena'] === '') {
            $errors[] = 'La contrasena es obligatoria.';
        }
        if ($data['contrasena'] !== '' && strlen($data['contrasena']) < 6) {
            $errors[] = 'La contrasena debe tener al menos 6 caracteres.';
        }
        if (!in_array($data['estado'], ['activo', 'inactivo'], true)) {
            $errors[] = 'Seleccione un estado valido.';
        }

        return $errors;
    }
}
