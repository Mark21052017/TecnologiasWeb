<?php

declare(strict_types=1);

final class SolicitudesTutorController
{
    private SolicitudTutor $model;

    public function __construct()
    {
        $this->model = new SolicitudTutor();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function review(array $input, int $reviewerId): ?string
    {
        $id = filter_var($input['id_solicitud'] ?? null, FILTER_VALIDATE_INT);
        $status = trim((string) ($input['estado'] ?? ''));
        if ($id === false || $id < 1) {
            return 'Solicitud no valida.';
        }
        try {
            $this->model->review($id, $status, $reviewerId);
            return null;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return $exception->getMessage();
        }
    }
}
