<?php

declare(strict_types=1);

final class Perfil
{
    public function findByUserId(int $userId): ?array
    {
        $statement = Database::connection()->prepare(
            <<<'SQL'
                SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.usuario,
                       u.telefono, u.estado, u.fecha_registro, u.foto_perfil,
                       r.nombre_rol, t.especialidad, t.biografia,
                       e.semestre, e.registro_universitario, c.nombre_carrera
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                LEFT JOIN tutores t ON t.id_usuario = u.id_usuario
                LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
                LEFT JOIN carreras c ON c.id_carrera = e.id_carrera
                WHERE u.id_usuario = :id_usuario
                  AND r.nombre_rol IN ('tutor', 'estudiante')
                LIMIT 1
            SQL
        );
        $statement->execute(['id_usuario' => $userId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function publicTutorProfile(int $tutorId): ?array
    {
        $statement = Database::connection()->prepare(
            <<<'SQL'
                SELECT t.id_tutor, u.nombre, u.apellido, t.especialidad, t.biografia
                FROM tutores t
                INNER JOIN usuarios u ON u.id_usuario = t.id_usuario
                    AND u.estado = 'activo'
                WHERE t.id_tutor = :id_tutor
                  AND EXISTS (
                      SELECT 1
                      FROM oferta_tutores ot
                      INNER JOIN ofertas_tutoria o ON o.id_oferta = ot.id_oferta
                      INNER JOIN periodos_tutoria p ON p.id_periodo = o.id_periodo
                      WHERE ot.id_tutor = t.id_tutor
                        AND ot.estado = 'confirmada'
                        AND o.estado = 'publicada'
                        AND p.estado IN ('publicado', 'cerrado')
                        AND p.fecha_fin >= CURRENT_DATE
                  )
                LIMIT 1
            SQL
        );
        $statement->execute(['id_tutor' => $tutorId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function emailExists(string $email, int $excludeUserId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM usuarios WHERE LOWER(TRIM(correo)) = LOWER(TRIM(:correo)) AND id_usuario <> :id_usuario LIMIT 1'
        );
        $statement->execute(['correo' => $email, 'id_usuario' => $excludeUserId]);

        return (bool) $statement->fetchColumn();
    }

    public function updatePersonalData(int $userId, string $role, array $data): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correo = :correo, telefono = :telefono WHERE id_usuario = :id_usuario'
            );
            $statement->execute([
                'id_usuario' => $userId,
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo' => $data['correo'],
                'telefono' => $data['telefono'] !== '' ? $data['telefono'] : null,
            ]);

            if ($role === 'tutor') {
                $statement = $pdo->prepare(
                    'UPDATE tutores SET especialidad = :especialidad, biografia = :biografia WHERE id_usuario = :id_usuario'
                );
                $statement->execute([
                    'id_usuario' => $userId,
                    'especialidad' => $data['especialidad'],
                    'biografia' => $data['biografia'] !== '' ? $data['biografia'] : null,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function passwordHash(int $userId): ?string
    {
        $statement = Database::connection()->prepare(
            'SELECT contrasena_hash FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1'
        );
        $statement->execute(['id_usuario' => $userId]);
        $hash = $statement->fetchColumn();

        return is_string($hash) ? $hash : null;
    }

    public function updatePassword(int $userId, string $password): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE usuarios SET contrasena_hash = :contrasena_hash WHERE id_usuario = :id_usuario'
        );
        $statement->execute([
            'id_usuario' => $userId,
            'contrasena_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    public function updatePhoto(int $userId, ?string $filename): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE usuarios SET foto_perfil = :foto_perfil WHERE id_usuario = :id_usuario'
        );
        $statement->execute(['id_usuario' => $userId, 'foto_perfil' => $filename]);
    }
}
