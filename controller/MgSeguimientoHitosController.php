<?php

declare(strict_types=1);

final class MgSeguimientoHitosController
{
    private MgSeguimientoHitos $model;

    public function __construct()
    {
        $this->model = new MgSeguimientoHitos();
    }

    public function tasksForStudent(int $workId, int $studentId): array
    {
        return $this->model->tasksForStudent($workId, $studentId);
    }

    public function deliver(int $taskId, int $studentId, int $actorId, array $input): void
    {
        $progress = array_key_exists('avance_real_pct', $input) ? trim((string) $input['avance_real_pct']) : null;
        $this->model->deliver($taskId, $studentId, $actorId, $progress, (string) ($input['observacion_estudiante'] ?? ''));
    }
}
