<?php
require __DIR__ . '/../layouts/header.php';

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
$turnos = array_values(array_unique(array_column($subjects, 'turno')));
?>
<main class="container">
    <div class="page-heading">
        <div><h1>Materias disponibles</h1><p>Regístrate en las materias publicadas durante el plazo de inscripción. El tutor puede asignarse después.</p></div>
        <div class="mg-actions"><a class="button" href="<?= e(app_url('inscripciones/')) ?>">Mis materias registradas</a></div>
    </div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="offer-catalog" aria-labelledby="student-offers-title">
        <div class="section-heading offer-section-heading">
            <div><h2 id="student-offers-title">Ofertas publicadas</h2><p>También se muestran las materias que todavía no tienen tutor asignado.</p></div>
            <span class="table-meta" data-offer-count><?= count($subjects) ?> resultado<?= count($subjects) === 1 ? '' : 's' ?></span>
        </div>
        <div class="offer-filters offer-turn-filter" data-offer-filters>
            <div class="offer-search"><i class="bi bi-search" aria-hidden="true"></i><label class="sr-only" for="student-offer-search">Buscar materia o carrera</label><input id="student-offer-search" type="search" placeholder="Buscar materia o carrera..." data-offer-search></div>
            <div class="offer-filter-turnos"><span>Turno</span><div class="offer-filter-buttons" role="group" aria-label="Filtrar materias por turno"><button class="offer-filter-button is-active" type="button" value="" data-offer-filter="turno" data-filter-value="" aria-pressed="true">Todos</button><?php foreach ($turnos as $turno): ?><button class="offer-filter-button" type="button" value="<?= e($turno) ?>" data-offer-filter="turno" data-filter-value="<?= e($turno) ?>" aria-pressed="false"><?= e(ucfirst($turno)) ?></button><?php endforeach; ?></div></div>
        </div>

        <div class="offer-grid student-offer-grid" data-offer-grid>
            <?php foreach ($subjects as $offer):
                $availableSeats = max(0, (int) $offer['cupo'] - (int) $offer['inscritos']);
                $occupancy = (int) $offer['cupo'] > 0 ? min(100, (int) round(((int) $offer['inscritos'] / (int) $offer['cupo']) * 100)) : 0;
                $searchText = strtolower(implode(' ', [$offer['nombre_materia'], $offer['nombre_carrera'] ?? '']));
                $tutorAssigned = (int) ($offer['tutores_confirmados'] ?? 0) > 0;
                $scheduleGroups = $groupSchedules($offer['horarios_oferta'] ?? []);
                $registrationOpen = (int) ($offer['inscripciones_abiertas'] ?? 0) === 1;
                $today = date('Y-m-d');
                $registrationStatus = $offer['periodo_estado'] !== 'publicado'
                    ? 'Las inscripciones están cerradas por Administración.'
                    : ($today < (string) $offer['inscripcion_inicio']
                        ? 'Inscripciones aún no abiertas. Apertura: ' . e($offer['inscripcion_inicio']) . '.'
                        : ($today > (string) $offer['inscripcion_fin']
                            ? 'El plazo de inscripciones cerró el ' . e($offer['inscripcion_fin']) . '.'
                            : 'Las inscripciones están cerradas por Administración.'));
            ?>
                <article class="offer-card student-offer-card" data-offer-card data-search="<?= e($searchText) ?>" data-turno="<?= e($offer['turno']) ?>">
                    <div class="offer-card-accent"></div>
                    <div class="offer-card-badges"><span class="offer-badge offer-badge-type"><?= e($offer['nombre_tipo_tutoria']) ?></span><span class="offer-badge offer-badge-turno"><?= e(ucfirst($offer['turno'])) ?></span><span class="offer-badge offer-badge-frequency"><?= e(ucfirst($offer['frecuencia_programacion'])) ?></span><span class="status status-publicada">Publicada</span><?php if (!$tutorAssigned): ?><span class="status status-pendiente">Tutor por asignar</span><?php endif; ?></div>
                    <div class="offer-card-title"><div class="offer-card-icon"><i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i></div><div><h3><?= e($offer['nombre_materia']) ?></h3><p><?= e($offer['nombre_carrera'] ?: 'Formación general') ?> · <?= e(ucfirst($offer['turno'])) ?> · Grupo <?= e($offer['nombre_grupo']) ?></p></div></div>
                    <?php if (!empty($offer['descripcion'])): ?><p class="offer-description"><?= e($offer['descripcion']) ?></p><?php endif; ?>
                    <div class="admin-offer-period student-offer-period"><i class="bi bi-calendar3" aria-hidden="true"></i><span><strong><?= e($offer['nombre_periodo']) ?></strong><small><?= e($offer['fecha_inicio']) ?> - <?= e($offer['fecha_fin']) ?></small></span></div>
                    <div class="offer-facts student-offer-facts">
                        <div><i class="bi bi-people" aria-hidden="true"></i><span><small>Cupos</small><strong><?= (int) $offer['inscritos'] ?> de <?= (int) $offer['cupo'] ?></strong><em><?= $availableSeats ?> disponibles</em></span></div>
                        <div><i class="bi bi-person-badge" aria-hidden="true"></i><span><small><?= count($offer['tutores_asignados']) > 1 ? 'Tutores' : 'Tutor' ?></small><strong><?php if ($offer['tutores_asignados']): ?><?php foreach ($offer['tutores_asignados'] as $tutorIndex => $assignedTutor): ?><?php if ($tutorIndex > 0): ?>, <?php endif; ?><a class="offer-tutor-link" href="<?= e(app_url('perfil-tutor/?id_tutor=' . (int) $assignedTutor['id_tutor'])) ?>"><?= e($assignedTutor['nombre'] . ' ' . $assignedTutor['apellido']) ?></a><?php endforeach; ?><?php else: ?>Por asignar<?php endif; ?></strong></span></div>
                    </div>
                    <div class="offer-capacity" aria-label="Ocupación <?= $occupancy ?> por ciento"><span style="width: <?= $occupancy ?>%"></span></div>
                    <div class="offer-schedules student-offer-schedules"><small>Horario publicado</small><div><?php foreach ($scheduleGroups as $schedule): ?><span><strong><?= e($schedule['dias']) ?></strong><i class="bi bi-clock" aria-hidden="true"></i><?= e(substr($schedule['hora_inicio'], 0, 5) . '–' . substr($schedule['hora_fin'], 0, 5)) ?><?php if ($schedule['nombre_aula'] !== ''): ?><em><?= e($schedule['nombre_aula']) ?></em><?php endif; ?></span><?php endforeach; ?><?php if (!$scheduleGroups): ?><span class="offer-schedule-empty">Horario por definir</span><?php endif; ?></div></div>
                    <div class="offer-select-form student-offer-register">
                        <?php if ($offer['inscrito']): ?>
                            <p class="success">Ya estás inscrito en esta materia.</p>
                        <?php elseif (!empty($offer['turno_ocupado'])): ?>
                            <p class="offer-inline-note">Ya tienes otra materia registrada en el turno <?= e(ucfirst($offer['turno'])) ?>. Puedes registrar materias de otros turnos.</p>
                        <?php elseif (!$registrationOpen): ?>
                            <p class="offer-inline-note"><?= $registrationStatus ?></p>
                        <?php elseif ($availableSeats < 1): ?>
                            <p class="empty-state">La oferta no tiene cupos disponibles.</p>
                        <?php else: ?>
                            <?php if (!$tutorAssigned): ?><p class="offer-inline-note">Puedes registrar la materia ahora. Administración podrá asignarte un tutor después.</p><?php endif; ?>
                            <form method="post" action="<?= e(app_url('inscripciones/create.php')) ?>"><input type="hidden" name="id_oferta" value="<?= (int) $offer['id_oferta'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-primary" type="submit">Registrar materia</button></form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="card offer-empty" data-offer-filter-empty hidden><i class="bi bi-search" aria-hidden="true"></i><h3>Sin coincidencias</h3><p>Cambia el turno o la búsqueda.</p></div>
        <?php if (!$subjects): ?><div class="card offer-empty"><i class="bi bi-inbox" aria-hidden="true"></i><h3>No hay materias publicadas</h3><p>Cuando Administración publique ofertas vigentes, aparecerán aquí.</p></div><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
