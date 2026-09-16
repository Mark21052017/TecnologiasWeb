<?php

declare(strict_types=1);

final class CatalogoEstudianteController
{
    private CatalogoEstudiante $model;

    public function __construct()
    {
        $this->model = new CatalogoEstudiante();
    }

    public function materias(): array
    {
        return $this->model->materias();
    }

    public function tutores(): array
    {
        return $this->model->tutores();
    }

    public function availability(): array
    {
        return $this->model->availability();
    }
}
