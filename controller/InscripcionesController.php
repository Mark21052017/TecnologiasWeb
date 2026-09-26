<?php

declare(strict_types=1);

final class InscripcionesController
{
    private InscripcionTutoria $model;

    public function __construct()
    {
        $this->model = new InscripcionTutoria();
    }

    public function index(string $role, int $userId): array
    {
        return $this->model->allForViewer($role, $userId);
    }

    public function store(int $studentId, array $input): ?string
    {
        $offerId = filter_var($input['id_oferta'] ?? null, FILTER_VALIDATE_INT);
        $offerTutorId = filter_var($input['id_oferta_tutor'] ?? null, FILTER_VALIDATE_INT);
        $scheduleId = filter_var($input['id_oferta_horario'] ?? null, FILTER_VALIDATE_INT);
        if ($offerId === false || $offerTutorId === false || $scheduleId === false) {
            return 'Seleccione una oferta, tutor y horario validos.';
        }

        try {
            $this->model->create($studentId, $offerId, $offerTutorId, $scheduleId);
            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo registrar la inscripcion.';
        }
    }

    public function cancel(int $studentId, array $input): ?string
    {
        $id = filter_var($input['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            return 'Inscripcion no valida.';
        }

        return $this->model->cancel($id, $studentId);
    }
}
