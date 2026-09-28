<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Configuración MG</span><h1>Importación académica</h1><p>Los datos solo participan en la verificación cuando Administración aprueba el lote.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/configuracion.php')) ?>">Volver a Configuración</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <section class="card mg-panel">
        <h2>1. Descargue una plantilla</h2>
        <p>Identificadores de carrera y materia corresponden a los catálogos del sistema. El historial requiere que el plan y su versión ya estén aprobados.</p>
        <div class="mg-actions">
            <a class="btn btn-outline-primary" href="<?= e(app_url('modalidades-grado/academico.php?plantilla=plan_estudio')) ?>">Plantilla del plan de estudios</a>
            <a class="btn btn-outline-primary" href="<?= e(app_url('modalidades-grado/academico.php?plantilla=historial')) ?>">Plantilla del historial académico</a>
        </div>
        <p class="form-hint">CSV UTF-8, separado por comas; máximo 5 MB y 10.000 filas. Las filas de ejemplo deben reemplazarse por datos reales.</p>
    </section>

    <section class="card mg-panel">
        <h2>2. Cargue y revise el archivo</h2>
        <form class="mg-form-grid" method="post" enctype="multipart/form-data" action="<?= e(app_url('modalidades-grado/academico.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="5000000">
            <label>Tipo de datos<select class="form-select" name="tipo" required><option value="plan_estudio">Plan de estudios por carrera y versión</option><option value="historial">Historial académico de estudiantes</option></select></label>
            <label>Archivo CSV<input class="form-control" type="file" name="archivo" accept=".csv,text/csv" required></label>
            <button class="btn btn-primary" type="submit">Importar para revisión</button>
        </form>
        <p class="form-hint">La misma cuenta administradora puede importar y aprobar luego de revisar la vista previa. Toda decisión conserva quién y cuándo.</p>
    </section>

    <section class="card mg-panel">
        <div class="section-heading"><div><h2>Importaciones recientes</h2><p>Los lotes rechazados se conservan como historial y no modifican los datos aprobados.</p></div></div>
        <?php if ($imports): ?><div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Tipo</th><th>Archivo</th><th>Filas</th><th>Estado</th><th>Importó / revisó</th><th>Revisión</th></tr></thead><tbody>
            <?php foreach ($imports as $import): ?><tr>
                <td><?= e($import['fecha_importacion']) ?></td><td><?= $import['tipo'] === 'plan_estudio' ? 'Plan' : 'Historial' ?></td><td><?= e($import['nombre_archivo']) ?></td>
                <td><?= (int) $import['filas_validas'] ?> válidas / <?= (int) $import['filas_con_error'] ?> con error</td>
                <td><span class="status <?= $import['estado'] === 'aprobada' ? 'status-activo' : ($import['estado'] === 'pendiente' ? '' : 'status-inactivo') ?>"><?= e($import['estado']) ?></span></td>
                <td><?= e($import['importador']) ?><br><small><?= e($import['revisor'] ?? 'Aún sin revisar') ?></small></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/academico.php?id=' . (int) $import['id_importacion'])) ?>">Ver filas</a></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">Todavía no hay archivos importados.</p><?php endif; ?>
    </section>

    <?php if ($selectedRows): ?>
        <section class="card mg-panel">
            <h2>Vista previa de importación #<?= $selectedId ?></h2>
            <div class="table-wrapper"><table><thead><tr><th>Fila CSV</th><th>Datos</th><th>Validación</th></tr></thead><tbody>
                <?php foreach ($selectedRows as $row): ?><tr><td><?= (int) $row['numero_fila'] ?></td><td><code><?= e(json_encode(json_decode($row['datos'], true), JSON_UNESCAPED_UNICODE)) ?></code></td><td><?= e($row['error_validacion'] ?? 'Válida') ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <p class="form-hint">Se muestran como máximo 100 filas. La importación completa solo se aprueba si todas las filas son válidas.</p>
            <?php foreach ($imports as $import): if ((int) $import['id_importacion'] === $selectedId && $import['estado'] === 'pendiente'): ?>
                <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/academico.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="revisar"><input type="hidden" name="id_importacion" value="<?= $selectedId ?>">
                    <label>Observación<textarea class="form-control" name="observacion" rows="2" maxlength="1000" placeholder="Obligatoria al rechazar"></textarea></label>
                    <div class="mg-actions"><button class="btn btn-primary" type="submit" name="decision" value="aprobada" <?= (int) $import['filas_con_error'] > 0 || (int) $import['filas_validas'] === 0 ? 'disabled' : '' ?>>Aprobar e incorporar</button><button class="btn btn-outline-danger" type="submit" name="decision" value="rechazada">Rechazar lote</button></div>
                </form>
            <?php endif; endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
