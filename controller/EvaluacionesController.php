<?php

declare(strict_types=1);

final class EvaluacionesController
{
    private EvaluacionTutoria $model;

    public function __construct()
    {
        $this->model = new EvaluacionTutoria();
    }

    public function index(string $role, int $userId, array $filters = []): array
    {
        return $this->model->allForViewer($role, $userId, $filters);
    }

    public function filterOptions(string $role, int $userId): array
    {
        return $this->model->filterOptions($role, $userId);
    }

    public function summary(string $role, int $userId): array
    {
        return $this->model->summaryForViewer($role, $userId);
    }

    public function options(int $userId): array
    {
        return $this->model->eligibleForStudent($userId);
    }

    public function store(array $input, int $userId): array
    {
        $tutoriaId = filter_var($input['id_tutoria'] ?? null, FILTER_VALIDATE_INT);
        $rating = filter_var($input['calificacion'] ?? null, FILTER_VALIDATE_INT);
        $comment = trim((string) ($input['comentario'] ?? ''));
        $errors = [];
        if ($tutoriaId === false || $tutoriaId < 1) {
            $errors[] = 'Seleccione una tutoria valida.';
        }
        if ($rating === false || $rating < 1 || $rating > 5) {
            $errors[] = 'La calificacion debe estar entre 1 y 5.';
        }
        if (strlen($comment) > 1000) {
            $errors[] = 'El comentario no puede superar 1000 caracteres.';
        }
        if ($errors) {
            return [['id_tutoria' => $tutoriaId, 'calificacion' => $rating, 'comentario' => $comment], $errors];
        }

        try {
            $this->model->create($tutoriaId, $userId, $rating, $comment);
            return [[], []];
        } catch (RuntimeException $exception) {
            return [[], ['La tutoria no existe, no esta realizada o ya fue evaluada.']];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [[], ['La tutoria ya tiene una evaluacion.']];
        }
    }
}
