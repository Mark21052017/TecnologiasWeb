<?php

declare(strict_types=1);

final class TutoriasController
{
    private Tutoria $model;

    public function __construct()
    {
        $this->model = new Tutoria();
    }

    public function index(string $role, int $userId, array $filters = []): array
    {
        return $this->model->allForViewer($role, $userId, $filters);
    }

    public function filterOptions(string $role, int $userId): array
    {
        return $this->model->filterOptions($role, $userId);
    }

    public function options(): array
    {
        return $this->model->offerings();
    }

    public function store(array $input, ?int $studentId): array
    {
        $data = $this->normalize($input, $studentId);
        $errors = $this->validate($data);
        if (!$errors && !$this->model->hasAvailability((int) $data['id_tutor'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
            $errors[] = 'El tutor no tiene disponibilidad para ese dia y horario.';
        }
        if (!$errors && $this->model->hasTutorConflict((int) $data['id_tutor'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
            $errors[] = 'El tutor ya tiene otra tutoria pendiente o confirmada en ese horario.';
        }
        if (!$errors && $this->model->hasStudentConflict((int) $data['id_estudiante'], $data['fecha'], $data['hora_inicio'], $data['hora_fin'])) {
            $errors[] = 'Ya tienes otra tutoria pendiente o confirmada en ese horario.';
        }
        if ($errors) {
            return [$data, $errors];
        }

        try {
            $this->model->create($data);
            return [$data, []];
        } catch (RuntimeException $exception) {
            return [$data, ['La materia seleccionada no esta asignada al tutor.']];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [$data, ['No fue posible crear la solicitud de tutoria.']];
        }
    }

    public function changeStatus(int $id, string $status, string $role, int $userId): ?string
    {
        $tutoria = $this->model->findForViewer($id, $role, $userId);
        if (!$tutoria) {
            return 'La tutoria no existe o no puede ser modificada.';
        }
        $transitions = [
            'pendiente' => $role === 'estudiante' ? ['cancelada'] : ['confirmada', 'cancelada'],
            'confirmada' => $role === 'estudiante' ? [] : ['realizada', 'cancelada'],
            'realizada' => [],
            'cancelada' => [],
        ];
        if (!in_array($status, $transitions[$tutoria['estado']] ?? [], true)) {
            return 'El cambio de estado no es valido para este usuario.';
        }
        if (!$this->model->changeStatus($id, $status, $role, $userId, (string) $tutoria['estado'])) {
            return 'La tutoria no existe o no puede ser modificada.';
        }

        return null;
    }

    private function normalize(array $input, ?int $studentId): array
    {
        $offering = explode(':', trim((string) ($input['id_oferta'] ?? '')), 2);
        return [
            'id_estudiante' => $studentId,
            'id_tutor' => $offering[0] ?? '',
            'id_materia' => $offering[1] ?? '',
            'id_oferta' => trim((string) ($input['id_oferta'] ?? '')),
            'fecha' => trim((string) ($input['fecha'] ?? '')),
            'hora_inicio' => trim((string) ($input['hora_inicio'] ?? '')),
            'hora_fin' => trim((string) ($input['hora_fin'] ?? '')),
            'modalidad' => trim((string) ($input['modalidad'] ?? 'presencial')),
            'lugar_o_enlace' => trim((string) ($input['lugar_o_enlace'] ?? '')),
            'observaciones' => trim((string) ($input['observaciones'] ?? '')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $tutorId = filter_var($data['id_tutor'], FILTER_VALIDATE_INT);
        $subjectId = filter_var($data['id_materia'], FILTER_VALIDATE_INT);

        if (!$data['id_estudiante']) {
            $errors[] = 'El usuario no tiene un perfil de estudiante.';
        }
        if ($tutorId === false || $tutorId < 1 || $subjectId === false || $subjectId < 1) {
            $errors[] = 'Seleccione una oferta de tutor y materia valida.';
        }
        $date = DateTime::createFromFormat('!Y-m-d', $data['fecha']);
        if (!$date || $date->format('Y-m-d') !== $data['fecha']) {
            $errors[] = 'Ingrese una fecha valida.';
        } elseif ($data['fecha'] < date('Y-m-d')) {
            $errors[] = 'La fecha no puede estar en el pasado.';
        } elseif ($data['fecha'] === date('Y-m-d') && $data['hora_inicio'] <= date('H:i')) {
            $errors[] = 'La hora de inicio debe ser futura.';
        }
        $startError = validation_time($data['hora_inicio'], 'hora inicial');
        $endError = validation_time($data['hora_fin'], 'hora final');
        if ($startError !== null || $endError !== null) {
            $errors[] = 'Ingrese horarios validos.';
        } elseif ($data['hora_fin'] <= $data['hora_inicio']) {
            $errors[] = 'La hora final debe ser posterior a la inicial.';
        }
        if (!in_array($data['modalidad'], ['presencial', 'virtual'], true)) {
            $errors[] = 'Seleccione una modalidad valida.';
        }
        if (strlen($data['lugar_o_enlace']) > 200) {
            $errors[] = 'El lugar o enlace no puede superar 200 caracteres.';
        }
        if ($data['modalidad'] === 'virtual' && $data['lugar_o_enlace'] === '') {
            $errors[] = 'Ingrese el enlace para una tutoria virtual.';
        }

        return $errors;
    }
}
