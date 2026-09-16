<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Evaluaciones</h1><p>Calificaciones y comentarios de las tutorias realizadas.</p></div><?php if ($role === 'estudiante'): ?><a class="button" href="<?= e(app_url('evaluaciones/create.php')) ?>">Nueva evaluacion</a><?php endif; ?></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <section class="stat-grid compact-stats">
        <article class="stat-card"><span class="stat-label">Evaluaciones</span><strong class="stat-value"><?= (int) ($evaluationSummary['total_evaluaciones'] ?? 0) ?></strong></article>
        <article class="stat-card"><span class="stat-label">Promedio</span><strong class="stat-value"><?= e(number_format((float) ($evaluationSummary['promedio_calificacion'] ?? 0), 2)) ?>/5</strong></article>
    </section>
    <section class="card filter-card">
        <form method="get" action="<?= e(app_url('evaluaciones/')) ?>">
            <div class="form-grid">
                <div><label for="filter_materia">Materia</label><select id="filter_materia" name="id_materia"><option value="">Todas</option><?php foreach ($filterOptions as $option): ?><option value="<?= (int) $option['id_materia'] ?>" <?= (string) $filters['id_materia'] === (string) $option['id_materia'] ? 'selected' : '' ?>><?= e($option['nombre_materia']) ?></option><?php endforeach; ?></select></div>
                <div><label for="fecha_desde">Desde</label><input id="fecha_desde" name="fecha_desde" type="date" value="<?= e($filters['fecha_desde']) ?>"></div>
                <div><label for="fecha_hasta">Hasta</label><input id="fecha_hasta" name="fecha_hasta" type="date" value="<?= e($filters['fecha_hasta']) ?>"></div>
            </div>
            <button type="submit">Filtrar</button> <a class="button secondary" href="<?= e(app_url('evaluaciones/')) ?>">Limpiar</a>
        </form>
    </section>
    <div class="table-wrapper card"><table><thead><tr><th>Fecha</th><th>Materia</th><th>Estudiante</th><th>Tutor</th><th>Calificacion</th><th>Comentario</th></tr></thead><tbody>
        <?php foreach ($evaluations as $evaluation): ?><tr><td><?= e($evaluation['fecha']) ?></td><td><?= e($evaluation['nombre_materia']) ?></td><td><?= e($evaluation['estudiante']) ?></td><td><?= e($evaluation['tutor']) ?></td><td><span class="status status-confirmada"><?= (int) $evaluation['calificacion'] ?>/5</span></td><td><?= e($evaluation['comentario'] ?: 'Sin comentario') ?></td></tr><?php endforeach; ?>
        <?php if (!$evaluations): ?><tr><td colspan="6">No hay evaluaciones registradas.</td></tr><?php endif; ?>
    </tbody></table></div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
