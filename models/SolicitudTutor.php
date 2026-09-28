<?php

declare(strict_types=1);

final class SolicitudTutor
{
    public function all(): array
    {
        return Database::connection()->query(
            "SELECT s.*, u.nombre, u.apellido, u.correo, u.usuario, u.telefono, u.estado AS estado_usuario,
                    r.nombre_rol, CONCAT(rv.nombre, ' ', rv.apellido) AS revisor
             FROM solicitudes_tutor s
             INNER JOIN usuarios u ON u.id_usuario = s.id_usuario
             INNER JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN usuarios rv ON rv.id_usuario = s.revisado_por
             ORDER BY FIELD(s.estado, 'pendiente', 'aprobada', 'rechazada'), s.fecha_solicitud DESC"
        )->fetchAll();
    }

    public function create(int $userId, string $specialty, string $biography): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO solicitudes_tutor (id_usuario, especialidad, biografia)
             VALUES (:id_usuario, :especialidad, :biografia)'
        );
        $statement->execute([
            'id_usuario' => $userId,
            'especialidad' => $specialty,
            'biografia' => $biography !== '' ? $biography : null,
        ]);
    }

    public function review(int $id, string $status, int $reviewerId): void
    {
        if (!in_array($status, ['aprobada', 'rechazada'], true)) {
            throw new InvalidArgumentException('Estado de solicitud no valido.');
        }
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare('SELECT id_usuario, especialidad, biografia, estado FROM solicitudes_tutor WHERE id_solicitud = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            $request = $statement->fetch();
            if (!$request) {
                throw new RuntimeException('La solicitud no existe.');
            }
            if ($request['estado'] !== 'pendiente') {
                throw new RuntimeException('La solicitud ya fue revisada.');
            }

            $newUserState = $status === 'aprobada' ? 'activo' : 'inactivo';
            $updateUser = $pdo->prepare('UPDATE usuarios SET estado = :estado WHERE id_usuario = :id_usuario');
            $updateUser->execute(['estado' => $newUserState, 'id_usuario' => $request['id_usuario']]);
            $updateRequest = $pdo->prepare(
                'UPDATE solicitudes_tutor SET estado = :estado, fecha_revision = CURRENT_TIMESTAMP,
                    revisado_por = :revisado_por WHERE id_solicitud = :id'
            );
            $updateRequest->execute(['estado' => $status, 'revisado_por' => $reviewerId, 'id' => $id]);
            $profile = $pdo->prepare('UPDATE tutores SET especialidad = :especialidad, biografia = :biografia WHERE id_usuario = :id_usuario');
            $profile->execute([
                'especialidad' => $request['especialidad'],
                'biografia' => $request['biografia'],
                'id_usuario' => $request['id_usuario'],
            ]);
            (new Notificacion())->add(
                $pdo,
                (int) $request['id_usuario'],
                'postulacion_tutor_' . $status,
                $status === 'aprobada' ? 'Postulación de tutor aprobada' : 'Postulación de tutor rechazada',
                $status === 'aprobada'
                    ? 'Administración aprobó tu postulación de tutor. Ya puedes ingresar al portal.'
                    : 'Administración rechazó tu postulación de tutor.',
                'login.php',
                'tutor-application-review:' . $id . ':' . $status
            );
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
