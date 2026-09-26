<?php
$isEditing = ($mode ?? 'create') === 'edit';
$existingSchedules = $data['schedules'] ?? $data['horarios'] ?? [];
$weeklyRoom = (string) ($data['weekly_room'] ?? '');
if ((int) $weeklyRoom < 1) {
    foreach ($existingSchedules as $schedule) {
        if (!empty($schedule['id_aula'])) {
            $weeklyRoom = (string) $schedule['id_aula'];
            break;
        }
    }
}
require __DIR__ . '/../layouts/header.php';
?>
<main class="container"><section class="card"><h1><?= $isEditing ? 'Editar oferta academica' : 'Nueva oferta academica' ?></h1><p class="form-intro">Cada oferta define su materia, turno, frecuencia, fechas reales y un aula aplicada a todas las fechas seleccionadas. El tipo de tutoria se hereda del periodo.</p>
<?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
<?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" data-offer-form data-room-availability-url="<?= e(app_url('ofertas/aulas-disponibles.php')) ?>" data-offer-id="<?= $isEditing ? (int) $data['id_oferta'] : '' ?>" action="<?= e($isEditing ? app_url('ofertas/edit.php?id=' . (int) $data['id_oferta']) : app_url('ofertas/create.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="row g-3 offer-form-fields">
<div class="col-12 col-md-6"><label for="id_periodo">Periodo</label><select class="form-select" id="id_periodo" name="id_periodo" data-offer-period required><option value="">Seleccione</option><?php foreach ($options['periodos'] as $period): ?><option value="<?= (int) $period['id_periodo'] ?>" data-period-start="<?= e($period['fecha_inicio']) ?>" data-period-end="<?= e($period['fecha_fin']) ?>" data-period-type-id="<?= (int) ($period['id_tipo_tutoria'] ?? 0) ?>" data-period-type-name="<?= e($period['nombre_tipo_tutoria'] ?? '') ?>" <?= (string) ($data['id_periodo'] ?? '') === (string) $period['id_periodo'] ? 'selected' : '' ?>><?= e($period['nombre_periodo']) ?> (<?= e($period['fecha_inicio']) ?> a <?= e($period['fecha_fin']) ?>)</option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6"><label for="id_carrera">Carrera</label><select class="form-select" id="id_carrera" name="id_carrera" data-offer-career required><option value="">Seleccione</option><?php foreach ($options['carreras'] as $career): ?><option value="<?= (int) $career['id_carrera'] ?>" <?= (string) ($data['id_carrera'] ?? '') === (string) $career['id_carrera'] ? 'selected' : '' ?>><?= e($career['nombre_carrera']) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6"><label for="id_materia">Materia</label><select class="form-select" id="id_materia" name="id_materia" data-offer-subject required><option value="">Seleccione</option><?php foreach ($options['materias'] as $subject): ?><option value="<?= (int) $subject['id_materia'] ?>" data-offer-career-id="<?= (int) $subject['id_carrera'] ?>" <?= (string) ($data['id_materia'] ?? '') === (string) $subject['id_materia'] ? 'selected' : '' ?>><?= e($subject['nombre_materia']) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6"><label for="id_tipo_tutoria">Tipo de tutoria</label><select class="form-select" id="id_tipo_tutoria" data-offer-type disabled aria-describedby="offer-type-hint"><option value="">Heredado del periodo</option><?php foreach ($options['tipos_tutoria'] as $type): ?><option value="<?= (int) $type['id_tipo_tutoria'] ?>" <?= (string) ($data['id_tipo_tutoria'] ?? '') === (string) $type['id_tipo_tutoria'] ? 'selected' : '' ?>><?= e($type['nombre']) ?><?= $type['estado'] === 'inactivo' ? ' (inactivo)' : '' ?></option><?php endforeach; ?></select><small class="form-hint" id="offer-type-hint">Se hereda del periodo seleccionado; no se puede cambiar aqui.</small></div>
<div class="col-12 col-md-6"><label for="frecuencia_programacion">Frecuencia</label><select class="form-select" id="frecuencia_programacion" name="frecuencia_programacion" data-offer-frequency required><option value="mensual" <?= ($data['frecuencia_programacion'] ?? '') === 'mensual' ? 'selected' : '' ?>>Mensual</option><option value="semanal" <?= ($data['frecuencia_programacion'] ?? 'semanal') === 'semanal' ? 'selected' : '' ?>>Semanal</option><option value="diaria" <?= ($data['frecuencia_programacion'] ?? 'diaria') === 'diaria' ? 'selected' : '' ?>>Diaria</option></select></div>
<div class="col-12 col-md-6"><label for="id_turno">Turno</label><select class="form-select" id="id_turno" name="id_turno" data-offer-turno required><option value="">Seleccione</option><?php foreach ($options['turnos'] as $turnOption): ?><option value="<?= (int) $turnOption['id_turno'] ?>" <?= (int) ($data['id_turno'] ?? 0) === (int) $turnOption['id_turno'] ? 'selected' : '' ?>><?= e($turnOption['nombre_turno'] . ' (' . substr($turnOption['hora_inicio'], 0, 5) . ' - ' . substr($turnOption['hora_fin'], 0, 5) . ')') ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6"><label for="weekly_room">Aula</label><select class="form-select" id="weekly_room" name="weekly_room" data-offer-weekly-room><option value="">Sin aula</option><?php foreach ($options['aulas'] as $room): ?><option value="<?= (int) $room['id_aula'] ?>" <?= $weeklyRoom === (string) $room['id_aula'] ? 'selected' : '' ?>><?= e($room['nombre_aula']) ?></option><?php endforeach; ?></select><small class="form-hint" data-offer-weekly-room-status>Seleccione periodo y turno.</small><small class="form-hint">El aula seleccionada aplica a todas las fechas de esta oferta.</small></div>
<div class="col-12 col-md-6"><label for="nombre_grupo">Paralelo</label><input class="form-control" id="nombre_grupo" name="nombre_grupo" maxlength="50" required value="<?= e($data['nombre_grupo'] ?? 'Grupo A') ?>"><small class="form-hint">Las nuevas ofertas comienzan en Grupo A; los siguientes grupos se crean al completar el cupo.</small></div>
<div class="col-12 col-md-6"><label for="cupo">Cupos</label><input class="form-control" id="cupo" name="cupo" type="number" min="1" required value="<?= e((string) ($data['cupo'] ?? 20)) ?>"></div>
<div class="col-12 col-md-6"><label for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="borrador" <?= ($data['estado'] ?? '') === 'borrador' ? 'selected' : '' ?>>Borrador</option><option value="publicada" <?= ($data['estado'] ?? '') === 'publicada' ? 'selected' : '' ?>>Publicada</option><option value="cerrada" <?= ($data['estado'] ?? '') === 'cerrada' ? 'selected' : '' ?>>Cerrada</option><option value="finalizada" <?= ($data['estado'] ?? '') === 'finalizada' ? 'selected' : '' ?>>Finalizada</option><option value="cancelada" <?= ($data['estado'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option></select></div>
<div class="col-12"><label for="descripcion">Descripcion</label><textarea class="form-control" id="descripcion" name="descripcion" maxlength="500" rows="3"><?= e($data['descripcion'] ?? '') ?></textarea></div>
</div>
<section class="bg-body-tertiary border rounded p-3 p-md-4 mt-4" data-offer-calendar data-selected-dates="<?= e(json_encode(array_values($data['fechas'] ?? []), JSON_UNESCAPED_SLASHES)) ?>">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div><h2 class="h5 mb-1">Calendario de la oferta</h2><p class="text-muted small mb-0">Selecciona el periodo y define las fechas reales de esta materia. Los domingos no estan habilitados.</p></div>
        <span class="badge text-bg-primary" data-offer-calendar-summary></span>
    </div>
    <p class="form-hint period-calendar-status mb-3" data-offer-calendar-empty>Seleccione un periodo para visualizar el calendario.</p>
    <div data-offer-calendar-content hidden>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button class="btn btn-sm btn-outline-primary" type="button" data-offer-calendar-select="habiles"><i class="bi bi-briefcase" aria-hidden="true"></i> Dias habiles</button>
            <button class="btn btn-sm btn-outline-primary" type="button" data-offer-calendar-select="todos"><i class="bi bi-check2-all" aria-hidden="true"></i> Todos</button>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-offer-calendar-select="limpiar"><i class="bi bi-eraser" aria-hidden="true"></i> Limpiar</button>
        </div>
        <div class="offer-calendar-settings mb-3" data-offer-calendar-weekly hidden>
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="offer-calendar-settings-group">
                        <h3 class="offer-calendar-settings-title"><i class="bi bi-calendar-week" aria-hidden="true"></i> Semanas del periodo</h3>
                        <div class="period-calendar-options" data-offer-calendar-weeks></div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="offer-calendar-settings-group">
                        <h3 class="offer-calendar-settings-title"><i class="bi bi-calendar2-check" aria-hidden="true"></i> Dias de la semana</h3>
                        <div class="period-calendar-options"><?php foreach (['Lunes' => 1, 'Martes' => 2, 'Miercoles' => 3, 'Jueves' => 4, 'Viernes' => 5, 'Sabado' => 6, 'Domingo' => 7] as $dayName => $dayNumber): ?><label class="offer-calendar-option<?= $dayNumber === 7 ? ' is-disabled' : '' ?>"><input type="checkbox" value="<?= $dayNumber ?>" data-offer-calendar-weekday <?= $dayNumber <= 5 ? 'checked' : '' ?> <?= $dayNumber === 7 ? 'disabled' : '' ?>> <?= e($dayName) ?><?= $dayNumber === 7 ? ' (no disponible)' : '' ?></label><?php endforeach; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="offer-calendar-months" data-offer-calendar-grid></div>
        <small class="form-hint d-block mt-2" data-offer-calendar-detail></small>
    </div>
</section>
<div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-save" aria-hidden="true"></i> Guardar oferta</button><a class="btn btn-outline-secondary" href="<?= e(app_url('ofertas/')) ?>">Cancelar</a></div></form></section></main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
