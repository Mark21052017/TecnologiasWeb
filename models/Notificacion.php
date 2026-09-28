<?php

declare(strict_types=1);

final class Notificacion
{
    public function unreadCount(int $userId): int
    {
        $statement = Database::connection()->prepare('SELECT COUNT(*) FROM notificaciones WHERE id_usuario=:usuario AND leida_en IS NULL');
        $statement->execute(['usuario' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function latestForUser(int $userId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 200));
        $statement = Database::connection()->prepare(
            'SELECT id_notificacion,tipo,titulo,mensaje,ruta,creada_en,leida_en
             FROM notificaciones WHERE id_usuario=:usuario
             ORDER BY creada_en DESC,id_notificacion DESC LIMIT ' . $limit
        );
        $statement->execute(['usuario' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(PDO $pdo, int $userId, string $type, string $title, string $message, ?string $path, string $eventKey): void
    {
        if ($userId < 1 || $type === '' || $eventKey === '' || mb_strlen($type) > 60 || mb_strlen($title) > 180
            || mb_strlen($message) > 1000 || mb_strlen($eventKey) > 190) {
            return;
        }
        $path = $this->safePath($path);
        $statement = $pdo->prepare(
            'INSERT IGNORE INTO notificaciones (id_usuario,tipo,titulo,mensaje,ruta,clave_evento)
             VALUES (:usuario,:tipo,:titulo,:mensaje,:ruta,:evento)'
        );
        $statement->execute([
            'usuario' => $userId, 'tipo' => $type, 'titulo' => $title,
            'mensaje' => $message, 'ruta' => $path, 'evento' => $eventKey,
        ]);
    }

    public function notifyRole(PDO $pdo, string $role, string $type, string $title, string $message, ?string $path, string $eventKey): void
    {
        $statement = $pdo->prepare(
            'SELECT u.id_usuario FROM usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol
             WHERE r.nombre_rol=:rol AND u.estado="activo"'
        );
        $statement->execute(['rol' => $role]);
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $userId) {
            $this->add($pdo, (int) $userId, $type, $title, $message, $path, $eventKey . ':user:' . (int) $userId);
        }
    }

    public function notifyAdministrators(PDO $pdo, string $type, string $title, string $message, ?string $path, string $eventKey): void
    {
        $this->notifyRole($pdo, 'administrador', $type, $title, $message, $path, $eventKey);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE notificaciones SET leida_en=COALESCE(leida_en,CURRENT_TIMESTAMP)
             WHERE id_notificacion=:id AND id_usuario=:usuario'
        );
        $statement->execute(['id' => $notificationId, 'usuario' => $userId]);
        return $statement->rowCount() > 0;
    }

    public function markAllRead(int $userId): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE notificaciones SET leida_en=CURRENT_TIMESTAMP WHERE id_usuario=:usuario AND leida_en IS NULL'
        );
        $statement->execute(['usuario' => $userId]);
    }

    private function safePath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }
        $path = trim($path);
        if (mb_strlen($path) > 255 || str_starts_with($path, '//') || parse_url($path, PHP_URL_SCHEME) !== null
            || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return null;
        }
        return ltrim($path, '/');
    }
}
