<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Configuración MG</span><h1>Calendario por cohorte</h1><p>La cantidad de informes se determina con los hitos activos cuyo tipo sea <code>informe</code>.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/')) ?>">Volver a MG</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="card mg-panel">
        <form class="mg-cohort-filter" method="get" action="<?= e(app_url('modalidades-grado/calendario.php')) ?>">
            <label>Cohorte<select class="form-select" name="id_cohorte" required><?php foreach ($cohorts as $option): ?><option value="<?= (int) $option['id_cohorte'] ?>" <?= (int) $option['id_cohorte'] === $selectedCohortId ? 'selected' : '' ?>><?= e($option['codigo'] . ' · ' . $option['nombre']) ?><?= (int) $option['activa'] === 1 ? '' : ' (inactiva)' ?></option><?php endforeach; ?></select></label>
            <button class="btn btn-outline-primary" type="submit">Ver calendario</button>
        </form>
        <?php if ($selectedCohort): ?><p class="mg-calendar-summary"><strong><?= e($selectedCohort['nombre']) ?></strong><span><?= e($selectedCohort['fecha_inicio']) ?> – <?= e($selectedCohort['fecha_fin']) ?></span><span><?= $reportCount ?> <?= $reportCount === 1 ? 'hito de informe' : 'hitos de informe' ?></span></p><?php endif; ?>
    </section>

    <?php if (!$readOnly && $selectedCohort && (int) $selectedCohort['activa'] === 1): ?>
        <section class="card mg-panel">
            <h2><?= $milestone ? 'Editar hito' : 'Agregar hito' ?></h2>
            <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId)) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_cohorte" value="<?= $selectedCohortId ?>">
                <?php if ($milestone): ?><input type="hidden" name="id_hito" value="<?= (int) $milestone['id_hito'] ?>"><?php endif; ?>
                <label>Etapa<select class="form-select" name="etapa" required><?php foreach (['previa' => 'Previa', 'mg1' => 'MG1', 'mg2' => 'MG2', 'finalizado' => 'Finalizado'] as $value => $label): ?><option value="<?= e($value) ?>" <?= ($milestone['etapa'] ?? 'previa') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                <label>Tipo<input class="form-control" name="tipo" list="mg-hito-tipos" maxlength="40" required value="<?= e($milestone['tipo'] ?? '') ?>" placeholder="taller, informe, defensa..."><datalist id="mg-hito-tipos"><option value="taller"><option value="asignacion_tutor"><option value="informe"><option value="asignacion_tribunal"><option value="defensa"><option value="ingreso_mg2"></datalist></label>
                <label>Nombre<input class="form-control" name="nombre" maxlength="180" required value="<?= e($milestone['nombre'] ?? '') ?>"></label>
                <label>Orden<input class="form-control" name="orden" type="number" min="0" step="1" required value="<?= e((string) ($milestone['orden'] ?? 0)) ?>"></label>
                <label>Fecha límite<input class="form-control" name="fecha_limite" type="date" value="<?= e($milestone['fecha_limite'] ?? '') ?>"></label>
                <label>Avance esperado (%)<input class="form-control" name="avance_esperado_pct" type="number" min="0" max="100" step="0.01" value="<?= e((string) ($milestone['avance_esperado_pct'] ?? '')) ?>"></label>
                <button class="btn btn-primary" type="submit"><?= $milestone ? 'Guardar hito' : 'Agregar hito' ?></button>
                <?php if ($milestone): ?><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId)) ?>">Cancelar</a><?php endif; ?>
            </form>
        </section>
    <?php endif; ?>

    <section class="card mg-panel">
        <div class="section-heading"><div><h2>Hitos</h2><p><?= $readOnly ? 'Vista de consulta.' : 'Los hitos se desactivan para conservar su historial.' ?></p></div></div>
        <?php if ($milestones): ?><div class="table-wrapper"><table><thead><tr><th>Orden</th><th>Etapa</th><th>Tipo</th><th>Hito</th><th>Fecha límite</th><th>Avance esperado</th><th>Estado</th><?php if (!$readOnly): ?><th>Acciones</th><?php endif; ?></tr></thead><tbody>
            <?php foreach ($milestones as $row): ?><tr><td><?= (int) $row['orden'] ?></td><td><?= e(strtoupper($row['etapa'])) ?></td><td><code><?= e($row['tipo']) ?></code></td><td><?= e($row['nombre']) ?></td><td><?= e($row['fecha_limite'] ?? '—') ?></td><td><?= $row['avance_esperado_pct'] === null ? '—' : e($row['avance_esperado_pct']) . '%' ?></td><td><?= e($row['estado']) ?></td><?php if (!$readOnly): ?><td class="mg-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId . '&id_hito=' . (int) $row['id_hito'])) ?>">Editar</a><form method="post" action="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId)) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_cohorte" value="<?= $selectedCohortId ?>"><input type="hidden" name="id_hito" value="<?= (int) $row['id_hito'] ?>"><input type="hidden" name="estado" value="<?= $row['estado'] === 'activo' ? 'inactivo' : 'activo' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= $row['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button></form></td><?php endif; ?></tr><?php endforeach; ?>
        </tbody></table></div><?php elseif ($selectedCohort): ?><p class="form-hint">Esta cohorte aún no tiene hitos.</p><?php else: ?><p class="form-hint">Crea una cohorte para configurar su calendario.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
