<?php

declare(strict_types=1);

final class OfertasController
{
    private OfertaTutoria $model;

    public function __construct()
    {
        $this->model = new OfertaTutoria();
    }

    public function index(): array
    {
        return $this->model->all();
    }

    public function publicOffers(?int $studentId = null): array
    {
        return $this->model->publicOffers($studentId);
    }

    public function find(int $id): ?array
    {
        return $this->model->find($id);
    }

    public function options(): array
    {
        return $this->model->optionsForForm();
    }

    public function roomAvailability(array $input): array
    {
        $periodId = filter_var($input['periodo'] ?? null, FILTER_VALIDATE_INT);
        $turnId = filter_var($input['id_turno'] ?? null, FILTER_VALIDATE_INT);
        $excludeOfferId = filter_var($input['excluir'] ?? null, FILTER_VALIDATE_INT);
        $day = trim((string) ($input['dia'] ?? ''));

        return $this->model->roomAvailability(
            $periodId !== false ? (int) $periodId : 0,
            $day,
            $turnId !== false ? (int) $turnId : 0,
            $excludeOfferId !== false && $excludeOfferId !== null ? (int) $excludeOfferId : null
        );
    }

    public function store(array $input): array
    {
        $data = $this->normalize($input);
        $dates = $this->dates($input);
        $schedules = $this->schedules($input, $dates);
        $errors = $this->applyPeriodType($data['id_periodo'], $data);
        if (!$errors) {
            $errors = $this->validate($data);
            $persistData = $data;
            unset($persistData['id_carrera'], $persistData['weekly_room']);
        }
        if (!$errors && !$this->model->activeTurnExists($data['id_turno'])) {
            $errors[] = 'Seleccione un turno activo.';
        }
        if (!$errors) {
            $errors = array_merge($errors, $this->dateErrors($data['id_periodo'], $dates));
        }
        if (!$errors && !$this->model->subjectBelongsToCareer($data['id_materia'], $data['id_carrera'])) {
            $errors[] = 'La materia seleccionada no pertenece a la carrera indicada.';
        }
        if (!$errors) {
            $errors = $this->model->validateBeforeSave($data, $schedules, $dates, null);
        }
        if (!$errors) {
            try {
                $this->model->create($persistData, $schedules, $dates);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = $this->persistError($exception, 'guardar');
            }
        }

        return [$data + ['schedules' => $schedules, 'fechas' => $dates], $errors];
    }

    public function update(int $id, array $input): array
    {
        $data = $this->normalize($input);
        $dates = $this->dates($input);
        $schedules = $this->schedules($input, $dates);
        $errors = $this->applyPeriodType($data['id_periodo'], $data);
        if (!$errors) {
            $errors = $this->validate($data);
            $persistData = $data;
            unset($persistData['id_carrera'], $persistData['weekly_room']);
        }
        if (!$errors && !$this->model->activeTurnExists($data['id_turno'])) {
            $errors[] = 'Seleccione un turno activo.';
        }
        if (!$errors) {
            $errors = array_merge($errors, $this->dateErrors($data['id_periodo'], $dates));
        }
        if (!$errors && !$this->model->subjectBelongsToCareer($data['id_materia'], $data['id_carrera'])) {
            $errors[] = 'La materia seleccionada no pertenece a la carrera indicada.';
        }
        if (!$errors) {
            $errors = $this->model->validateBeforeSave($data, $schedules, $dates, $id);
        }
        if (!$errors) {
            try {
                $this->model->update($id, $persistData, $schedules, $dates);
            } catch (PDOException $exception) {
                error_log($exception->getMessage());
                $errors[] = $this->persistError($exception, 'actualizar');
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        return [$data + ['schedules' => $schedules, 'fechas' => $dates], $errors];
    }

    public function openNextGroup(int $id): array
    {
        try {
            $newOfferId = $this->model->openNextGroup($id);
            return [$newOfferId, null];
        } catch (RuntimeException $exception) {
            return [null, $exception->getMessage()];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [null, 'No se pudo crear el siguiente grupo. Intente de nuevo.'];
        }
    }

    public function delete(int $id): ?string
    {
        try {
            $this->model->delete($id);
            return null;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se puede eliminar una oferta con inscripciones asociadas.';
        }
    }

    public function tutorOffers(int $userId): array
    {
        return $this->model->tutorOffers($userId);
    }

    public function availableForTutor(int $userId): array
    {
        return $this->model->availableForTutor($userId);
    }

    public function selectAsTutor(int $userId, array $input): ?string
    {
        if (($input['acepto_horarios'] ?? '') !== '1') {
            return 'Confirma que aceptas cumplir todos los días y horarios de esta oferta.';
        }
        $offerId = filter_var($input['id_oferta'] ?? null, FILTER_VALIDATE_INT);
        if ($offerId === false || $offerId < 1) {
            return 'Seleccione una oferta valida.';
        }
        try {
            $this->model->selectAsTutor($userId, $offerId);
            return null;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'Ya seleccionaste esta materia ofertada.';
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        }
    }

    public function requestTutorWithdrawal(int $userId, array $input): array
    {
        $offerTutorId = filter_var($input['id_oferta_tutor'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim((string) ($input['motivo'] ?? ''));
        if ($offerTutorId === false || $offerTutorId < 1) {
            return [null, 'La asignación seleccionada no es válida.'];
        }
        if ($reason === '' || mb_strlen($reason) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $reason)) {
            return [null, 'Indica el motivo de la baja (máximo 500 caracteres).'];
        }
        try {
            return [$this->model->requestTutorWithdrawal($userId, (int) $offerTutorId, $reason), null];
        } catch (RuntimeException $exception) {
            return [null, $exception->getMessage()];
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return [null, 'No se pudo registrar la baja de la materia.'];
        }
    }

    public function pendingTutorWithdrawals(): array
    {
        return $this->model->pendingTutorWithdrawals();
    }

    public function recentTutorWithdrawals(): array
    {
        return $this->model->recentTutorWithdrawals();
    }

    public function reviewTutorWithdrawal(array $input, int $reviewerId): ?string
    {
        $withdrawalId = filter_var($input['id_baja'] ?? null, FILTER_VALIDATE_INT);
        $decision = trim((string) ($input['decision'] ?? ''));
        $notes = trim((string) ($input['respuesta_admin'] ?? ''));
        if ($withdrawalId === false || $withdrawalId < 1) {
            return 'Seleccione una solicitud de baja válida.';
        }
        if (!in_array($decision, ['aprobada', 'rechazada'], true)) {
            return 'Seleccione aprobar o rechazar la solicitud.';
        }
        if (mb_strlen($notes) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $notes)) {
            return 'La respuesta no puede superar 500 caracteres ni contener caracteres no válidos.';
        }
        try {
            $this->model->reviewTutorWithdrawal((int) $withdrawalId, $decision, $reviewerId, $notes);
            return null;
        } catch (InvalidArgumentException | RuntimeException $exception) {
            return $exception->getMessage();
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            return 'No se pudo resolver la solicitud de baja.';
        }
    }

    public function setTutorSchedules(int $userId, array $input): ?string
    {
        $id = filter_var($input['id_oferta_tutor'] ?? null, FILTER_VALIDATE_INT);
        $scheduleIds = array_values(array_unique(array_filter(array_map('intval', (array) ($input['id_oferta_horario'] ?? [])))));
        if ($id === false || $id < 1) {
            return 'Asignacion no valida.';
        }
        if (!$scheduleIds) {
            return 'Seleccione al menos un turno horario.';
        }
        try {
            $this->model->setTutorSchedules($userId, $id, $scheduleIds);
            return null;
        } catch (Throwable $exception) {
            error_log($exception->getMessage());
            return $exception->getMessage();
        }
    }

    public function enrollableRows(int $offerId): array
    {
        return $this->model->enrollableRows($offerId);
    }

    private function normalize(array $input): array
    {
        return [
            'id_periodo' => (int) ($input['id_periodo'] ?? 0),
            'id_carrera' => (int) ($input['id_carrera'] ?? 0),
            'id_materia' => (int) ($input['id_materia'] ?? 0),
            'id_tipo_tutoria' => (int) ($input['id_tipo_tutoria'] ?? 0),
            'frecuencia_programacion' => trim((string) ($input['frecuencia_programacion'] ?? 'mensual')),
            'id_turno' => (int) ($input['id_turno'] ?? 0),
            'nombre_grupo' => trim((string) ($input['nombre_grupo'] ?? 'Grupo A')),
            'cupo' => (int) ($input['cupo'] ?? 0),
            'descripcion' => trim((string) ($input['descripcion'] ?? '')),
            'estado' => trim((string) ($input['estado'] ?? 'pendiente')),
            'weekly_room' => (int) ($input['weekly_room'] ?? 0),
        ];
    }

    private function schedules(array $input, array $dates): array
    {
        $room = filter_var($input['weekly_room'] ?? null, FILTER_VALIDATE_INT);
        $roomId = $room !== false && $room > 0 ? (int) $room : null;
        $dayNames = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        $days = [];
        foreach ($dates as $date) {
            $weekday = (int) date('w', strtotime($date));
            if ($weekday >= 1 && $weekday <= 6) {
                $days[$dayNames[$weekday - 1]] = true;
            }
        }
        $schedules = [];
        foreach ($dayNames as $day) {
            if (isset($days[$day])) {
                $schedules[] = ['dia_semana' => $day, 'id_aula' => $roomId];
            }
        }

        return $schedules;
    }

    private function dates(array $input): array
    {
        $dates = [];
        foreach (array_unique(array_map('strval', (array) ($input['fechas'] ?? []))) as $date) {
            $date = trim($date);
            $parsed = DateTime::createFromFormat('!Y-m-d', $date);
            if ($parsed && $parsed->format('Y-m-d') === $date) {
                $dates[] = $date;
            }
        }
        sort($dates);
        return $dates;
    }

    private function dateErrors(int $periodId, array $dates): array
    {
        $period = $this->model->periodBounds($periodId);
        if (!$period) {
            return ['Seleccione un periodo valido.'];
        }
        foreach ($dates as $date) {
            if ($date < $period['fecha_inicio'] || $date > $period['fecha_fin']) {
                return ['Todas las fechas de la oferta deben estar dentro del periodo seleccionado.'];
            }
            if ((int) date('w', strtotime($date)) === 0) {
                return ['El calendario no habilita domingos; seleccione otra fecha.'];
            }
        }
        return [];
    }

    private function applyPeriodType(int $periodId, array &$data): array
    {
        $period = $this->model->periodBounds($periodId);
        if (!$period) {
            return ['Seleccione un periodo valido.'];
        }
        if (!(int) $period['id_tipo_tutoria']) {
            return ['El periodo seleccionado no tiene un tipo de tutoria configurado. Edite el periodo para asignarlo.'];
        }
        $data['id_tipo_tutoria'] = (int) $period['id_tipo_tutoria'];
        return [];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if ($data['id_periodo'] < 1 || $data['id_carrera'] < 1 || $data['id_materia'] < 1 || $data['id_tipo_tutoria'] < 1) {
            $errors[] = 'Seleccione un periodo, una carrera, una materia y un tipo de tutoria.';
        }
        if ($data['id_turno'] < 1) {
            $errors[] = 'Seleccione un turno valido.';
        }
        if (!in_array($data['frecuencia_programacion'], ['mensual', 'semanal', 'diaria'], true)) {
            $errors[] = 'Seleccione una frecuencia valida.';
        }
        if ($data['nombre_grupo'] === '' || strlen($data['nombre_grupo']) > 50) {
            $errors[] = 'Ingrese un paralelo valido.';
        }
        if ($data['cupo'] < 1) {
            $errors[] = 'El cupo debe ser mayor a cero.';
        }
        if (strlen($data['descripcion']) > 500) {
            $errors[] = 'La descripcion no puede superar 500 caracteres.';
        }
        if (!in_array($data['estado'], ['pendiente', 'publicada', 'cerrada', 'finalizada', 'cancelada'], true)) {
            $errors[] = 'Seleccione un estado de oferta valido.';
        }

        return $errors;
    }

    private function persistError(PDOException $exception, string $action): string
    {
        if (str_starts_with((string) $exception->getCode(), '23')) {
            return 'Ya existe una oferta con ese periodo, materia, turno y paralelo.';
        }

        return 'No se pudo ' . $action . ' la oferta. Intente de nuevo.';
    }
}
