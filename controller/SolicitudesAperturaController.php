<?php

declare(strict_types=1);

final class SolicitudesAperturaController
{
    private SolicitudAperturaMateria $model;

    public function __construct()
    {
        $this->model = new SolicitudAperturaMateria();
    }

    public function futurePeriods(): array
    {
        return $this->model->futurePeriods();
    }

    public function activeTurns(): array
    {
        return $this->model->activeTurns();
    }

    public function availableSubjects(int $studentId, int $periodId, int $turnId): array
    {
        if ($studentId < 1 || $periodId < 1 || $turnId < 1) {
            return [];
        }
        return $this->model->availableSubjects($studentId, $periodId, $turnId);
    }

    public function ownRequests(int $studentId): array
    {
        return $this->model->ownRequests($studentId);
    }

    public function allForReview(): array
    {
        return $this->model->allForReview();
    }

    public function submit(int $studentId, array $input): ?string
    {
        $periodId = filter_var($input['id_periodo'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = filter_var($input['id_materia'] ?? null, FILTER_VALIDATE_INT);
        $turnId = filter_var($input['id_turno'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim((string) ($input['motivo'] ?? ''));
        if ($periodId === false || $periodId < 1 || $subjectId === false || $subjectId < 1 || $turnId === false || $turnId < 1) {
            return 'Seleccione un periodo, materia y turno válidos.';
        }
        if (mb_strlen($reason) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $reason)) {
            return 'El motivo no puede superar 500 caracteres ni contener caracteres no válidos.';
        }

        try {
            $this->model->create($studentId, (int) $periodId, (int) $subjectId, (int) $turnId, $reason);
            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo registrar la solicitud. Intente nuevamente.';
        }
    }

    public function review(array $input, int $reviewerId): ?string
    {
        $requestId = filter_var($input['id_solicitud'] ?? null, FILTER_VALIDATE_INT);
        $state = trim((string) ($input['estado'] ?? ''));
        $notes = trim((string) ($input['observaciones_revision'] ?? ''));
        if ($requestId === false || $requestId < 1) {
            return 'Seleccione una solicitud válida.';
        }
        if (!in_array($state, ['aprobada', 'rechazada'], true)) {
            return 'Seleccione aprobar o rechazar la solicitud.';
        }
        if (mb_strlen($notes) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $notes)) {
            return 'Las observaciones no pueden superar 500 caracteres ni contener caracteres no válidos.';
        }

        try {
            $this->model->review((int) $requestId, $state, $reviewerId, $notes);
            return null;
        } catch (InvalidArgumentException | RuntimeException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo actualizar la solicitud.';
        }
    }
}
