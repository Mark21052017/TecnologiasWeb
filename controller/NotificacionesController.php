<?php

declare(strict_types=1);

final class NotificacionesController
{
    private Notificacion $model;

    public function __construct()
    {
        $this->model = new Notificacion();
    }

    public function unreadCount(int $userId): int
    {
        return $this->model->unreadCount($userId);
    }

    public function latest(int $userId): array
    {
        return $this->model->latestForUser($userId);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        return $this->model->markRead($notificationId, $userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->model->markAllRead($userId);
    }
}
