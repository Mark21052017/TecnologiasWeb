<?php
$formatDate = static fn (string $date): string => date('d/m/Y', strtotime($date));
$dayOrder = ['Lunes' => 0, 'Martes' => 1, 'Miercoles' => 2, 'Jueves' => 3, 'Viernes' => 4, 'Sabado' => 5];
$dayLabels = ['Lunes' => 'Lun', 'Martes' => 'Mar', 'Miercoles' => 'Mié', 'Jueves' => 'Jue', 'Viernes' => 'Vie', 'Sabado' => 'Sáb'];
$formatDays = static function (array $days) use ($dayOrder, $dayLabels): string {
    $days = array_values(array_unique(array_filter($days, static fn (string $day): bool => isset($dayOrder[$day]))));
    usort($days, static fn (string $first, string $second): int => $dayOrder[$first] <=> $dayOrder[$second]);
    $segments = [];
    for ($index = 0, $total = count($days); $index < $total;) {
        $end = $index;
        while ($end + 1 < $total && $dayOrder[$days[$end + 1]] === $dayOrder[$days[$end]] + 1) {
            $end++;
        }
        $segments[] = $index === $end
            ? $dayLabels[$days[$index]]
            : $dayLabels[$days[$index]] . '–' . $dayLabels[$days[$end]];
        $index = $end + 1;
    }
    if (count($segments) < 2) {
        return $segments[0] ?? '';
    }
    $last = array_pop($segments);
    return ($segments ? implode(', ', $segments) . ' y ' : '') . $last;
};
$types = array_values(array_unique(array_column($ofertas, 'nombre_tipo_tutoria')));
$periods = [];
$statuses = [];
$turnos = [];
foreach ($ofertas as $offer) {
    $periods[$offer['nombre_periodo']] = $offer['nombre_periodo'];
    $statuses[$offer['estado']] = $offer['estado'];
    $turnos[(int) $offer['id_turno']] = $offer['turno'];
}
ksort($periods);
ksort($statuses);
?>
<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container offered-subjects-page admin-offers-page">
    <div class="page-heading offered-subjects-heading">
        <div><span class="hero-kicker">Planificacion universitaria</span><h1>Ofertas academicas</h1><p>Consulta materias, turnos, horarios, aulas y cupos desde una vista clara.</p></div>
        <div class="page-heading-actions"><span class="offered-subjects-summary"><strong><?= count($ofertas) ?></strong><span>ofertas</span></span><a class="button btn btn-primary" href="<?= e(app_url('ofertas/create.php')) ?>">Nueva oferta</a></div>
    </div>

    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="offer-catalog" aria-labelledby="admin-offers-title">
        <div class="section-heading offer-section-heading"><div><h2 id="admin-offers-title">Catalogo de ofertas</h2><p>Filtra la planificacion por turno, periodo, estado o tipo.</p></div><span class="table-meta" data-offer-count><?= count($ofertas) ?> resultado<?= count($ofertas) === 1 ? '' : 's' ?></span></div>

        <div class="offer-filters admin-offer-filters" data-offer-filters>
            <div class="offer-filter-turnos"><span>Turno</span><div class="offer-filter-buttons" role="group" aria-label="Filtrar por turno"><button class="offer-filter-button is-active" type="button" value="" data-offer-filter="turno" data-filter-value="" aria-pressed="true">Todos</button><?php foreach ($turnos as $turnId => $turno): ?><button class="offer-filter-button" type="button" value="<?= (int) $turnId ?>" data-offer-filter="turno" data-filter-value="<?= (int) $turnId ?>" aria-pressed="false"><?= e($turno) ?></button><?php endforeach; ?></div></div>
            <div class="offer-search"><i class="bi bi-search" aria-hidden="true"></i><label class="sr-only" for="admin-offer-search">Buscar oferta</label><input class="form-control form-control-sm" id="admin-offer-search" type="search" placeholder="Buscar materia, carrera o paralelo..." data-offer-search></div>
            <label><span>Periodo</span><select class="form-select form-select-sm" data-offer-filter="period"><option value="">Todos</option><?php foreach ($periods as $period): ?><option value="<?= e(strtolower($period)) ?>"><?= e($period) ?></option><?php endforeach; ?></select></label>
            <label><span>Estado</span><select class="form-select form-select-sm" data-offer-filter="status"><option value="">Todos</option><?php foreach ($statuses as $status): ?><option value="<?= e(strtolower($status)) ?>"><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></label>
            <label><span>Tipo</span><select class="form-select form-select-sm" data-offer-filter="type"><option value="">Todos</option><?php foreach ($types as $type): ?><option value="<?= e(strtolower($type)) ?>"><?= e(ucfirst($type)) ?></option><?php endforeach; ?></select></label>
            <button class="offer-filter-reset" type="button" data-offer-reset><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Limpiar</button>
        </div>

        <div class="offer-grid admin-offer-grid" data-offer-grid>
            <?php foreach ($ofertas as $offer):
                $occupancy = (int) $offer['cupo'] > 0 ? min(100, (int) round(((int) $offer['inscritos'] / (int) $offer['cupo']) * 100)) : 0;
                $isFull = (int) $offer['inscritos'] >= (int) $offer['cupo'] && !in_array($offer['estado'], ['cancelada', 'finalizada'], true);
                $scheduleGroups = [];
                foreach ($offer['horarios_oferta'] as $schedule) {
                    $roomId = (string) ($schedule['id_aula'] ?? $schedule['nombre_aula'] ?? '');
                    $groupKey = implode('|', [$schedule['hora_inicio'], $schedule['hora_fin'], $roomId]);
                    $scheduleGroups[$groupKey] ??= ['days' => [], 'hora_inicio' => $schedule['hora_inicio'], 'hora_fin' => $schedule['hora_fin'], 'nombre_aula' => $schedule['nombre_aula'] ?? ''];
                    $scheduleGroups[$groupKey]['days'][] = $schedule['dia_semana'];
                }
                $scheduleSearch = implode(' ', array_map(static fn (array $schedule): string => implode(' ', [
                    $schedule['dia_semana'],
                    $schedule['nombre_aula'] ?? '',
                    substr($schedule['hora_inicio'], 0, 5),
                    substr($schedule['hora_fin'], 0, 5),
                ]), $offer['horarios_oferta']));
                $searchText = strtolower(implode(' ', [$offer['nombre_materia'], $offer['nombre_carrera'] ?? '', $offer['nombre_periodo'], $offer['nombre_grupo'], $offer['turno'], $scheduleSearch]));
            ?>
                <article class="offer-card admin-offer-card" data-offer-card data-search="<?= e($searchText) ?>" data-type="<?= e(strtolower($offer['nombre_tipo_tutoria'])) ?>" data-period="<?= e(strtolower($offer['nombre_periodo'])) ?>" data-status="<?= e(strtolower($offer['estado'])) ?>" data-turno="<?= (int) $offer['id_turno'] ?>">
                    <div class="offer-card-accent"></div>
                    <div class="admin-offer-card-header"><div class="offer-card-badges"><span class="offer-badge offer-badge-type"><?= e($offer['nombre_tipo_tutoria']) ?></span><span class="offer-badge offer-badge-turno"><?= e(ucfirst($offer['turno'])) ?></span><span class="offer-badge offer-badge-frequency"><?= e(ucfirst($offer['frecuencia_programacion'])) ?></span></div><div class="admin-offer-statuses"><span class="status status-<?= e($offer['estado']) ?>"><?= e($offer['estado']) ?></span><?php if ($isFull): ?><span class="status status-llena">Llena</span><?php endif; ?></div></div>
                    <div class="offer-card-title"><div class="offer-card-icon"><i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i></div><div><h3><?= e($offer['nombre_materia']) ?></h3><p><?= e($offer['nombre_carrera'] ?: 'Formacion general') ?> · Grupo <?= e($offer['nombre_grupo']) ?></p></div></div>
                    <div class="admin-offer-period"><i class="bi bi-calendar3" aria-hidden="true"></i><span><strong><?= e($offer['nombre_periodo']) ?></strong><small><?= e($formatDate($offer['fecha_inicio'])) ?> - <?= e($formatDate($offer['fecha_fin'])) ?></small></span></div>
                    <div class="offer-facts">
                        <div class="offer-fact-capacity"><i class="bi bi-people" aria-hidden="true"></i><span><small>Cupos</small><strong><?= (int) $offer['inscritos'] ?> de <?= (int) $offer['cupo'] ?></strong><em><?= max(0, (int) $offer['cupo'] - (int) $offer['inscritos']) ?> disponibles</em><span class="offer-capacity" role="img" aria-label="Ocupacion <?= $occupancy ?> por ciento"><span style="width: <?= $occupancy ?>%"></span></span></span></div>
                        <div class="offer-fact-teacher"><i class="bi bi-person-badge" aria-hidden="true"></i><span><small>Docente</small><strong>Por asignar</strong></span></div>
                        <div><i class="bi bi-calendar-check" aria-hidden="true"></i><span><small>Calendario</small><strong><?= (int) $offer['total_fechas'] ?></strong><em><?= (int) $offer['total_fechas'] === 1 ? 'fecha' : 'fechas' ?></em></span></div>
                    </div>
                    <div class="offer-schedules admin-offer-schedules">
                        <div class="admin-offer-schedules-heading"><small>Horario y aula</small></div>
                        <div class="admin-offer-schedule-list">
                            <?php foreach ($scheduleGroups as $schedule): ?>
                                <div class="admin-offer-schedule">
                                    <strong class="admin-offer-schedule-days"><?= e($formatDays($schedule['days'])) ?></strong>
                                    <span class="admin-offer-schedule-time"><i class="bi bi-clock" aria-hidden="true"></i><?= e(substr($schedule['hora_inicio'], 0, 5) . '–' . substr($schedule['hora_fin'], 0, 5)) ?></span>
                                    <span class="admin-offer-schedule-room"><i class="bi bi-door-open" aria-hidden="true"></i><?= e($schedule['nombre_aula'] ?: 'Aula por definir') ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$scheduleGroups): ?><span class="offer-schedule-empty">Sin horarios definidos</span><?php endif; ?>
                        </div>
                    </div>
                     <div class="admin-offer-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('ofertas/edit.php?id=' . (int) $offer['id_oferta'])) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar</a><form method="post" action="<?= e(app_url('ofertas/delete.php')) ?>" onsubmit="return confirm('Eliminar esta oferta?');"><input type="hidden" name="id" value="<?= (int) $offer['id_oferta'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i> Eliminar</button></form></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="card offer-empty" data-offer-filter-empty hidden><i class="bi bi-search" aria-hidden="true"></i><h3>Sin coincidencias</h3><p>Cambia los filtros o limpia la busqueda.</p></div>
        <?php if (!$ofertas): ?><div class="card offer-empty"><i class="bi bi-inbox" aria-hidden="true"></i><h3>No hay ofertas creadas</h3><p>Crea una oferta para que los tutores puedan seleccionarla.</p></div><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
