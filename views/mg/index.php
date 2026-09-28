<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <section class="page-heading">
        <div><span class="hero-kicker">Subsistema independiente</span><h1>Modalidades de Grado</h1><p>Configuración y seguimiento de los procesos académicos de grado.</p></div>
    </section>

    <?php if ($mode === 'admin' || $mode === 'coordinator'): ?>
        <?php if ($mode === 'admin'): ?>
            <section class="stat-grid" aria-label="Resumen administrativo de Modalidades de Grado">
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Solicitudes por revisar</span><span class="stat-icon">SO</span></div><strong class="stat-value"><?= (int) $adminSummary['solicitudes_por_revisar'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Pendientes de habilitar</span><span class="stat-icon">HA</span></div><strong class="stat-value"><?= (int) $adminSummary['pendientes_habilitar'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Pendientes de inscripción</span><span class="stat-icon">IN</span></div><strong class="stat-value"><?= (int) $adminSummary['pendientes_inscripcion'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Trabajos sin tutor</span><span class="stat-icon">ST</span></div><strong class="stat-value"><?= (int) $adminSummary['trabajos_sin_tutor'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Hitos vencidos</span><span class="stat-icon">HV</span></div><strong class="stat-value"><?= (int) $adminSummary['hitos_vencidos'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Defensas próximas/programadas</span><span class="stat-icon">DF</span></div><strong class="stat-value"><?= (int) $adminSummary['defensas_programadas'] ?></strong></article>
            </section>
            <details class="card mg-panel"><summary>Más indicadores</summary><section class="stat-grid" aria-label="Indicadores secundarios MG">
                <article class="stat-card"><span class="stat-label">Importaciones pendientes</span><strong class="stat-value"><?= (int) $adminSummary['importaciones_pendientes'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Planes aprobados</span><strong class="stat-value"><?= (int) $adminSummary['planes_aprobados'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Estudiantes con plan</span><strong class="stat-value"><?= (int) $adminSummary['estudiantes_con_plan'] ?> / <?= (int) $adminSummary['estudiantes'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Modalidades ofrecidas</span><strong class="stat-value"><?= (int) $adminSummary['ofertas_carrera'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Solicitudes observadas</span><strong class="stat-value"><?= (int) $adminSummary['solicitudes_observadas'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Inscripciones activas</span><strong class="stat-value"><?= (int) $adminSummary['inscripciones_activas'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Trabajos activos</span><strong class="stat-value"><?= (int) $adminSummary['trabajos_activos'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Hitos pendientes</span><strong class="stat-value"><?= (int) $adminSummary['hitos_pendientes'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Trabajos pendientes de cierre</span><strong class="stat-value"><?= (int) $adminSummary['trabajos_pendientes_cierre'] ?></strong></article>
                <article class="stat-card"><span class="stat-label">Cierres aprobados</span><strong class="stat-value"><?= (int) $adminSummary['cierres_aprobados'] ?></strong></article>
            </section></details>
            <p class="form-hint">Las solicitudes aprobadas requieren habilitación; las habilitadas se formalizan desde Cohortes.</p>
        <?php else: ?>
            <section class="stat-grid" aria-label="Resumen de configuración MG">
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Parámetros</span><span class="stat-icon">PA</span></div><strong class="stat-value"><?= (int) $summary['parametros'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Modalidades activas</span><span class="stat-icon">MO</span></div><strong class="stat-value"><?= (int) $summary['modalidades'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Cohortes activas</span><span class="stat-icon">CO</span></div><strong class="stat-value"><?= (int) $summary['cohortes'] ?></strong></article>
                <article class="stat-card"><div class="stat-card-top"><span class="stat-label">Hitos activos</span><span class="stat-icon">HI</span></div><strong class="stat-value"><?= (int) $summary['hitos'] ?></strong></article>
            </section>
        <?php endif; ?>
        <?php if ($mode === 'admin' || $mode === 'coordinator'): ?><p class="form-hint">Usa las opciones del panel izquierdo para administrar las secciones de Modalidades de Grado. Los valores marcados como propuesta o pendientes son informativos y no se usan para bloquear el flujo.</p><?php endif; ?>
    <?php elseif ($mode === 'assistant'): ?>
        <section class="card mg-panel">
            <div class="section-heading"><div><h2>Cohortes activas</h2><p>Consulta cohortes y sus hitos; la gestión está reservada a Coordinación y Administración.</p></div></div>
            <?php if ($cohorts): ?>
                <div class="table-wrapper"><table><thead><tr><th>Código</th><th>Cohorte</th><th>Periodo</th><th>Hitos</th><th>Informes</th><th>Acción</th></tr></thead><tbody>
                    <?php foreach ($cohorts as $cohort): ?><tr><td><?= e($cohort['codigo']) ?></td><td><?= e($cohort['nombre']) ?></td><td><?= e($cohort['fecha_inicio']) ?> – <?= e($cohort['fecha_fin']) ?></td><td><?= (int) $cohort['total_hitos'] ?></td><td><?= (int) $cohort['total_informes'] ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . (int) $cohort['id_cohorte'])) ?>">Ver calendario</a></td></tr><?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?><p class="form-hint">Todavía no hay cohortes activas.</p><?php endif; ?>
        </section>
    <?php elseif ($role === 'estudiante'): ?>
        <?php
        $studentProfile = (new Estudiante())->findByUserId((int) $user['id_usuario']);
        $academicVerification = $studentProfile ? (new MgAcademicoController())->verification((int) $studentProfile['id_estudiante']) : [];
        ?>
        <section class="card mg-panel">
            <h2>Verificación académica</h2>
            <?php if (!$studentProfile): ?><p>No se encontró un perfil de estudiante asociado a esta cuenta.</p>
            <?php elseif (!$academicVerification): ?><p>Administración debe asignarte una versión de plan de estudios y aprobar un historial asociado antes de que el sistema pueda verificar materias o calcular el promedio.</p><a class="btn btn-primary" href="<?= e(app_url('modalidades-grado/mi-solicitud.php')) ?>">Mi Modalidad de Grado</a>
            <?php else: ?><p>Resumen de datos académicos aprobados por Administración. Este resumen informativo todavía no sustituye la solicitud ni su aprobación administrativa.</p>
                <div class="table-wrapper"><table><thead><tr><th>Carrera</th><th>Plan</th><th>Materias obligatorias</th><th>Aprobadas</th><th>Sin registro</th><th>Promedio de aprobadas</th><th>Verificación</th></tr></thead><tbody>
                    <?php foreach ($academicVerification as $plan): $complete = (int) $plan['materias_requeridas'] > 0 && (int) $plan['materias_aprobadas'] === (int) $plan['materias_requeridas']; ?><tr>
                        <td><?= e($plan['nombre_carrera']) ?></td><td><?= e($plan['codigo_plan']) ?> / <?= e($plan['version_plan']) ?></td><td><?= (int) $plan['materias_requeridas'] ?></td><td><?= (int) $plan['materias_aprobadas'] ?></td><td><?= (int) $plan['materias_sin_registro'] ?></td><td><?= $plan['promedio_aprobadas'] === null ? '—' : e(number_format((float) $plan['promedio_aprobadas'], 2)) ?></td><td><?= $complete ? 'Materias requeridas aprobadas' : 'Incompleta' ?></td>
                    </tr><?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </section>
        <section class="card mg-panel"><h2>Tu espacio de Modalidades de Grado</h2><p>Inicia o consulta tu solicitud y sigue el historial de revisión desde tu espacio personal.</p><a class="btn btn-primary" href="<?= e(app_url('modalidades-grado/mi-solicitud.php')) ?>">Mi Modalidad de Grado</a></section>
    <?php else: ?>
        <section class="card mg-panel">
            <h2>Tu espacio de Modalidades de Grado</h2>
            <p>La consulta personal de expedientes y calendarios se habilitará cuando estén disponibles las historias de expedientes y asignaciones. Esta cuenta solo podrá ver información vinculada a sus propios registros.</p>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
