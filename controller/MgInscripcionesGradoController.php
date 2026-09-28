<?php

declare(strict_types=1);

final class MgInscripcionesGradoController
{
    private MgInscripcionesGrado $model;

    public function __construct()
    {
        $this->model = new MgInscripcionesGrado();
    }

    public function eligibleRequests(): array
    {
        return $this->model->eligibleRequests();
    }

    public function cohorts(): array
    {
        return $this->model->activeCohorts();
    }

    public function openGroups(): array
    {
        return $this->model->openGroups();
    }

    public function enroll(array $input, int $adminId): int
    {
        return $this->model->enroll($input, $adminId);
    }

    public function registrations(): array
    {
        return $this->model->registrations();
    }
}
