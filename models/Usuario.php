<?php

declare(strict_types=1);

final class Usuario
{
    private const PROTECTED_ADMIN_ID = 1;
    private const PROTECTED_ADMIN_USERNAME = 'admin';

    public function all(): array
    {
        $statement = Database::connection()->query(
            "SELECT u.id_usuario, u.id_rol, u.nombre, u.apellido, u.correo, u.usuario, u.telefono, u.estado, u.fecha_registro, r.nombre_rol,
                    CASE WHEN r.nombre_rol = 'administrador' AND (u.id_usuario = 1 OR u.usuario = 'admin') THEN 1 ELSE 0 END AS es_admin_protegido,
                    (SELECT e.id_estudiante FROM estudiantes e WHERE e.id_usuario = u.id_usuario LIMIT 1) AS id_estudiante,
                    (SELECT t.id_tutor FROM tutores t WHERE t.id_usuario = u.id_usuario LIMIT 1) AS id_tutor
             FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol ORDER BY u.id_usuario DESC"
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT u.id_usuario, u.id_rol, u.nombre, u.apellido, u.correo, u.usuario, u.telefono, u.estado, u.foto_perfil, r.nombre_rol,
                    e.id_estudiante, e.id_carrera, e.semestre, e.registro_universitario,
                    t.id_tutor, t.especialidad, t.biografia
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
             LEFT JOIN tutores t ON t.id_usuario = u.id_usuario
             WHERE u.id_usuario = :id_usuario LIMIT 1'
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

    public function roleName(int $roleId): ?string
    {
        $statement = Database::connection()->prepare('SELECT nombre_rol FROM roles WHERE id_rol = :id_rol LIMIT 1');
        $statement->execute(['id_rol' => $roleId]);
        $name = $statement->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    public function create(array $data): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $role = $this->roleName((int) $data['id_rol']);
            if ($role === null) {
                throw new RuntimeException('El rol seleccionado no existe.');
            }
            $statement = $pdo->prepare(
                'INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono, estado)
                 VALUES (:id_rol, :nombre, :apellido, :correo, :usuario, :contrasena_hash, :telefono, :estado)'
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
            $userId = (int) $pdo->lastInsertId();
            $this->saveRoleProfile($pdo, $userId, $role, $data, null);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function update(int $id, array $data): void
    {
        if ($this->isProtectedAdmin($id)) {
            throw new RuntimeException('La cuenta admin esta protegida y no puede modificarse.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $currentStatement = $pdo->prepare(
                'SELECT u.id_rol, r.nombre_rol,
                        e.id_estudiante, t.id_tutor
                 FROM usuarios u
                 INNER JOIN roles r ON r.id_rol = u.id_rol
                 LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
                 LEFT JOIN tutores t ON t.id_usuario = u.id_usuario
                 WHERE u.id_usuario = :id_usuario LIMIT 1 FOR UPDATE'
            );
            $currentStatement->execute(['id_usuario' => $id]);
            $current = $currentStatement->fetch();
            if (!$current) {
                throw new RuntimeException('La cuenta no existe.');
            }

            $newRole = $this->roleName((int) $data['id_rol']);
            if ($newRole === null) {
                throw new RuntimeException('El rol seleccionado no existe.');
            }
            $hasProfile = $current['id_estudiante'] !== null || $current['id_tutor'] !== null;
            if ($newRole !== $current['nombre_rol'] && $hasProfile) {
                throw new RuntimeException('No se puede cambiar el rol de una cuenta que ya tiene perfil académico. Gestione el perfil o cree otra cuenta.');
            }

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
            $statement = $pdo->prepare($sql);
            $statement->execute($fields);

            $this->saveRoleProfile(
                $pdo,
                $id,
                $newRole,
                $data,
                $current['id_estudiante'] !== null ? (int) $current['id_estudiante'] : ($current['id_tutor'] !== null ? (int) $current['id_tutor'] : null)
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function saveRoleProfile(PDO $pdo, int $userId, string $role, array $data, ?int $profileId): void
    {
        if ($role === 'estudiante') {
            if ($profileId !== null) {
                $statement = $pdo->prepare('UPDATE estudiantes SET id_carrera = :id_carrera, semestre = :semestre WHERE id_estudiante = :id_estudiante');
                $statement->execute([
                    'id_estudiante' => $profileId,
                    'id_carrera' => (int) $data['id_carrera'],
                    'semestre' => (int) $data['semestre'],
                ]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO estudiantes (id_usuario, id_carrera, semestre, registro_universitario)
                     VALUES (:id_usuario, :id_carrera, :semestre, :registro_universitario)'
                );
                $statement->execute([
                    'id_usuario' => $userId,
                    'id_carrera' => (int) $data['id_carrera'],
                    'semestre' => (int) $data['semestre'],
                    'registro_universitario' => RegistroUniversitario::next($pdo),
                ]);
            }
        } elseif ($role === 'tutor') {
            if ($profileId !== null) {
                $statement = $pdo->prepare('UPDATE tutores SET especialidad = :especialidad, biografia = :biografia WHERE id_tutor = :id_tutor');
                $statement->execute([
                    'id_tutor' => $profileId,
                    'especialidad' => $data['especialidad'] !== '' ? $data['especialidad'] : null,
                    'biografia' => $data['biografia'] !== '' ? $data['biografia'] : null,
                ]);
            } else {
                $statement = $pdo->prepare('INSERT INTO tutores (id_usuario, especialidad, biografia) VALUES (:id_usuario, :especialidad, :biografia)');
                $statement->execute([
                    'id_usuario' => $userId,
                    'especialidad' => $data['especialidad'] !== '' ? $data['especialidad'] : null,
                    'biografia' => $data['biografia'] !== '' ? $data['biografia'] : null,
                ]);
            }
        } elseif ($profileId !== null) {
            throw new RuntimeException('No se puede asignar un rol general a una cuenta con perfil académico vinculado.');
        }
    }

    public function deactivate(int $id): bool
    {
        if ($this->isProtectedAdmin($id)) {
            return false;
        }

        $statement = Database::connection()->prepare(
            "UPDATE usuarios SET estado = 'inactivo' WHERE id_usuario = :id_usuario AND estado = 'activo'"
        );
        $statement->execute(['id_usuario' => $id]);

        return $statement->rowCount() > 0;
    }

    public function activate(int $id, ?int $reviewerId = null): bool
    {
        if ($this->isProtectedAdmin($id)) {
            return false;
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $current = $pdo->prepare(
                'SELECT u.estado,r.nombre_rol,u.nombre,u.apellido FROM usuarios u
                 INNER JOIN roles r ON r.id_rol=u.id_rol WHERE u.id_usuario=:id FOR UPDATE'
            );
            $current->execute(['id' => $id]);
            $user = $current->fetch(PDO::FETCH_ASSOC);
            if (!$user || !in_array($user['estado'], ['pendiente', 'inactivo'], true)) {
                $pdo->rollBack();
                return false;
            }

            $statement = $pdo->prepare(
                "UPDATE usuarios SET estado = 'activo' WHERE id_usuario = :id_usuario AND estado IN ('pendiente', 'inactivo')"
            );
            $statement->execute(['id_usuario' => $id]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('La cuenta cambió antes de ser activada.');
            }

            $request = $pdo->prepare(
                "UPDATE solicitudes_tutor SET estado = 'aprobada', fecha_revision = CURRENT_TIMESTAMP,
                     revisado_por = COALESCE(:revisor,revisado_por)
                 WHERE id_usuario = :id_usuario AND estado = 'pendiente'"
            );
            $request->execute(['id_usuario' => $id, 'revisor' => $reviewerId]);
            $wasPending = $user['estado'] === 'pendiente';
            (new Notificacion())->add(
                $pdo,
                $id,
                $wasPending ? 'cuenta_aprobada' : 'cuenta_activada',
                $wasPending ? 'Cuenta aprobada' : 'Cuenta activada',
                $wasPending
                    ? 'Administración aprobó tu registro. Ya puedes acceder al sistema.'
                    : 'Administración activó tu cuenta; ya puedes acceder al sistema.',
                'dashboard.php',
                'account-activation:' . $id . ':' . ($wasPending ? 'approved' : 'reactivated')
            );
            $pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function isProtectedAdmin(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT 1 FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id_usuario AND r.nombre_rol = :rol AND (u.id_usuario = :protected_id OR u.usuario = :protected_username) LIMIT 1'
        );
        $statement->execute([
            'id_usuario' => $id,
            'rol' => 'administrador',
            'protected_id' => self::PROTECTED_ADMIN_ID,
            'protected_username' => self::PROTECTED_ADMIN_USERNAME,
        ]);

        return (bool) $statement->fetchColumn();
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
                u.foto_perfil,
                u.estado,
                r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON r.id_rol = u.id_rol
            WHERE u.usuario = :usuario
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
