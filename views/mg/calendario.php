<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Cohortes MG</span><h1>Calendario por cohorte y modalidad</h1><p>Los calendarios se separan por cohorte y modalidad. El calendario legado sin modalidad se conserva para consulta.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/cohortes.php')) ?>">Volver a Cohortes</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="card mg-panel">
        <form class="mg-cohort-filter" method="get" action="<?= e(app_url('modalidades-grado/calendario.php')) ?>">
            <label>Cohorte<select class="form-select" name="id_cohorte" required><?php foreach ($cohorts as $option): ?><option value="<?= (int) $option['id_cohorte'] ?>" <?= (int) $option['id_cohorte'] === $selectedCohortId ? 'selected' : '' ?>><?= e($option['codigo'] . ' · ' . $option['nombre']) ?><?= (int) $option['activa'] === 1 ? '' : ' (inactiva)' ?></option><?php endforeach; ?></select></label>
            <label>Modalidad<select class="form-select" name="id_modalidad" required><option value="legacy" <?= $legacyCalendar ? 'selected' : '' ?>>Legado sin modalidad (solo consulta)</option><?php foreach ($modalities as $option): ?><option value="<?= (int) $option['id_modalidad'] ?>" <?= !$legacyCalendar && (int) $option['id_modalidad'] === $selectedModalityId ? 'selected' : '' ?>><?= e($option['nombre']) ?><?= $option['estado'] === 'activa' ? '' : ' (inactiva)' ?></option><?php endforeach; ?></select></label>
            <button class="btn btn-outline-primary" type="submit">Ver calendario</button>
        </form>
        <?php if ($selectedCohort): ?><p class="mg-calendar-summary"><strong><?= e($selectedCohort['nombre']) ?></strong><span><?= e($selectedCohort['fecha_inicio']) ?> – <?= e($selectedCohort['fecha_fin']) ?></span><span><?= $legacyCalendar ? 'Legado' : e($selectedModality['nombre'] ?? 'Modalidad') ?></span><span><?= $reportCount ?> <?= $reportCount === 1 ? 'hito de informe' : 'hitos de informe' ?></span></p><?php endif; ?>
    </section>

    <?php if ($canManage): ?>
        <section class="card mg-panel">
            <div class="section-heading"><div><h2><?= $milestone ? 'Editar hito' : 'Agregar hito' ?></h2><p>Las obligaciones se generan automáticamente para trabajos activos de esta cohorte y modalidad.</p></div><a class="btn btn-outline-primary" href="<?= e(app_url('modalidades-grado/plantillas-calendario.php?id_modalidad=' . $selectedModalityId)) ?>">Plantillas</a></div>
            <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId . '&id_modalidad=' . $selectedModalityId)) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_cohorte" value="<?= $selectedCohortId ?>"><input type="hidden" name="id_modalidad" value="<?= $selectedModalityId ?>">
                <?php if ($milestone): ?><input type="hidden" name="id_hito" value="<?= (int) $milestone['id_hito'] ?>"><?php endif; ?>
                <label>Etapa<select class="form-select" name="etapa" required><?php foreach (['previa' => 'Previa', 'mg1' => 'MDG I', 'mg2' => 'MDG II', 'defensa' => 'Defensa', 'cierre' => 'Cierre', 'finalizado' => 'Finalizado'] as $value => $label): ?><option value="<?= e($value) ?>" <?= ($milestone['etapa'] ?? 'previa') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                <label>Tipo<select class="form-select" name="tipo" required><option value="">Seleccione</option><?php foreach ($milestoneTypes as $type): ?><option value="<?= e($type['codigo']) ?>" <?= ($milestone['tipo'] ?? '') === $type['codigo'] ? 'selected' : '' ?>><?= e($type['nombre']) ?></option><?php endforeach; ?></select></label>
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
        <div class="section-heading"><div><h2>Hitos</h2><p><?= $readOnly ? 'Vista de consulta; los hitos legado no se reasignan automáticamente.' : 'Los hitos se desactivan para conservar su historial. Cada hito activo genera una obligación por trabajo.' ?></p></div></div>
        <?php if ($milestones): ?><div class="table-wrapper"><table><thead><tr><th>Orden</th><th>Etapa</th><th>Tipo</th><th>Hito</th><th>Fecha límite</th><th>Avance esperado</th><th>Obligaciones</th><th>Estado</th><?php if (!$readOnly): ?><th>Acciones</th><?php endif; ?></tr></thead><tbody>
            <?php foreach ($milestones as $row): ?><tr><td><?= (int) $row['orden'] ?></td><td><?= e(strtoupper($row['etapa'])) ?></td><td><?= e($row['tipo_nombre'] ?? $row['tipo']) ?></td><td><?= e($row['nombre']) ?></td><td><?= e($row['fecha_limite'] ?? '—') ?></td><td><?= $row['avance_esperado_pct'] === null ? '—' : e($row['avance_esperado_pct']) . '%' ?></td><td><?= (int) $row['obligaciones'] ?></td><td><?= e($row['estado']) ?></td><?php if (!$readOnly): ?><td class="mg-actions"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId . '&id_modalidad=' . $selectedModalityId . '&id_hito=' . (int) $row['id_hito'])) ?>">Editar</a><form method="post" action="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . $selectedCohortId . '&id_modalidad=' . $selectedModalityId)) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_cohorte" value="<?= $selectedCohortId ?>"><input type="hidden" name="id_modalidad" value="<?= $selectedModalityId ?>"><input type="hidden" name="id_hito" value="<?= (int) $row['id_hito'] ?>"><input type="hidden" name="estado" value="<?= $row['estado'] === 'activo' ? 'inactivo' : 'activo' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= $row['estado'] === 'activo' ? 'Desactivar' : 'Activar' ?></button></form></td><?php endif; ?></tr><?php endforeach; ?>
        </tbody></table></div><?php elseif ($selectedCohort): ?><p class="form-hint">Esta cohorte aún no tiene hitos.</p><?php else: ?><p class="form-hint">Crea una cohorte para configurar su calendario.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
