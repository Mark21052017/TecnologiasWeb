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

    public function confirmedTutorsForOffers(array $offerIds): array
    {
        return $this->model->confirmedTutorOptions($offerIds);
    }

    public function assignTutor(array $input): ?string
    {
        $enrollmentId = filter_var($input['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
        $offerTutorId = filter_var($input['id_oferta_tutor'] ?? null, FILTER_VALIDATE_INT);
        if ($enrollmentId === false || $enrollmentId < 1 || $offerTutorId === false || $offerTutorId < 1) {
            return 'Seleccione una inscripción y un tutor válidos.';
        }
        try {
            $this->model->assignTutor((int) $enrollmentId, (int) $offerTutorId);
            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo asignar el tutor a la inscripción.';
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
