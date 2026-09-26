<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <section class="page-heading">
        <div><span class="hero-kicker">Subsistema independiente</span><h1>Modalidades de Grado</h1><p>Configuración y seguimiento de los procesos académicos de grado.</p></div>
    </section>

    <?php if ($mode === 'admin' || $mode === 'coordinator'): ?>
        <section class="stat-grid" aria-label="Resumen de configuración MG">
            <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Parámetros</span><span class="stat-icon">PA</span></div><strong class="stat-value"><?= (int) $summary['parametros'] ?></strong></article>
            <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Modalidades activas</span><span class="stat-icon">MO</span></div><strong class="stat-value"><?= (int) $summary['modalidades'] ?></strong></article>
            <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Cohortes activas</span><span class="stat-icon">CO</span></div><strong class="stat-value"><?= (int) $summary['cohortes'] ?></strong></article>
            <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Hitos activos</span><span class="stat-icon">HI</span></div><strong class="stat-value"><?= (int) $summary['hitos'] ?></strong></article>
        </section>
        <section class="quick-links mg-config-links" aria-label="Configuración de Modalidades de Grado">
            <?php if ($mode === 'admin' || $role === 'coordinador_mg'): ?>
                <a href="<?= e(app_url('modalidades-grado/parametros.php')) ?>"><span>PA</span><strong>Parámetros</strong><small>Valores configurables y estado de evidencia</small></a>
                <a href="<?= e(app_url('modalidades-grado/modalidades.php')) ?>"><span>MO</span><strong>Modalidades</strong><small>Catálogo y requerimiento de tutor</small></a>
                <a href="<?= e(app_url('modalidades-grado/cohortes.php')) ?>"><span>CO</span><strong>Cohortes</strong><small>Crear, actualizar o desactivar cohortes</small></a>
                <a href="<?= e(app_url('modalidades-grado/calendario.php')) ?>"><span>CA</span><strong>Calendario</strong><small>Gestionar hitos por cohorte</small></a>
            <?php endif; ?>
        </section>
        <p class="form-hint">Los valores marcados como propuesta o pendientes son informativos; no se usan para bloquear el flujo.</p>
    <?php elseif ($mode === 'assistant'): ?>
        <section class="card mg-panel">
            <div class="section-heading"><div><h2>Cohortes activas</h2><p>Consulta cohortes y sus hitos; la gestión está reservada a Coordinación y Administración.</p></div></div>
            <?php if ($cohorts): ?>
                <div class="table-wrapper"><table><thead><tr><th>Código</th><th>Cohorte</th><th>Periodo</th><th>Hitos</th><th>Informes</th><th>Acción</th></tr></thead><tbody>
                    <?php foreach ($cohorts as $cohort): ?><tr><td><?= e($cohort['codigo']) ?></td><td><?= e($cohort['nombre']) ?></td><td><?= e($cohort['fecha_inicio']) ?> – <?= e($cohort['fecha_fin']) ?></td><td><?= (int) $cohort['total_hitos'] ?></td><td><?= (int) $cohort['total_informes'] ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . (int) $cohort['id_cohorte'])) ?>">Ver calendario</a></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?><p class="form-hint">Todavía no hay cohortes activas.</p><?php endif; ?>
        </section>
    <?php else: ?>
        <section class="card mg-panel">
            <h2>Tu espacio de Modalidades de Grado</h2>
            <p>La consulta personal de expedientes y calendarios se habilitará cuando estén disponibles las historias de expedientes y asignaciones. Esta cuenta solo podrá ver información vinculada a sus propios registros.</p>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
