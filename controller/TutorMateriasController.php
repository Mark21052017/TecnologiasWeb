<?php

declare(strict_types=1);

final class TutorMateriasController
{
    private TutorMateria $model;

    public function __construct()
    {
        $this->model = new TutorMateria();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function options(): array
    {
        return ['tutors' => $this->model->tutors(), 'subjects' => $this->model->subjects()];
    }

    public function store(array $input): array
    {
        $tutorId = filter_var($input['id_tutor'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = filter_var($input['id_materia'] ?? null, FILTER_VALIDATE_INT);
        $errors = [];

        if ($tutorId === false || $tutorId < 1) {
            $errors[] = 'Seleccione un tutor valido.';
        }
        if ($subjectId === false || $subjectId < 1) {
            $errors[] = 'Seleccione una materia valida.';
        }
        if ($errors) {
            return [[], $errors];
        }

        try {
            $this->model->create($tutorId, $subjectId);
            return [[], []];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [[], ['El tutor ya tiene asignada esa materia.']];
        }
    }

    public function delete(array $input): ?string
    {
        $tutorId = filter_var($input['id_tutor'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = filter_var($input['id_materia'] ?? null, FILTER_VALIDATE_INT);
        if ($tutorId === false || $subjectId === false) {
            return 'Asignacion no valida.';
        }

        if ($this->model->hasActiveTutorings($tutorId, $subjectId)) {
            return 'No se puede eliminar una asignacion con tutorias pendientes o confirmadas.';
        }

        $this->model->delete($tutorId, $subjectId);
        return null;
    }
}
