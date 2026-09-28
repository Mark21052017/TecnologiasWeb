<?php

declare(strict_types=1);

final class MgAsignacionesTutorController
{
    private MgAsignacionesTutor $model;

    public function __construct()
    {
        $this->model = new MgAsignacionesTutor();
    }

    public function capacityRule(): array
    {
        return $this->model->capacityRule();
    }

    public function works(): array
    {
        return $this->model->activeWorks();
    }

    public function tutors(): array
    {
        return $this->model->tutorsWithLoad();
    }

    public function assign(array $input, int $adminId): void
    {
        $workId = filter_var($input['id_trabajo'] ?? null, FILTER_VALIDATE_INT);
        $tutorId = filter_var($input['id_tutor'] ?? null, FILTER_VALIDATE_INT);
        if ($workId === false || $workId < 1 || $tutorId === false || $tutorId < 1) {
            throw new RuntimeException('Seleccione trabajo y tutor válidos.');
        }
        $this->model->assign((int) $workId, (int) $tutorId, $adminId, (string) ($input['observacion'] ?? ''));
    }

    public function tutorWorks(int $userId): array
    {
        return $this->model->tutorWorks($userId);
    }
}
