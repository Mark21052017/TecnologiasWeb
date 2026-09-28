<?php

declare(strict_types=1);

final class PostulacionesTutoriaController
{
    private PostulacionTutoria $model;

    public function __construct()
    {
        $this->model = new PostulacionTutoria();
    }

    public function studentId(int $userId): ?int
    {
        $profile = (new Estudiante())->findByUserId($userId);
        return $profile ? (int) $profile['id_estudiante'] : null;
    }

    public function ownApplications(int $studentId): array
    {
        return $this->model->ownApplications($studentId);
    }

    public function latestStatusByOffer(int $studentId): array
    {
        $latest = [];
        foreach ($this->ownApplications($studentId) as $application) {
            $offerId = (int) $application['id_oferta'];
            if (!isset($latest[$offerId])) {
                $latest[$offerId] = $application;
            }
        }
        return $latest;
    }

    public function submit(int $studentId, int $userId, array $input): int
    {
        $offerId = filter_var($input['id_oferta'] ?? null, FILTER_VALIDATE_INT);
        if ($offerId === false || $offerId < 1) {
            throw new RuntimeException('Seleccione una oferta publicada válida.');
        }
        return $this->model->create($studentId, $userId, (int) $offerId, (string) ($input['motivo'] ?? ''));
    }

    public function pending(): array
    {
        return $this->model->allPending();
    }

    public function review(array $input, int $adminId): void
    {
        $id = filter_var($input['id_postulacion'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            throw new RuntimeException('Seleccione una postulación válida.');
        }
        $this->model->review((int) $id, $adminId, (string) ($input['estado'] ?? ''), (string) ($input['observaciones_revision'] ?? ''));
    }

    public function cancel(int $applicationId, int $studentId, int $userId): void
    {
        $this->model->cancelOwn($applicationId, $studentId, $userId);
    }
}
