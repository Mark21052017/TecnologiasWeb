<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Configuración MG</span><h1>Cohortes</h1><p>Una cohorte inactiva conserva sus expedientes e hitos; no se elimina.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/')) ?>">Volver a MG</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (Auth::user()['nombre_rol'] === 'administrador'): ?><section class="card mg-panel"><h2>Estudiantes y trabajos de cohorte</h2><p>Formaliza solicitudes habilitadas y asigna tutores a trabajos activos desde las tareas de cohorte.</p><div class="mg-actions"><a class="btn btn-outline-primary" href="<?= e(app_url('modalidades-grado/inscripciones.php')) ?>">Inscripciones habilitadas</a><a class="btn btn-outline-primary" href="<?= e(app_url('modalidades-grado/asignaciones-tutor.php')) ?>">Asignación de tutores</a></div></section><?php endif; ?>
    <?php if ($canManage): ?>
        <section class="card mg-panel">
            <h2><?= $cohort ? 'Editar cohorte' : 'Crear cohorte' ?></h2>
            <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/cohortes.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($cohort): ?><input type="hidden" name="id_cohorte" value="<?= (int) $cohort['id_cohorte'] ?>"><?php endif; ?>
                <label>Código<input class="form-control" name="codigo" required maxlength="40" value="<?= e($cohort['codigo'] ?? '') ?>" placeholder="G1-2026-03"></label>
                <label>Nombre<input class="form-control" name="nombre" required maxlength="150" value="<?= e($cohort['nombre'] ?? '') ?>" placeholder="Grupo 1 - Marzo 2026"></label>
                <label>Fecha de inicio<input class="form-control" type="date" name="fecha_inicio" required value="<?= e($cohort['fecha_inicio'] ?? '') ?>"></label>
                <label>Fecha de fin<input class="form-control" type="date" name="fecha_fin" required value="<?= e($cohort['fecha_fin'] ?? '') ?>"></label>
                <button class="btn btn-primary" type="submit"><?= $cohort ? 'Guardar cambios' : 'Crear cohorte' ?></button>
                <?php if ($cohort): ?><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/cohortes.php')) ?>">Cancelar edición</a><?php endif; ?>
            </form>
        </section>
    <?php endif; ?>
    <section class="card mg-panel">
        <div class="section-heading"><div><h2>Cohortes registradas</h2><p>Los informes visibles son el recuento de hitos activos con tipo <code>informe</code>.</p></div></div>
        <?php if ($cohorts): ?><div class="table-wrapper"><table><thead><tr><th>Código</th><th>Nombre</th><th>Fechas</th><th>Hitos</th><th>Informes</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
            <?php foreach ($cohorts as $row): ?><tr><td><?= e($row['codigo']) ?></td><td><?= e($row['nombre']) ?></td><td><?= e($row['fecha_inicio']) ?> – <?= e($row['fecha_fin']) ?></td><td><?= (int) $row['total_hitos'] ?></td><td><?= (int) $row['total_informes'] ?></td><td><span class="status <?= (int) $row['activa'] === 1 ? 'status-activo' : 'status-inactivo' ?>"><?= (int) $row['activa'] === 1 ? 'Activa' : 'Inactiva' ?></span></td><td class="mg-actions">
                <?php if ($canManage): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/cohortes.php?id=' . (int) $row['id_cohorte'])) ?>">Editar</a><form method="post" action="<?= e(app_url('modalidades-grado/cohortes.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_cohorte" value="<?= (int) $row['id_cohorte'] ?>"><input type="hidden" name="activa" value="<?= (int) $row['activa'] === 1 ? '0' : '1' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= (int) $row['activa'] === 1 ? 'Desactivar' : 'Activar' ?></button></form><?php endif; ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/calendario.php?id_cohorte=' . (int) $row['id_cohorte'])) ?>">Calendario</a>
            </td></tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">Todavía no hay cohortes.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
