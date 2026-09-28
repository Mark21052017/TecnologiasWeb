<?php

declare(strict_types=1);

final class MgSeguimientoController
{
    private MgSeguimiento $model;

    public function __construct()
    {
        $this->model = new MgSeguimiento();
    }

    public function works(?int $tutorUserId = null): array
    {
        return $this->model->availableWorks($tutorUserId);
    }

    public function detail(int $workId, int $userId, string $role): ?array
    {
        return $this->model->workDetail($workId, $userId, $role);
    }

    public function uploadReport(int $taskId, int $studentId, int $userId, array $file, array $input): void
    {
        $progress = trim((string) ($input['avance_real_pct'] ?? ''));
        $this->model->uploadReport($taskId, $studentId, $userId, $file,
            (string) ($input['observacion_estudiante'] ?? ''), $progress !== '' ? $progress : null);
    }

    public function reviewReport(int $versionId, int $userId, string $role, string $action, string $comment): void
    {
        $this->model->reviewReport($versionId, $userId, $role, $action, $comment);
    }

    public function download(int $versionId, int $userId, string $role): array
    {
        return $this->model->reportDownload($versionId, $userId, $role);
    }

    public function createSession(int $workId, int $userId, string $role, array $input): int
    {
        return $this->model->createSession($workId, $userId, $role, $input);
    }

    public function recordAttendance(int $sessionId, int $userId, string $role, array $statuses, array $comments): void
    {
        $this->model->recordAttendance($sessionId, $userId, $role, $statuses, $comments);
    }

    public function saveStageResult(int $workId, int $userId, string $role, array $input): void
    {
        $this->model->saveStageResult(
            $workId, $userId, $role, (string) ($input['etapa'] ?? ''),
            (string) ($input['estado'] ?? ''), (string) ($input['nota'] ?? ''), (string) ($input['observaciones'] ?? '')
        );
    }

    public function studentSessions(int $workId, int $studentId): array
    {
        return $this->model->studentSessions($workId, $studentId);
    }

    public function studentStageResults(int $workId): array
    {
        return $this->model->studentStageResults($workId);
    }

    public function attendanceSummary(int $workId): array
    {
        return $this->model->attendanceSummary($workId);
    }
}
