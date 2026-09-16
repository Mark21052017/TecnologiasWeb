<?php

declare(strict_types=1);

final class AccesosController
{
    public function index(): array
    {
        return (new RegistroAcceso())->all();
    }
}
