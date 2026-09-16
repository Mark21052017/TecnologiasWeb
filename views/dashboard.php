<?php
$title = 'Resumen general';
require __DIR__ . '/layouts/header.php';
$role = (string) ($user['nombre_rol'] ?? '');
$statValues = [
    'total_usuarios' => (int) ($stats['total_usuarios'] ?? 0),
    'usuarios_activos' => (int) ($stats['usuarios_activos'] ?? 0),
    'total_tutores' => (int) ($stats['total_tutores'] ?? 0),
    'total_estudiantes' => (int) ($stats['total_estudiantes'] ?? 0),
    'total_carreras' => (int) ($stats['total_carreras'] ?? 0),
    'total_materias' => (int) ($stats['total_materias'] ?? 0),
    'tutorias_pendientes' => (int) ($stats['tutorias_pendientes'] ?? 0),
    'tutorias_confirmadas' => (int) ($stats['tutorias_confirmadas'] ?? 0),
];
?>

<main class="container">
    <section class="hero-heading">
        <div>
            <div class="hero-kicker">Panel principal</div>
            <h1>Hola, <?= e($user['nombre'] ?? 'usuario') ?>.</h1>
            <p>Este es el estado actual de tu espacio academico.</p>
        </div>
        <span class="status status-activo">Sesion activa</span>
    </section>

    <?php if ($role === 'administrador'): ?><section class="stat-grid" aria-label="Resumen del sistema">
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-label">Usuarios</span><span class="stat-icon">US</span></div>
            <strong class="stat-value"><?= $statValues['total_usuarios'] ?></strong>
        </article>
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-label">Activos</span><span class="stat-icon">AC</span></div>
            <strong class="stat-value"><?= $statValues['usuarios_activos'] ?></strong>
        </article>
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-label">Tutores</span><span class="stat-icon">TU</span></div>
            <strong class="stat-value"><?= $statValues['total_tutores'] ?></strong>
        </article>
        <article class="stat-card">
            <div class="stat-card-top"><span class="stat-label">Estudiantes</span><span class="stat-icon">ES</span></div>
            <strong class="stat-value"><?= $statValues['total_estudiantes'] ?></strong>
        </article>
    </section><?php elseif ($role === 'tutor'): ?><section class="stat-grid" aria-label="Resumen del tutor">
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Mis materias</span><span class="stat-icon">MA</span></div><strong class="stat-value"><?= (int) ($stats['materias_asignadas'] ?? 0) ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Pendientes</span><span class="stat-icon">PE</span></div><strong class="stat-value"><?= (int) ($stats['tutorias_pendientes'] ?? 0) ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Confirmadas</span><span class="stat-icon">CO</span></div><strong class="stat-value"><?= (int) ($stats['tutorias_confirmadas'] ?? 0) ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Mi promedio</span><span class="stat-icon">EV</span></div><strong class="stat-value"><?= e(number_format((float) ($stats['promedio_calificacion'] ?? 0), 2)) ?>/5</strong></article>
    </section><?php else: ?><section class="stat-grid" aria-label="Resumen del estudiante">
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Pendientes</span><span class="stat-icon">PE</span></div><strong class="stat-value"><?= (int) ($stats['tutorias_pendientes'] ?? 0) ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Confirmadas</span><span class="stat-icon">CO</span></div><strong class="stat-value"><?= (int) ($stats['tutorias_confirmadas'] ?? 0) ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Por evaluar</span><span class="stat-icon">EV</span></div><strong class="stat-value"><?= (int) ($stats['evaluaciones_pendientes'] ?? 0) ?></strong></article>
        <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Materias</span><span class="stat-icon">MA</span></div><strong class="stat-value"><?= (int) ($stats['materias_disponibles'] ?? 0) ?></strong></article>
    </section><?php endif; ?>

    <?php if ($role === 'administrador'): ?><section class="quick-grid">
        <article class="card">
            <div class="section-heading">
                <h2>Catalogo academico</h2>
                <?php if (($user['nombre_rol'] ?? '') === 'administrador'): ?>
                    <a href="<?= e(app_url('materias/')) ?>">Ver materias</a>
                <?php endif; ?>
            </div>
            <ul class="activity-list">
                <li><div><strong>Carreras registradas</strong><span>Clasificacion academica</span></div><span class="activity-count"><?= $statValues['total_carreras'] ?></span></li>
                <li><div><strong>Materias disponibles</strong><span>Asignaturas para tutorias</span></div><span class="activity-count"><?= $statValues['total_materias'] ?></span></li>
            </ul>
        </article>

        <article class="card">
            <div class="section-heading">
                <h2>Tutorias</h2>
                <span class="eyebrow">Estado</span>
            </div>
            <ul class="activity-list">
                <li><div><strong>Pendientes</strong><span>Esperando confirmacion</span></div><span class="activity-count"><?= $statValues['tutorias_pendientes'] ?></span></li>
                <li><div><strong>Confirmadas</strong><span>Proximas sesiones</span></div><span class="activity-count"><?= $statValues['tutorias_confirmadas'] ?></span></li>
            </ul>
        </article>
    </section><?php endif; ?>

    <?php if ($role === 'tutor'): ?><section class="card dashboard-actions"><div class="section-heading"><h2>Operacion del tutor</h2><span class="eyebrow">Mi espacio</span></div><div class="quick-links">
        <a href="<?= e(app_url('mis-materias/')) ?>"><span>MA</span><strong>Mis materias</strong><small>Gestionar asignaturas</small></a>
        <a href="<?= e(app_url('disponibilidad/')) ?>"><span>DI</span><strong>Disponibilidad</strong><small>Definir horarios</small></a>
        <a href="<?= e(app_url('tutorias/')) ?>"><span>TI</span><strong>Tutorias</strong><small>Atender solicitudes</small></a>
        <a href="<?= e(app_url('evaluaciones/')) ?>"><span>EV</span><strong>Evaluaciones</strong><small>Revisar opiniones</small></a>
    </div></section><?php endif; ?>

    <?php if (($user['nombre_rol'] ?? '') === 'administrador'): ?>
        <section class="card dashboard-actions">
            <div class="section-heading">
                <h2>Accesos rapidos</h2>
                <span class="eyebrow">Administracion</span>
            </div>
            <div class="quick-links">
                <a href="<?= e(app_url('usuarios/')) ?>"><span>US</span><strong>Cuentas de acceso</strong><small>Gestionar credenciales</small></a>
                <a href="<?= e(app_url('roles/')) ?>"><span>RO</span><strong>Roles</strong><small>Permisos del sistema</small></a>
                <a href="<?= e(app_url('carreras/')) ?>"><span>CA</span><strong>Carreras</strong><small>Catalogo academico</small></a>
                <a href="<?= e(app_url('materias/')) ?>"><span>MA</span><strong>Materias</strong><small>Asignaturas disponibles</small></a>
                <a href="<?= e(app_url('estudiantes/')) ?>"><span>ES</span><strong>Estudiantes</strong><small>Perfiles academicos</small></a>
                <a href="<?= e(app_url('tutores/')) ?>"><span>TU</span><strong>Tutores</strong><small>Perfiles profesionales</small></a>
                <a href="<?= e(app_url('asignaciones/')) ?>"><span>AS</span><strong>Asignaciones</strong><small>Materias por tutor</small></a>
                <a href="<?= e(app_url('accesos/')) ?>"><span>LG</span><strong>Accesos</strong><small>Auditoria del sistema</small></a>
                <a href="<?= e(app_url('permisos/')) ?>"><span>PE</span><strong>Permisos</strong><small>Acceso por usuario y rol</small></a>
            </div>
        </section>
    <?php endif; ?>
    <?php if (($user['nombre_rol'] ?? '') === 'estudiante'): ?>
        <section class="card dashboard-actions">
            <div class="section-heading"><h2>Explorar apoyo academico</h2><span class="eyebrow">Mi espacio</span></div>
            <div class="quick-links">
                <a href="<?= e(app_url('materias-disponibles/')) ?>"><span>MA</span><strong>Materias</strong><small>Ver asignaturas disponibles</small></a>
                <a href="<?= e(app_url('tutores-disponibles/')) ?>"><span>TU</span><strong>Tutores</strong><small>Conocer tutores activos</small></a>
                <a href="<?= e(app_url('horarios-disponibles/')) ?>"><span>HO</span><strong>Horarios</strong><small>Consultar disponibilidad</small></a>
                <a href="<?= e(app_url('tutorias/create.php')) ?>"><span>TI</span><strong>Solicitar</strong><small>Crear una tutoria</small></a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/layouts/footer.php'; ?>
