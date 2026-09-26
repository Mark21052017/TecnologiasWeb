<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php
$formatDate = static fn (string $date): string => date('d/m/Y', strtotime($date));
$statusInfo = static function (array $offer): array {
    if ($offer['tutor_estado'] === 'rechazada') {
        return ['rechazada', 'Rechazada'];
    }
    if ($offer['oferta_estado'] === 'cancelada') {
        return ['cancelada', 'Cancelada'];
    }
    if ($offer['oferta_estado'] === 'cerrada' || $offer['periodo_estado'] === 'cerrado') {
        return ['cerrada', 'Cerrada'];
    }
    if ($offer['oferta_estado'] === 'finalizada' || $offer['periodo_estado'] === 'finalizado' || $offer['fecha_fin'] < date('Y-m-d')) {
        return ['finalizada', 'Finalizada'];
    }
    if ($offer['fecha_inicio'] <= date('Y-m-d')) {
        return ['activa', 'Activa'];
    }

    return ['proxima', 'Próxima'];
};
$types = array_values(array_unique(array_column($availableSubjects, 'nombre_tipo_tutoria')));
$turnos = array_values(array_unique(array_column($availableSubjects, 'turno')));
$periods = [];
foreach ($availableSubjects as $offer) {
    $periods[$offer['nombre_periodo']] = $offer['nombre_periodo'];
}
?>
<main class="container offered-subjects-page">
    <div class="page-heading offered-subjects-heading">
        <div><span class="hero-kicker">Oferta académica</span><h1>Materias ofertadas</h1><p>Elige las materias que deseas impartir y configura tus turnos disponibles.</p></div>
        <div class="offered-subjects-summary"><strong><?= count($availableSubjects) ?></strong><span>disponibles</span></div>
    </div>

    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="offer-catalog" aria-labelledby="available-offers-title">
        <div class="section-heading offer-section-heading"><div><h2 id="available-offers-title">Ofertas disponibles</h2><p>Disponibles hasta antes del inicio de cada periodo.</p></div><span class="table-meta" data-offer-count><?= count($availableSubjects) ?> resultado<?= count($availableSubjects) === 1 ? '' : 's' ?></span></div>

        <div class="offer-filters" data-offer-filters>
            <div class="offer-search"><i class="bi bi-search" aria-hidden="true"></i><label class="sr-only" for="offer-search">Buscar materia o carrera</label><input id="offer-search" type="search" placeholder="Buscar materia o carrera..." data-offer-search></div>
            <label><span>Tipo</span><select data-offer-filter="type"><option value="">Todos</option><?php foreach ($types as $type): ?><option value="<?= e(strtolower($type)) ?>"><?= e(ucfirst($type)) ?></option><?php endforeach; ?></select></label>
            <label><span>Turno</span><select data-offer-filter="turno"><option value="">Todos</option><?php foreach ($turnos as $turno): ?><option value="<?= e($turno) ?>"><?= e(ucfirst($turno)) ?></option><?php endforeach; ?></select></label>
            <label><span>Periodo</span><select data-offer-filter="period"><option value="">Todos</option><?php foreach ($periods as $period): ?><option value="<?= e(strtolower($period)) ?>"><?= e($period) ?></option><?php endforeach; ?></select></label>
            <button class="offer-filter-reset" type="button" data-offer-reset><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Limpiar</button>
        </div>

        <div class="offer-grid" data-offer-grid>
            <?php foreach ($availableSubjects as $offer):
                $occupancy = (int) $offer['cupo'] > 0 ? min(100, (int) round(((int) $offer['inscritos'] / (int) $offer['cupo']) * 100)) : 0;
                $searchText = strtolower(implode(' ', [$offer['nombre_materia'], $offer['nombre_carrera'] ?? '', $offer['nombre_periodo'], $offer['nombre_grupo'], $offer['turno']]));
            ?>
                <article class="offer-card" data-offer-card data-search="<?= e($searchText) ?>" data-type="<?= e(strtolower($offer['nombre_tipo_tutoria'])) ?>" data-period="<?= e(strtolower($offer['nombre_periodo'])) ?>" data-turno="<?= e($offer['turno']) ?>">
                    <div class="offer-card-accent"></div>
                    <div class="offer-card-badges"><span class="offer-badge offer-badge-type"><?= e($offer['nombre_tipo_tutoria']) ?></span><span class="offer-badge offer-badge-turno"><?= e(ucfirst($offer['turno'])) ?></span><span class="offer-badge offer-badge-frequency"><?= e(ucfirst($offer['frecuencia_programacion'])) ?></span></div>
                    <div class="offer-card-title"><div class="offer-card-icon"><i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i></div><div><h3><?= e($offer['nombre_materia']) ?></h3><p><?= e($offer['nombre_carrera'] ?: 'Formación general') ?> · <?= e(ucfirst($offer['turno'])) ?> · Grupo <?= e($offer['nombre_grupo']) ?></p></div></div>
                    <?php if (!empty($offer['descripcion'])): ?><p class="offer-description"><?= e($offer['descripcion']) ?></p><?php endif; ?>
                    <div class="offer-facts">
                        <div><i class="bi bi-calendar3" aria-hidden="true"></i><span><small>Periodo</small><strong><?= e($offer['nombre_periodo']) ?></strong><em><?= e($formatDate($offer['fecha_inicio'])) ?> - <?= e($formatDate($offer['fecha_fin'])) ?></em></span></div>
                        <div><i class="bi bi-people" aria-hidden="true"></i><span><small>Cupos</small><strong><?= (int) $offer['inscritos'] ?> de <?= (int) $offer['cupo'] ?></strong><em><?= max(0, (int) $offer['cupo'] - (int) $offer['inscritos']) ?> disponibles</em></span></div>
                    </div>
                    <div class="offer-capacity" aria-label="Ocupación <?= $occupancy ?> por ciento"><span style="width: <?= $occupancy ?>%"></span></div>
                    <div class="offer-schedules"><small>Horarios propuestos</small><div><?php foreach ($offer['horarios_oferta'] as $schedule): ?><span><i class="bi bi-clock" aria-hidden="true"></i><?= e($schedule['dia_semana'] . ' · ' . $schedule['nombre_turno']) ?><?php if ($schedule['nombre_aula']): ?><em><?= e($schedule['nombre_aula']) ?></em><?php endif; ?></span><?php endforeach; ?><?php if (!$offer['horarios_oferta']): ?><span class="offer-schedule-empty">Por definir</span><?php endif; ?></div></div>
                    <form class="offer-select-form" method="post" action="<?= e(app_url('materias-ofertadas/seleccionar.php')) ?>" onsubmit="return confirm('¿Confirmas que deseas impartir <?= e(addslashes($offer['nombre_materia'])) ?> durante el periodo <?= e(addslashes($offer['nombre_periodo'])) ?>?');">
                        <input type="hidden" name="id_oferta" value="<?= (int) $offer['id_oferta'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i> Seleccionar para impartir</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="card offer-empty" data-offer-empty <?= $availableSubjects ? 'hidden' : '' ?>><i class="bi bi-inbox" aria-hidden="true"></i><h3>No hay ofertas disponibles</h3><p>No encontramos materias publicadas que puedan seleccionarse antes de iniciar su periodo.</p></div>
        <div class="card offer-empty" data-offer-filter-empty hidden><i class="bi bi-search" aria-hidden="true"></i><h3>Sin coincidencias</h3><p>Prueba con otros términos o limpia los filtros.</p></div>
    </section>

    <section class="selected-offers" aria-labelledby="selected-offers-title">
        <div class="section-heading offer-section-heading"><div><h2 id="selected-offers-title">Materias que impartiré</h2><p>Configura tu disponibilidad y consulta el avance de cada oferta.</p></div><span class="table-meta"><?= count($subjects) ?> seleccionada<?= count($subjects) === 1 ? '' : 's' ?></span></div>
        <?php if ($subjects): ?><div class="selected-offer-grid">
            <?php foreach ($subjects as $offer): [$statusClass, $statusLabel] = $statusInfo($offer); $selectedIds = array_map('intval', array_column($offer['horarios'], 'id_oferta_horario')); ?>
                <article class="card selected-offer-card">
                    <div class="selected-offer-header"><div><span class="offer-badge offer-badge-turno"><?= e(ucfirst($offer['turno'])) ?></span><span class="offer-badge offer-badge-frequency"><?= e(ucfirst($offer['frecuencia_programacion'])) ?></span><span class="offer-badge offer-badge-type"><?= e($offer['nombre_tipo_tutoria']) ?></span></div><span class="status status-<?= e($statusClass) ?>"><?= e($statusLabel) ?></span></div>
                    <h3><?= e($offer['nombre_materia']) ?></h3><p class="selected-offer-subtitle"><?= e($offer['nombre_periodo']) ?> · <?= e(ucfirst($offer['turno'])) ?> · Grupo <?= e($offer['nombre_grupo']) ?></p>
                    <div class="selected-offer-stats"><span><i class="bi bi-calendar3" aria-hidden="true"></i><?= e($formatDate($offer['fecha_inicio'])) ?> - <?= e($formatDate($offer['fecha_fin'])) ?></span><span><i class="bi bi-people" aria-hidden="true"></i><?= (int) $offer['inscritos'] ?> estudiante<?= (int) $offer['inscritos'] === 1 ? '' : 's' ?></span></div>
                    <?php if ($offer['tutor_estado'] === 'confirmada' && !in_array($statusClass, ['cancelada', 'finalizada'], true)): ?>
                        <form class="selected-schedule-form" method="post" action="<?= e(app_url('materias-ofertadas/horarios.php')) ?>">
                            <input type="hidden" name="id_oferta_tutor" value="<?= (int) $offer['id_oferta_tutor'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <fieldset><legend>Mi disponibilidad</legend><?php foreach ($offer['horarios_oferta'] as $schedule): ?><label class="offer-schedule-option"><input type="checkbox" name="id_oferta_horario[]" value="<?= (int) $schedule['id_oferta_horario'] ?>" <?= in_array((int) $schedule['id_oferta_horario'], $selectedIds, true) ? 'checked' : '' ?>><span><strong><?= e($schedule['dia_semana'] . ' · ' . $schedule['nombre_turno']) ?></strong><small><?= e($schedule['nombre_aula'] ?: 'Aula por definir') ?></small></span></label><?php endforeach; ?></fieldset>
                            <?php if ($offer['horarios_oferta']): ?><button type="submit"><i class="bi bi-calendar-check" aria-hidden="true"></i> Guardar disponibilidad</button><?php else: ?><p class="offer-inline-note">La universidad aún no definió turnos.</p><?php endif; ?>
                        </form>
                    <?php elseif ($statusClass === 'rechazada'): ?><p class="offer-inline-note">Esta selección se conserva únicamente como historial.</p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div><?php else: ?><div class="card offer-empty"><i class="bi bi-journal-plus" aria-hidden="true"></i><h3>Aún no seleccionaste materias</h3><p>Elige una oferta disponible para comenzar a configurar tus horarios.</p></div><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
