<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Modalidades de Grado</span><h1>Configuración</h1><p>Catálogos, reglas y datos académicos para preparar el proceso de Modalidad de Grado.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/')) ?>">Volver al resumen</a></div>
    <section class="quick-links mg-config-links" aria-label="Secciones de configuración de Modalidades de Grado">
        <a href="<?= e(app_url('modalidades-grado/parametros.php')) ?>"><span>PA</span><strong>Parámetros</strong><small>Valores configurables y nivel de evidencia</small></a>
        <a href="<?= e(app_url('modalidades-grado/modalidades.php')) ?>"><span>MO</span><strong>Modalidades</strong><small>Reglas, etapas e indicadores por modalidad</small></a>
        <?php if ($role === 'administrador'): ?>
            <a href="<?= e(app_url('modalidades-grado/oferta-carreras.php')) ?>"><span>OC</span><strong>Oferta por carrera</strong><small>Modalidades permitidas según carrera</small></a>
            <a href="<?= e(app_url('modalidades-grado/academico.php')) ?>"><span>DA</span><strong>Datos académicos</strong><small>Importar, validar y aprobar planes e historiales</small></a>
            <a href="<?= e(app_url('modalidades-grado/planes-estudiante.php')) ?>"><span>PE</span><strong>Planes por estudiante</strong><small>Asignar la versión académica correspondiente</small></a>
        <?php endif; ?>
    </section>
    <p class="form-hint">Los valores marcados como pendiente o propuesta no se aplican como restricciones automáticas.</p>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
