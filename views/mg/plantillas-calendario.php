<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Cohortes MG</span><h1>Plantillas de calendario</h1><p>Crea una plantilla desde un calendario existente y aplícala a otra cohorte de la misma modalidad.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/cohortes.php')) ?>">Volver a Cohortes</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <section class="card mg-panel"><h2>Crear plantilla desde calendario</h2><p>Se copian los hitos activos de la pareja cohorte/modalidad. Las fechas se almacenan como días relativos al inicio de la cohorte origen.</p>
        <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/plantillas-calendario.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="crear">
            <label>Cohorte origen<select class="form-select" name="id_cohorte" required><option value="">Seleccione</option><?php foreach ($cohorts as $cohort): if ((int) $cohort['activa'] === 1): ?><option value="<?= (int) $cohort['id_cohorte'] ?>"><?= e($cohort['codigo'] . ' · ' . $cohort['nombre']) ?></option><?php endif; endforeach; ?></select></label>
            <label>Modalidad<select class="form-select" name="id_modalidad" required><option value="">Seleccione</option><?php foreach ($modalities as $modality): ?><option value="<?= (int) $modality['id_modalidad'] ?>" <?= $selectedModalityId === (int) $modality['id_modalidad'] ? 'selected' : '' ?>><?= e($modality['nombre']) ?></option><?php endforeach; ?></select></label>
            <label>Nombre de plantilla<input class="form-control" name="nombre" maxlength="150" required></label>
            <label>Descripción<input class="form-control" name="descripcion" maxlength="1000"></label>
            <button class="btn btn-primary" type="submit">Crear plantilla</button>
        </form>
    </section>

    <section class="card mg-panel"><h2>Plantillas disponibles</h2>
        <form class="mg-cohort-filter" method="get" action="<?= e(app_url('modalidades-grado/plantillas-calendario.php')) ?>"><label>Filtrar modalidad<select class="form-select" name="id_modalidad"><option value="0">Todas</option><?php foreach ($modalities as $modality): ?><option value="<?= (int) $modality['id_modalidad'] ?>" <?= $selectedModalityId === (int) $modality['id_modalidad'] ? 'selected' : '' ?>><?= e($modality['nombre']) ?></option><?php endforeach; ?></select></label><button class="btn btn-outline-primary" type="submit">Filtrar</button></form>
        <?php if ($templates): ?><div class="table-wrapper"><table><thead><tr><th>Plantilla</th><th>Modalidad</th><th>Hitos</th><th>Descripción</th><th>Aplicar en cohorte</th></tr></thead><tbody>
            <?php foreach ($templates as $template): ?><tr><td><?= e($template['nombre']) ?></td><td><?= e($template['modalidad']) ?></td><td><?= (int) $template['total_hitos'] ?></td><td><?= e($template['descripcion'] ?? '—') ?></td><td><form class="mg-actions" method="post" action="<?= e(app_url('modalidades-grado/plantillas-calendario.php?id_modalidad=' . (int) $template['id_modalidad'])) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="aplicar"><input type="hidden" name="id_plantilla" value="<?= (int) $template['id_plantilla'] ?>"><label class="visually-hidden" for="cohorte-<?= (int) $template['id_plantilla'] ?>">Cohorte destino</label><select class="form-select" id="cohorte-<?= (int) $template['id_plantilla'] ?>" name="id_cohorte" required><option value="">Seleccione cohorte destino</option><?php foreach ($cohorts as $cohort): if ((int) $cohort['activa'] === 1): ?><option value="<?= (int) $cohort['id_cohorte'] ?>"><?= e($cohort['codigo'] . ' · ' . $cohort['nombre']) ?></option><?php endif; endforeach; ?></select><button class="btn btn-sm btn-primary" type="submit">Aplicar</button></form></td></tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">Aún no hay plantillas para este filtro.</p><?php endif; ?>
        <p class="form-hint">La aplicación se rechaza si ya existe calendario para esa cohorte y modalidad o si una fecha calculada queda fuera del periodo de cohorte.</p>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
