<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Administración MG</span><h1>Plan de estudios por estudiante</h1><p>Asigne una versión aprobada que pertenezca a la carrera del estudiante. Los cambios quedan auditados.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/configuracion.php')) ?>">Volver a Configuración</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <?php if (!$plans): ?><section class="card mg-panel"><h2>No hay planes aprobados</h2><p>Importe y apruebe un plan de estudios antes de asignarlo a estudiantes.</p><a class="btn btn-primary" href="<?= e(app_url('modalidades-grado/academico.php')) ?>">Ir a Datos académicos</a></section>
    <?php else: ?>
        <section class="card mg-panel">
            <form class="mg-cohort-filter" method="get" action="<?= e(app_url('modalidades-grado/planes-estudiante.php')) ?>">
                <label>Buscar estudiante, RU o carrera<input class="form-control" name="buscar" value="<?= e($search) ?>" maxlength="100"></label>
                <button class="btn btn-outline-primary" type="submit">Buscar</button>
            </form>
            <p class="form-hint">Se listan hasta 500 perfiles. El plan se valida de nuevo contra la carrera al guardar.</p>
            <?php if ($students): ?><div class="table-wrapper"><table><thead><tr><th>Estudiante / RU</th><th>Carrera</th><th>Plan asignado</th><th>Asignar o cambiar</th></tr></thead><tbody>
                <?php foreach ($students as $student): $eligiblePlans = array_values(array_filter($plans, static fn (array $plan): bool => (int) $plan['id_carrera'] === (int) $student['id_carrera'])); ?>
                    <tr><td><?= e($student['estudiante']) ?><br><small>RU: <?= e($student['registro_universitario'] ?? '—') ?></small></td><td><?= e($student['nombre_carrera']) ?></td>
                    <td><?php if ($student['id_plan_estudio']): ?><?= e($student['codigo_plan']) ?> / <?= e($student['version_plan']) ?><br><small>Desde <?= e($student['asignado_en']) ?>, por <?= e($student['asignado_por']) ?></small><?php else: ?><span class="status status-inactivo">Sin asignación</span><?php endif; ?></td>
                    <td><?php if ($eligiblePlans): ?><form class="mg-actions" method="post" action="<?= e(app_url('modalidades-grado/planes-estudiante.php?buscar=' . rawurlencode($search))) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_estudiante" value="<?= (int) $student['id_estudiante'] ?>">
                        <select class="form-select" name="id_plan_estudio" required aria-label="Plan para <?= e($student['estudiante']) ?>"><option value="">Seleccione versión</option><?php foreach ($eligiblePlans as $plan): ?><option value="<?= (int) $plan['id_plan_estudio'] ?>" <?= (int) $student['id_plan_estudio'] === (int) $plan['id_plan_estudio'] ? 'selected' : '' ?>><?= e($plan['codigo_plan']) ?> / <?= e($plan['version_plan']) ?> (<?= (int) $plan['materias'] ?> materias)</option><?php endforeach; ?></select>
                        <input class="form-control" name="observacion" maxlength="500" placeholder="Observación (opcional)"><button class="btn btn-sm btn-primary" type="submit">Guardar</button>
                    </form><?php else: ?><span class="form-hint">Aún no hay un plan aprobado para esta carrera.</span><?php endif; ?><p><a href="<?= e(app_url('modalidades-grado/planes-estudiante.php?buscar=' . rawurlencode($search) . '&historial=' . (int) $student['id_estudiante'])) ?>">Ver historial de asignaciones</a></p></td></tr>
                <?php endforeach; ?>
            </tbody></table></div><?php else: ?><p class="form-hint">No se encontraron estudiantes.</p><?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($historyStudentId > 0): ?><section class="card mg-panel"><h2>Historial de asignaciones del estudiante #<?= $historyStudentId ?></h2><?php if ($assignmentHistory): ?><div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Plan anterior</th><th>Plan asignado</th><th>Responsable</th><th>Observación</th></tr></thead><tbody><?php foreach ($assignmentHistory as $assignment): ?><tr><td><?= e($assignment['asignado_en']) ?></td><td><?= $assignment['codigo_anterior'] ? e($assignment['codigo_anterior'] . ' / ' . $assignment['version_anterior']) : 'Primera asignación' ?></td><td><?= e($assignment['codigo_nuevo'] . ' / ' . $assignment['version_nueva']) ?></td><td><?= e($assignment['asignado_por']) ?></td><td><?= e($assignment['observacion'] ?? '—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>No hay cambios de plan registrados para este estudiante.</p><?php endif; ?></section><?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
