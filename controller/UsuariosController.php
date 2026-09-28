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

    public function careers(): array
    {
        return (new Estudiante())->careers();
    }

    public function find(int $id): ?array
    {
        return $this->model->findById($id);
    }

    public function isProtectedAdmin(int $id): bool
    {
        return $this->model->isProtectedAdmin($id);
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
        } catch (RuntimeException $exception) {
            return [$data, [$exception->getMessage()]];
        }
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input);
        if ($this->isProtectedAdmin($id)) {
            return [$data, ['La cuenta admin esta protegida y no puede modificarse.']];
        }
        $current = $this->model->findById($id);
        $errors = $this->validate($data, true, $current ?: null);

        if ($errors) {
            $newRoleId = filter_var($data['id_rol'], FILTER_VALIDATE_INT);
            if ($current && $newRoleId !== false && (int) $current['id_rol'] !== (int) $newRoleId
                && (!empty($current['id_estudiante']) || !empty($current['id_tutor']))) {
                $data['id_rol'] = (string) $current['id_rol'];
                $data['id_carrera'] = (string) ($current['id_carrera'] ?? '');
                $data['semestre'] = (string) ($current['semestre'] ?? '');
                $data['especialidad'] = (string) ($current['especialidad'] ?? '');
                $data['biografia'] = (string) ($current['biografia'] ?? '');
            }
            return [$data, $errors];
        }

        try {
            $this->model->update($id, $data);
            return [$data, []];
        } catch (RuntimeException $exception) {
            return [$data, [$exception->getMessage()]];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['El correo o el usuario ya pueden estar registrados.']];
        }
    }

    public function deactivate(int $id): ?string
    {
        if ($this->isProtectedAdmin($id)) {
            return 'La cuenta admin esta protegida y no puede desactivarse.';
        }

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

    public function activate(int $id): ?string
    {
        if ($this->isProtectedAdmin($id)) {
            return 'La cuenta admin esta protegida y no puede modificarse.';
        }

        try {
            $reviewerId = (int) (Auth::user()['id_usuario'] ?? 0);
            return $this->model->activate($id, $reviewerId > 0 ? $reviewerId : null) ? null : 'La cuenta ya estaba activa o no existe.';
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No fue posible activar la cuenta.';
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
            'confirmacion' => (string) ($input['confirmacion'] ?? ''),
            'telefono' => trim((string) ($input['telefono'] ?? '')),
            'estado' => trim((string) ($input['estado'] ?? 'activo')),
            'id_carrera' => trim((string) ($input['id_carrera'] ?? '')),
            'semestre' => trim((string) ($input['semestre'] ?? '')),
            'especialidad' => trim((string) ($input['especialidad'] ?? '')),
            'biografia' => trim((string) ($input['biografia'] ?? '')),
        ];
    }

    private function validate(array $data, bool $editing, ?array $current = null): array
    {
        $errors = [];
        $roleId = filter_var($data['id_rol'], FILTER_VALIDATE_INT);
        $roleName = $roleId !== false && $roleId > 0 ? $this->model->roleName((int) $roleId) : null;

        if ($roleName === null) {
            $errors[] = 'Seleccione un rol valido.';
        } elseif ($editing && $current && $current['nombre_rol'] !== $roleName
            && (!empty($current['id_estudiante']) || !empty($current['id_tutor']))) {
            $errors[] = 'No se puede cambiar el rol de una cuenta con perfil académico. Conserve el rol o cree otra cuenta.';
        }
        foreach ([['value' => $data['nombre'], 'label' => 'nombre'], ['value' => $data['apellido'], 'label' => 'apellido']] as $personField) {
            $error = validation_name($personField['value'], $personField['label']);
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ingrese un correo valido.';
        }
        $usernameError = validation_username($data['usuario']);
        if ($usernameError !== null) {
            $errors[] = $usernameError;
        }
        $phoneError = validation_phone($data['telefono']);
        if ($phoneError !== null) {
            $errors[] = $phoneError;
        }
        if (!$editing && $data['contrasena'] === '') {
            $errors[] = 'La contrasena es obligatoria.';
        }
        if ($data['contrasena'] !== '' && strlen($data['contrasena']) < 6) {
            $errors[] = 'La contrasena debe tener al menos 6 caracteres.';
        }
        if (($data['contrasena'] !== '' || $data['confirmacion'] !== '') && $data['contrasena'] !== $data['confirmacion']) {
            $errors[] = 'Las contrasenas no coinciden.';
        }
        if (!in_array($data['estado'], ['pendiente', 'activo', 'inactivo'], true)) {
            $errors[] = 'Seleccione un estado valido.';
        }

        if ($roleName === 'estudiante') {
            $careerId = filter_var($data['id_carrera'], FILTER_VALIDATE_INT);
            $semester = filter_var($data['semestre'], FILTER_VALIDATE_INT);
            if ($careerId === false || $careerId < 1 || !(new Estudiante())->careerExists((int) $careerId)) {
                $errors[] = 'Seleccione una carrera válida para el perfil del estudiante.';
            }
            if ($semester === false || $semester < 1 || $semester > 20) {
                $errors[] = 'El semestre del estudiante debe estar entre 1 y 20.';
            }
        } elseif ($roleName === 'tutor') {
            if (mb_strlen($data['especialidad']) > 150) {
                $errors[] = 'La especialidad no puede superar 150 caracteres.';
            }
            if (mb_strlen($data['biografia']) > 2000 || preg_match('/[\x00-\x1F\x7F]/', $data['biografia'])) {
                $errors[] = 'La biografía no puede superar 2000 caracteres ni contener caracteres no válidos.';
            }
        }

        return $errors;
    }
}
