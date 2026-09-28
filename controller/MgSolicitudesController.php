<?php

declare(strict_types=1);

final class MgSolicitudesController
{
    private MgSolicitudes $model;

    public function __construct()
    {
        $this->model = new MgSolicitudes();
    }

    public function studentIdForUser(int $userId): ?int
    {
        $student = (new Estudiante())->findByUserId($userId);
        return $student ? (int) $student['id_estudiante'] : null;
    }

    public function modalitiesForStudent(int $studentId): array
    {
        return $this->model->modalitiesForStudent($studentId);
    }

    public function studentRequests(int $studentId): array
    {
        return $this->model->studentRequests($studentId);
    }

    public function academicVerification(int $studentId): array
    {
        return (new MgAcademico())->studentVerification($studentId);
    }

    public function pendingSubjects(int $studentId): array
    {
        return (new MgAcademico())->studentPendingSubjects($studentId);
    }

    public function deliverMilestone(int $taskId, int $studentId, int $userId, array $input): void
    {
        (new MgSeguimientoHitosController())->deliver($taskId, $studentId, $userId, $input);
    }

    public function uploadMilestoneReport(int $taskId, int $studentId, int $userId, array $file, array $input): void
    {
        (new MgSeguimientoController())->uploadReport($taskId, $studentId, $userId, $file, $input);
    }

    public function studentRequest(int $requestId, int $studentId): ?array
    {
        return $this->model->requestForStudent($requestId, $studentId);
    }

    public function saveDraft(int $studentId, int $actorId, array $input): int
    {
        return $this->model->saveDraft($studentId, $actorId, $input);
    }

    public function submit(int $requestId, int $studentId, int $actorId): void
    {
        $this->model->submit($requestId, $studentId, $actorId);
    }

    public function cancel(int $requestId, int $studentId, int $actorId, string $note): void
    {
        $this->model->cancelOwn($requestId, $studentId, $actorId, trim($note));
    }

    public function adminRequests(string $state): array
    {
        return $this->model->adminRequests($state);
    }

    public function adminRequest(int $requestId): ?array
    {
        return $this->model->adminRequest($requestId);
    }

    public function review(int $requestId, int $reviewerId, string $action, string $note): void
    {
        $this->model->review($requestId, $reviewerId, $action, $note);
    }

    public function habilitate(int $requestId, int $adminId, string $note): void
    {
        $this->model->habilitate($requestId, $adminId, trim($note));
    }
}
