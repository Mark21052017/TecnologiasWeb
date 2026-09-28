<?php

declare(strict_types=1);

final class MgAcademicoController
{
    private MgAcademico $model;

    public function __construct()
    {
        $this->model = new MgAcademico();
    }

    public function import(array $file, int $userId, string $kind): int
    {
        return $this->model->importCsv($file, $userId, $kind);
    }

    public function imports(): array
    {
        return $this->model->imports();
    }

    public function rows(int $id): array
    {
        return $this->model->importRows($id);
    }

    public function review(int $id, int $userId, string $decision, string $note): void
    {
        $this->model->review($id, $userId, $decision, $note);
    }

    public function verification(int $studentId): array
    {
        return $this->model->studentVerification($studentId);
    }

    public function administrativeSummary(): array
    {
        return $this->model->administrativeSummary();
    }

    public function approvedPlans(): array
    {
        return $this->model->approvedPlans();
    }

    public function studentsForPlanAssignment(string $search = ''): array
    {
        return $this->model->studentsForPlanAssignment($search);
    }

    public function planAssignmentHistory(int $studentId): array
    {
        return $this->model->planAssignmentHistory($studentId);
    }

    public function assignPlan(array $input, int $userId): ?string
    {
        $studentId = filter_var($input['id_estudiante'] ?? null, FILTER_VALIDATE_INT);
        $planId = filter_var($input['id_plan_estudio'] ?? null, FILTER_VALIDATE_INT);
        if ($studentId === false || $studentId < 1 || $planId === false || $planId < 1) {
            return 'Seleccione un estudiante y un plan válidos.';
        }
        $note = trim((string) ($input['observacion'] ?? ''));
        if (mb_strlen($note) > 500) {
            return 'La observación no puede superar 500 caracteres.';
        }
        try {
            $this->model->assignPlanToStudent((int) $studentId, (int) $planId, $userId, $note);
            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No se pudo asignar el plan al estudiante.';
        }
    }

    public function careerModalities(): array
    {
        return $this->model->careerModalities();
    }

    public function setCareerModality(array $input, int $userId): ?string
    {
        $careerId = filter_var($input['id_carrera'] ?? null, FILTER_VALIDATE_INT);
        $modalityId = filter_var($input['id_modalidad'] ?? null, FILTER_VALIDATE_INT);
        $available = (string) ($input['disponible'] ?? '') === '1';
        if ($careerId === false || $careerId < 1 || $modalityId === false || $modalityId < 1
            || !in_array((string) ($input['disponible'] ?? ''), ['0', '1'], true)) {
            return 'La solicitud de disponibilidad no es válida.';
        }
        try {
            $this->model->setCareerModality((int) $careerId, (int) $modalityId, $available, $userId);
            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return 'No se pudo actualizar la disponibilidad de la modalidad.';
        }
    }

    public function template(string $kind): never
    {
        $this->model->template($kind);
    }
}
