<?php require __DIR__ . '/../layouts/header.php'; ?>
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
$groupSchedules = static function (array $schedules) use ($formatDays): array {
    $groups = [];
    foreach ($schedules as $schedule) {
        $room = (string) ($schedule['nombre_aula'] ?? '');
        $key = implode('|', [$schedule['hora_inicio'], $schedule['hora_fin'], $room]);
        $groups[$key] ??= [
            'dias' => [],
            'hora_inicio' => $schedule['hora_inicio'],
            'hora_fin' => $schedule['hora_fin'],
            'nombre_aula' => $room,
        ];
        $groups[$key]['dias'][] = $schedule['dia_semana'];
    }
    foreach ($groups as &$group) {
        $group['dias'] = $formatDays($group['dias']);
    }
    unset($group);
    return array_values($groups);
};
$statusInfo = static function (array $offer): array {
    if ($offer['tutor_estado'] === 'baja_solicitada') {
        return ['baja-solicitada', 'Baja en revisión'];
    }
    if ($offer['tutor_estado'] === 'cancelada') {
        return ['cancelada', 'Baja completada'];
    }
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
$turnos = array_values(array_unique(array_column($availableSubjects, 'turno')));
$selectedCount = count(array_filter($subjects, static fn (array $offer): bool =>
    $offer['tutor_estado'] === 'confirmada'
    && !in_array($offer['oferta_estado'], ['cancelada', 'finalizada'], true)
    && $offer['periodo_estado'] !== 'finalizado'
));
$selectableCount = count(array_filter($availableSubjects, static fn (array $offer): bool =>
    empty($offer['materia_bloqueante'])
    && !empty($offer['seleccion_habilitada'])
    && !empty($offer['horarios_oferta'])
));
?>
<main class="container offered-subjects-page">
    <div class="page-heading offered-subjects-heading">
        <div><span class="hero-kicker">Oferta académica</span><h1>Materias ofertadas</h1><p>Consulta las materias publicadas y acepta el horario completo definido por la universidad.</p></div>
        <div class="offered-subjects-summary"><strong><?= $selectableCount ?></strong><span>disponibles para aceptar</span></div>
    </div>

    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="offer-catalog" aria-labelledby="available-offers-title">
        <div class="section-heading offer-section-heading"><div><h2 id="available-offers-title">Ofertas publicadas</h2><p>Al aceptar, te comprometes a cumplir todos los días y horarios publicados. Si el periodo ya comenzó, se muestran las ofertas que necesitan tutor o reemplazo.</p></div><span class="table-meta" data-offer-count><?= count($availableSubjects) ?> resultado<?= count($availableSubjects) === 1 ? '' : 's' ?></span></div>

        <div class="offer-filters offer-turn-filter" data-offer-filters>
            <div class="offer-search"><i class="bi bi-search" aria-hidden="true"></i><label class="sr-only" for="offer-search">Buscar materia o carrera</label><input id="offer-search" type="search" placeholder="Buscar materia o carrera..." data-offer-search></div>
            <div class="offer-filter-turnos"><span>Turno</span><div class="offer-filter-buttons" role="group" aria-label="Filtrar ofertas por turno"><button class="offer-filter-button is-active" type="button" value="" data-offer-filter="turno" data-filter-value="" aria-pressed="true">Todos</button><?php foreach ($turnos as $turno): ?><button class="offer-filter-button" type="button" value="<?= e($turno) ?>" data-offer-filter="turno" data-filter-value="<?= e($turno) ?>" aria-pressed="false"><?= e(ucfirst($turno)) ?></button><?php endforeach; ?></div></div>
        </div>

        <div class="offer-grid" data-offer-grid>
            <?php foreach ($availableSubjects as $offer):
                $occupancy = (int) $offer['cupo'] > 0 ? min(100, (int) round(((int) $offer['inscritos'] / (int) $offer['cupo']) * 100)) : 0;
                $searchText = strtolower(implode(' ', [$offer['nombre_materia'], $offer['nombre_carrera'] ?? '']));
                $scheduleGroups = $groupSchedules($offer['horarios_oferta']);
                $needsReplacement = !empty($offer['necesita_reemplazo']);
            ?>
                <article class="offer-card" data-offer-card data-search="<?= e($searchText) ?>" data-type="<?= e(strtolower($offer['nombre_tipo_tutoria'])) ?>" data-period="<?= e(strtolower($offer['nombre_periodo'])) ?>" data-turno="<?= e($offer['turno']) ?>">
                    <div class="offer-card-accent"></div>
                    <div class="offer-card-badges"><span class="offer-badge offer-badge-type"><?= e($offer['nombre_tipo_tutoria']) ?></span><span class="offer-badge offer-badge-turno"><?= e(ucfirst($offer['turno'])) ?></span><span class="offer-badge offer-badge-frequency"><?= e(ucfirst($offer['frecuencia_programacion'])) ?></span><span class="offer-badge offer-badge-type"><?= $needsReplacement ? 'Reemplazo requerido' : (!empty($offer['tiene_tutor_confirmado']) ? 'Tutor confirmado' : 'Tutor por asignar') ?></span></div>
                    <div class="offer-card-title"><div class="offer-card-icon"><i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i></div><div><h3><?= e($offer['nombre_materia']) ?></h3><p><?= e($offer['nombre_carrera'] ?: 'Formación general') ?> · <?= e(ucfirst($offer['turno'])) ?> · Grupo <?= e($offer['nombre_grupo']) ?></p></div></div>
                    <?php if (!empty($offer['descripcion'])): ?><p class="offer-description"><?= e($offer['descripcion']) ?></p><?php endif; ?>
                    <div class="offer-facts">
                        <div><i class="bi bi-calendar3" aria-hidden="true"></i><span><small>Periodo</small><strong><?= e($offer['nombre_periodo']) ?></strong><em><?= e($formatDate($offer['fecha_inicio'])) ?> - <?= e($formatDate($offer['fecha_fin'])) ?></em></span></div>
                        <div><i class="bi bi-people" aria-hidden="true"></i><span><small>Cupos</small><strong><?= (int) $offer['inscritos'] ?> de <?= (int) $offer['cupo'] ?></strong><em><?= max(0, (int) $offer['cupo'] - (int) $offer['inscritos']) ?> disponibles</em></span></div>
                    </div>
                    <div class="offer-capacity" aria-label="Ocupación <?= $occupancy ?> por ciento"><span style="width: <?= $occupancy ?>%"></span></div>
                    <div class="offer-schedules"><small>Horario de impartición</small><div><?php foreach ($scheduleGroups as $schedule): ?><span><strong><?= e($schedule['dias']) ?></strong><i class="bi bi-clock" aria-hidden="true"></i><?= e(substr($schedule['hora_inicio'], 0, 5) . '–' . substr($schedule['hora_fin'], 0, 5)) ?><?php if ($schedule['nombre_aula'] !== ''): ?><em><?= e($schedule['nombre_aula']) ?></em><?php endif; ?></span><?php endforeach; ?><?php if (!$scheduleGroups): ?><span class="offer-schedule-empty">Horario por definir</span><?php endif; ?></div></div>
                    <?php if ($needsReplacement): ?><p class="offer-inline-note"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Se busca un tutor de reemplazo para una asignación en revisión.</p><?php endif; ?>
                    <form class="offer-select-form" method="post" action="<?= e(app_url('materias-ofertadas/seleccionar.php')) ?>" onsubmit="return confirm('¿Confirmas que impartirás esta materia en todos los días y horarios indicados?');">
                        <input type="hidden" name="id_oferta" value="<?= (int) $offer['id_oferta'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <?php if (!empty($offer['materia_bloqueante'])): ?>
                            <p class="offer-conflict-note" role="note"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Ya tienes <?= e($offer['materia_bloqueante']) ?> en el mismo turno/periodo o con un horario que se solapa; no puedes aceptar esta materia.</p>
                            <button type="submit" disabled aria-disabled="true"><i class="bi bi-lock-fill" aria-hidden="true"></i> Turno ocupado</button>
                        <?php elseif (empty($offer['seleccion_habilitada'])): ?>
                            <p class="offer-inline-note">El periodo ya comenzó y la oferta tiene un tutor confirmado. Solo se habilitará si se necesita un reemplazo.</p>
                            <button type="submit" disabled aria-disabled="true"><i class="bi bi-lock-fill" aria-hidden="true"></i> Selección cerrada</button>
                        <?php elseif (!$scheduleGroups): ?>
                            <p class="offer-inline-note">Administración debe definir el horario antes de que puedas aceptar esta materia.</p>
                            <button type="submit" disabled aria-disabled="true"><i class="bi bi-lock-fill" aria-hidden="true"></i> Horario pendiente</button>
                        <?php else: ?>
                            <label class="offer-commitment-check"><input type="checkbox" name="acepto_horarios" value="1" required><span>Acepto impartir la materia en todos los días y horarios indicados.</span></label>
                            <button type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i> <?= $needsReplacement ? 'Aceptar reemplazo' : 'Seleccionar para impartir' ?></button>
                        <?php endif; ?>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="card offer-empty" data-offer-empty <?= $availableSubjects ? 'hidden' : '' ?>><i class="bi bi-inbox" aria-hidden="true"></i><h3>No hay ofertas publicadas para aceptar</h3><p>Se muestran ofertas publicadas vigentes y las que requieren cubrir una baja.</p></div>
        <div class="card offer-empty" data-offer-filter-empty hidden><i class="bi bi-search" aria-hidden="true"></i><h3>Sin coincidencias</h3><p>Prueba con otros términos o limpia los filtros.</p></div>
    </section>

    <section class="selected-offers" aria-labelledby="selected-offers-title">
        <div class="section-heading offer-section-heading"><div><h2 id="selected-offers-title">Materias que impartiré</h2><p>Consulta los horarios que aceptaste y el avance de cada oferta.</p></div><span class="table-meta"><?= $selectedCount ?> seleccionada<?= $selectedCount === 1 ? '' : 's' ?></span></div>
        <?php if ($subjects): ?><div class="selected-offer-grid">
            <?php foreach ($subjects as $offer): [$statusClass, $statusLabel] = $statusInfo($offer); $committedSchedules = $offer['horarios'] ?: $offer['horarios_oferta']; $scheduleGroups = $groupSchedules($committedSchedules); ?>
                <article class="card selected-offer-card">
                    <div class="selected-offer-header"><div><span class="offer-badge offer-badge-turno"><?= e(ucfirst($offer['turno'])) ?></span><span class="offer-badge offer-badge-frequency"><?= e(ucfirst($offer['frecuencia_programacion'])) ?></span><span class="offer-badge offer-badge-type"><?= e($offer['nombre_tipo_tutoria']) ?></span></div><span class="status status-<?= e($statusClass) ?>"><?= e($statusLabel) ?></span></div>
                    <h3><?= e($offer['nombre_materia']) ?></h3><p class="selected-offer-subtitle"><?= e($offer['nombre_periodo']) ?> · <?= e(ucfirst($offer['turno'])) ?> · Grupo <?= e($offer['nombre_grupo']) ?></p>
                    <div class="selected-offer-stats"><span><i class="bi bi-calendar3" aria-hidden="true"></i><?= e($formatDate($offer['fecha_inicio'])) ?> - <?= e($formatDate($offer['fecha_fin'])) ?></span><span><i class="bi bi-people" aria-hidden="true"></i><?= (int) $offer['inscritos'] ?> estudiante<?= (int) $offer['inscritos'] === 1 ? '' : 's' ?></span></div>
                    <?php if ((int) ($offer['materias_mismo_turno'] ?? 0) > 1): ?><p class="offer-conflict-note" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Hay varias materias asignadas a este turno en el mismo periodo. Contacta a Administración para corregir la asignación.</p><?php endif; ?>
                    <?php if ($scheduleGroups && !in_array($offer['tutor_estado'], ['cancelada', 'rechazada'], true)): ?><div class="offer-schedules selected-committed-schedules"><small><?= $offer['tutor_estado'] === 'baja_solicitada' ? 'Horario actual mientras se revisa la baja' : 'Horario confirmado' ?></small><div><?php foreach ($scheduleGroups as $schedule): ?><span><strong><?= e($schedule['dias']) ?></strong><i class="bi bi-clock" aria-hidden="true"></i><?= e(substr($schedule['hora_inicio'], 0, 5) . '–' . substr($schedule['hora_fin'], 0, 5)) ?><?php if ($schedule['nombre_aula'] !== ''): ?><em><?= e($schedule['nombre_aula']) ?></em><?php endif; ?></span><?php endforeach; ?></div></div><?php endif; ?>
                    <?php if ($offer['tutor_estado'] === 'baja_solicitada' && !empty($offer['motivo_baja'])): ?><p class="offer-inline-note">Tu motivo: <?= e($offer['motivo_baja']) ?>. Administración coordina el reemplazo.</p><?php endif; ?>
                    <?php if ($offer['tutor_estado'] === 'cancelada' && !empty($offer['motivo_baja'])): ?><p class="offer-inline-note">Motivo de baja: <?= e($offer['motivo_baja']) ?></p><?php endif; ?>
                    <?php if ($offer['ultima_baja_estado'] === 'rechazada'): ?><p class="offer-inline-note">Administración rechazó tu solicitud de baja<?= !empty($offer['respuesta_baja']) ? ': ' . e($offer['respuesta_baja']) : '.' ?></p><?php endif; ?>
                    <?php if ($offer['tutor_estado'] === 'rechazada'): ?><p class="offer-inline-note">Esta selección se conserva únicamente como historial.</p><?php endif; ?>
                    <?php if ($offer['tutor_estado'] === 'confirmada' && !in_array($statusClass, ['cancelada', 'finalizada'], true)): ?>
                        <button class="btn btn-sm btn-outline-danger" type="button" data-tutor-withdrawal-open data-id-oferta-tutor="<?= (int) $offer['id_oferta_tutor'] ?>" data-materia="<?= e($offer['nombre_materia']) ?>" data-periodo="<?= e($offer['nombre_periodo']) ?>" data-turno="<?= e(ucfirst($offer['turno'])) ?>"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Dejar de impartir</button>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div><?php else: ?><div class="card offer-empty"><i class="bi bi-journal-plus" aria-hidden="true"></i><h3>Aún no seleccionaste materias</h3><p>Elige una oferta publicada y acepta todos los horarios definidos.</p></div><?php endif; ?>
    </section>
</main>
<dialog class="tutor-withdrawal-dialog" data-tutor-withdrawal-dialog>
    <form method="post" action="<?= e(app_url('materias-ofertadas/cancelar.php')) ?>" class="tutor-withdrawal-dialog-content">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id_oferta_tutor" value="" data-tutor-withdrawal-id>
        <h2>Dejar de impartir</h2>
        <p>Indica por qué solicitas dejar esta asignación. Si ya hay estudiantes o sesiones, Administración coordinará un reemplazo antes de completar la baja.</p>
        <dl><dt>Materia</dt><dd data-tutor-withdrawal-subject></dd><dt>Periodo / turno</dt><dd><span data-tutor-withdrawal-period></span> · <span data-tutor-withdrawal-turn></span></dd></dl>
        <label for="motivo-baja-tutor">Motivo *</label>
        <textarea class="form-control" id="motivo-baja-tutor" name="motivo" maxlength="500" rows="4" required></textarea>
        <div class="form-actions"><button class="btn btn-outline-secondary" type="button" data-tutor-withdrawal-close>Volver</button><button class="btn btn-danger" type="submit">Enviar solicitud</button></div>
    </form>
</dialog>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
