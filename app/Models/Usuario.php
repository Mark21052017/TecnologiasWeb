<?php

declare(strict_types=1);

final class Usuario
{
    public function all(): array
    {
        $statement = Database::connection()->query(
            'SELECT u.id_usuario, u.id_rol, u.nombre, u.apellido, u.correo, u.usuario, u.telefono, u.estado, u.fecha_registro, r.nombre_rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol ORDER BY u.id_usuario DESC'
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT u.id_usuario, u.id_rol, u.nombre, u.apellido, u.correo, u.usuario, u.telefono, u.estado, r.nombre_rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id_usuario LIMIT 1'
        );
        $statement->execute(['id_usuario' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function roles(): array
    {
        $statement = Database::connection()->query(
            'SELECT id_rol, nombre_rol FROM roles ORDER BY nombre_rol'
        );

        return $statement->fetchAll();
    }

    public function create(array $data): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado) VALUES (:id_rol, :nombre, :apellido, :correo, :usuario, :contrasena_hash, :telefono, :estado)'
        );
        $statement->execute([
            'id_rol' => $data['id_rol'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'correo' => $data['correo'],
            'usuario' => $data['usuario'],
            'contrasena_hash' => password_hash($data['contrasena'], PASSWORD_DEFAULT),
            'telefono' => $data['telefono'] !== '' ? $data['telefono'] : null,
            'estado' => $data['estado'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'id_rol' => $data['id_rol'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'correo' => $data['correo'],
            'usuario' => $data['usuario'],
            'telefono' => $data['telefono'] !== '' ? $data['telefono'] : null,
            'estado' => $data['estado'],
            'id_usuario' => $id,
        ];

        $sql = 'UPDATE usuarios SET id_rol = :id_rol, nombre = :nombre, apellido = :apellido, correo = :correo, usuario = :usuario, telefono = :telefono, estado = :estado';
        if ($data['contrasena'] !== '') {
            $sql .= ', contrasena_hash = :contrasena_hash';
            $fields['contrasena_hash'] = password_hash($data['contrasena'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id_usuario = :id_usuario';

        $statement = Database::connection()->prepare($sql);
        $statement->execute($fields);
    }

    public function deactivate(int $id): bool
    {
        $statement = Database::connection()->prepare(
            "UPDATE usuarios SET estado = 'inactivo' WHERE id_usuario = :id_usuario AND estado = 'activo'"
        );
        $statement->execute(['id_usuario' => $id]);

        return $statement->rowCount() > 0;
    }

    public function findForLogin(string $username): ?array
    {
        $sql = <<<'SQL'
            SELECT
                u.id_usuario,
                u.id_rol,
                u.nombre,
                u.apellido,
                u.correo,
                u.usuario,
                u.contrasena_hash,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            WHERE u.usuario = :usuario
              AND u.estado = 'activo'
            LIMIT 1
        SQL;

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['usuario' => $username]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function registerAccess(int $userId, string $result): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO registro_accesos (id_usuario, ip_origen, resultado) VALUES (:id_usuario, :ip_origen, :resultado)'
        );
        $statement->execute([
            'id_usuario' => $userId,
            'ip_origen' => $_SERVER['REMOTE_ADDR'] ?? null,
            'resultado' => $result,
        ]);
    }
}
