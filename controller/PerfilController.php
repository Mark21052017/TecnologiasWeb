<?php

declare(strict_types=1);

final class PerfilController
{
    private const MAX_PHOTO_SIZE = 2097152;
    private const PHOTO_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private Perfil $model;

    public function __construct()
    {
        $this->model = new Perfil();
    }

    public function find(int $userId): ?array
    {
        return $this->model->findByUserId($userId);
    }

    public function updatePersonalData(int $userId, string $role, array $input): array
    {
        $data = [
            'nombre' => trim((string) ($input['nombre'] ?? '')),
            'apellido' => trim((string) ($input['apellido'] ?? '')),
            'correo' => trim((string) ($input['correo'] ?? '')),
            'telefono' => trim((string) ($input['telefono'] ?? '')),
            'especialidad' => trim((string) ($input['especialidad'] ?? '')),
            'biografia' => trim((string) ($input['biografia'] ?? '')),
        ];
        $errors = [];

        foreach (['nombre', 'apellido'] as $field) {
            $error = validation_name($data[$field], $field, 100);
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL) || strlen($data['correo']) > 150) {
            $errors[] = 'Ingrese un correo valido.';
        } elseif ($this->model->emailExists($data['correo'], $userId)) {
            $errors[] = 'El correo ya esta registrado por otro usuario.';
        }
        $phoneError = validation_phone($data['telefono']);
        if ($phoneError !== null) {
            $errors[] = $phoneError;
        }
        if ($role === 'tutor') {
            $specialtyError = validation_text($data['especialidad'], 'especialidad', 150);
            if ($specialtyError !== null) {
                $errors[] = $specialtyError;
            }
            if (strlen($data['biografia']) > 2000 || preg_match('/[\x00-\x1F\x7F]/', $data['biografia'])) {
                $errors[] = 'La biografia no puede superar 2000 caracteres ni contener caracteres no validos.';
            }
        }
        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->updatePersonalData($userId, $role, $data);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['No fue posible actualizar el perfil.']];
        }

        return [$data, []];
    }

    public function changePassword(int $userId, array $input): array
    {
        $current = (string) ($input['contrasena_actual'] ?? '');
        $new = (string) ($input['contrasena_nueva'] ?? '');
        $confirmation = (string) ($input['confirmacion'] ?? '');
        $errors = [];
        $hash = $this->model->passwordHash($userId);

        if ($hash === null || !password_verify($current, $hash)) {
            $errors[] = 'La contrasena actual no es correcta.';
        }
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
            $errors[] = 'La nueva contrasena debe tener al menos 8 caracteres, una letra y un numero.';
        }
        if ($new !== $confirmation) {
            $errors[] = 'Las contrasenas nuevas no coinciden.';
        }
        if ($errors) {
            return $errors;
        }

        try {
            $this->model->updatePassword($userId, $new);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return ['No fue posible cambiar la contrasena.'];
        }

        return [];
    }

    public function uploadPhoto(int $userId, array $file, string $storagePath, bool $syncSession = true): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Seleccione una fotografia.';
        }
        if ($error !== UPLOAD_ERR_OK) {
            return 'No fue posible recibir la fotografia.';
        }
        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_PHOTO_SIZE) {
            return 'La fotografia no puede superar 2 MB.';
        }
        if (!is_uploaded_file($temporaryPath)) {
            return 'El archivo recibido no es valido.';
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extension = self::PHOTO_TYPES[$mimeType] ?? null;
        if ($extension === null || @getimagesize($temporaryPath) === false) {
            return 'La fotografia debe ser JPG, PNG o WebP.';
        }
        if (!is_dir($storagePath) && !mkdir($storagePath, 0775, true) && !is_dir($storagePath)) {
            return 'No fue posible preparar el almacenamiento de fotografias.';
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $storagePath . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $destination)) {
            return 'No fue posible guardar la fotografia.';
        }

        $profile = $this->model->findByUserId($userId);
        try {
            $this->model->updatePhoto($userId, $filename);
        } catch (PDOException $exception) {
            @unlink($destination);
            error_log($exception->getMessage());
            return 'No fue posible asociar la fotografia al perfil.';
        }
        $this->deleteStoredPhoto($storagePath, $profile['foto_perfil'] ?? null);
        if ($syncSession && (int) ($_SESSION['user']['id_usuario'] ?? 0) === $userId) {
            $_SESSION['user']['foto_perfil'] = $filename;
        }

        return null;
    }

    public function removePhoto(int $userId, string $storagePath, bool $syncSession = true): ?string
    {
        $profile = $this->model->findByUserId($userId);
        if (!$profile) {
            return 'Perfil no encontrado.';
        }

        try {
            $this->model->updatePhoto($userId, null);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No fue posible eliminar la fotografia.';
        }
        $this->deleteStoredPhoto($storagePath, $profile['foto_perfil'] ?? null);
        if ($syncSession && (int) ($_SESSION['user']['id_usuario'] ?? 0) === $userId) {
            $_SESSION['user']['foto_perfil'] = null;
        }

        return null;
    }

    private function deleteStoredPhoto(string $storagePath, mixed $filename): void
    {
        if (!is_string($filename) || $filename === '' || basename($filename) !== $filename) {
            return;
        }
        $path = $storagePath . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
