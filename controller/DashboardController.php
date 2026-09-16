<?php

declare(strict_types=1);

final class DashboardController
{
    private Dashboard $model;

    public function __construct()
    {
        $this->model = new Dashboard();
    }

    public function summary(string $role, int $userId): array
    {
        return $this->model->summary($role, $userId);
    }
}
