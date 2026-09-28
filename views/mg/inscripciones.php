<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Cohortes MG</span><h1>Inscripciones formales</h1><p>Solo se inscriben solicitudes aprobadas y habilitadas. Una cohorte puede contener cualquier cantidad de estudiantes.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/cohortes.php')) ?>">Volver a Cohortes</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <section class="card mg-panel">
        <h2>Solicitudes habilitadas pendientes de inscripción</h2>
        <?php if (!$cohorts): ?><p class="alert">Crea o activa una cohorte antes de inscribir solicitudes.</p>
        <?php elseif ($eligible): ?><div class="table-wrapper"><table><thead><tr><th>Estudiante / RU</th><th>Carrera</th><th>Solicitud / modalidad</th><th>Tema propuesto</th><th>Habilitada</th><th>Formalizar inscripción</th></tr></thead><tbody>
            <?php foreach ($eligible as $request): ?><tr>
                <td><?= e($request['estudiante']) ?><br><small><?= e($request['registro_universitario'] ?? 'Sin RU') ?></small></td><td><?= e($request['nombre_carrera']) ?></td>
                <td>#<?= (int) $request['id_solicitud'] ?> · <?= e($request['modalidad']) ?><br><small>Intención del estudiante: <?= e($request['tipo_trabajo']) ?></small></td>
                <td><?= e($request['tema_preliminar'] ?? '—') ?></td><td><?= e($request['habilitado_en']) ?></td>
                <td><form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/inscripciones.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_solicitud" value="<?= (int) $request['id_solicitud'] ?>">
                    <label>Cohorte<select class="form-select" name="id_cohorte" required><option value="">Seleccione cohorte</option><?php foreach ($cohorts as $cohort): ?><option value="<?= (int) $cohort['id_cohorte'] ?>"><?= e($cohort['codigo'] . ' · ' . $cohort['nombre']) ?></option><?php endforeach; ?></select></label>
                    <label>Trabajo<select class="form-select" name="tipo_asignacion" required><option value="individual" <?= $request['tipo_trabajo'] === 'individual' ? 'selected' : '' ?>>Nuevo trabajo individual</option><?php if ((int) $request['permite_trabajo_grupal'] === 1 && (int) $request['max_integrantes'] >= 2): ?><option value="nuevo_grupo" <?= $request['tipo_trabajo'] === 'grupal' ? 'selected' : '' ?>>Crear nuevo trabajo grupal</option><option value="grupo_existente">Añadir a grupo compatible</option><?php endif; ?></select></label>
                    <?php if ((int) $request['permite_trabajo_grupal'] === 1 && (int) $request['max_integrantes'] >= 2): ?><label>Grupo existente<select class="form-select" name="id_trabajo_existente"><option value="">No seleccionar</option><?php foreach ($groups as $group): if ((int) $group['id_modalidad'] === (int) $request['id_modalidad'] && (int) $group['id_carrera'] === (int) $request['id_carrera'] && mb_strtolower(trim((string) $group['tema'])) === mb_strtolower(trim((string) $request['tema_preliminar']))): ?><option value="<?= (int) $group['id_trabajo'] ?>"><?= e($group['codigo'] . ' · ' . $group['codigo_cohorte'] . ' · ' . (int) $group['integrantes'] . '/' . (int) $group['max_integrantes']) ?></option><?php endif; endforeach; ?></select></label><?php endif; ?>
                    <label>Observación administrativa<input class="form-control" name="observacion" maxlength="1000"></label><button class="btn btn-primary" type="submit">Crear inscripción</button>
                </form></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">No hay solicitudes habilitadas pendientes de inscripción.</p><?php endif; ?>
    </section>

    <section class="card mg-panel">
        <div class="section-heading"><div><h2>Inscripciones y trabajos</h2><p>Los integrantes de un trabajo grupal se cuentan como estudiantes. La cohorte no tiene un máximo general.</p></div></div>
        <?php if ($registrations): ?><div class="table-wrapper"><table><thead><tr><th>Inscripción</th><th>Estudiante / RU</th><th>Cohorte</th><th>Modalidad</th><th>Trabajo</th><th>Integrantes</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
            <?php foreach ($registrations as $registration): ?><tr><td>#<?= (int) $registration['id_inscripcion'] ?></td><td><?= e($registration['estudiante']) ?><br><small><?= e($registration['registro_universitario'] ?? '—') ?></small></td><td><?= e($registration['codigo_cohorte'] . ' · ' . $registration['cohorte']) ?></td><td><?= e($registration['modalidad']) ?></td><td><?= e($registration['codigo_trabajo']) ?><br><small><?= e($registration['tema'] ?? 'Sin tema') ?> · <?= e($registration['tipo_trabajo']) ?></small></td><td><?= (int) $registration['integrantes'] ?></td><td><?= e($registration['estado']) ?></td><td><?= e($registration['inscrito_en']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">Todavía no hay inscripciones formales.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
