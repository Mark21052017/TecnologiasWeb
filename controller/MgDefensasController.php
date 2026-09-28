<?php

declare(strict_types=1);

final class MgDefensasController
{
    private MgDefensas $model;

    public function __construct()
    {
        $this->model = new MgDefensas();
    }

    public function works(): array
    {
        return $this->model->activeWorks();
    }

    public function overview(int $workId): ?array
    {
        return $this->model->overview($workId);
    }

    public function candidates(): array
    {
        return $this->model->tribunalCandidates();
    }

    public function assignTribunal(int $workId, int $adminId, array $input): void
    {
        $president = filter_var($input['presidente'] ?? null, FILTER_VALIDATE_INT);
        if ($president === false || $president < 1) {
            throw new RuntimeException('Seleccione presidente del tribunal.');
        }
        $members = is_array($input['miembros'] ?? null) ? $input['miembros'] : [];
        $this->model->replaceTribunal($workId, $adminId, (int) $president, $members, (string) ($input['observacion'] ?? ''));
    }

    public function schedule(int $workId, int $adminId, array $input): int
    {
        $defenseId = $this->model->scheduleDefense($workId, $adminId, (string) ($input['fecha_hora'] ?? ''), (string) ($input['ubicacion'] ?? ''), (string) ($input['observaciones'] ?? ''));
        return $this->model->defenseNumber($defenseId);
    }

    public function updateDefense(int $defenseId, int $adminId, array $input): void
    {
        $action = (string) ($input['accion'] ?? '');
        $action = match ($action) {
            'resultado_defensa' => 'resultado',
            'cancelar_defensa' => 'cancelar',
            default => $action,
        };
        $this->model->updateDefense($defenseId, $adminId, $action, $input);
    }

    public function close(int $workId, int $adminId, array $input): void
    {
        $this->model->close($workId, $adminId, (string) ($input['resultado'] ?? ''), (string) ($input['observaciones'] ?? ''));
    }

    public function studentInfo(int $workId, int $studentId): array
    {
        return $this->model->studentDefenseInfo($workId, $studentId);
    }
}
