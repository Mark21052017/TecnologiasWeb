<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Configuración MG</span><h1>Modalidades de grado</h1><p>Las modalidades utilizadas por expedientes se desactivan; no se eliminan.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/configuracion.php')) ?>">Volver a Configuración</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="card mg-panel">
        <h2>Agregar modalidad</h2>
        <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/modalidades.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="guardar">
            <label>Código<input class="form-control" name="codigo" required maxlength="60" placeholder="PROYECTO_GRADO"></label>
            <label>Nombre<input class="form-control" name="nombre" required maxlength="150"></label>
            <label>Descripción<textarea class="form-control" name="descripcion" maxlength="1000" rows="2"></textarea></label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_tutor" value="1"> Requiere tutor</label>
            <details class="mg-advanced-rules"><summary>Reglas avanzadas</summary>
            <label class="mg-checkbox"><input type="checkbox" name="permite_trabajo_grupal" value="1"> Permite intención de trabajo grupal</label>
            <label>Máximo integrantes<input class="form-control" type="number" name="max_integrantes" min="2" max="20" placeholder="Requerido si permite grupo"></label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_tema_preliminar" value="1" checked> Requiere tema preliminar</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_descripcion" value="1" checked> Requiere descripción</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_informes" value="1"> Requiere informes de avance</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_asistencia" value="1"> Controlar asistencia</label>
            <label>Asistencia mínima (%)<input class="form-control" type="number" name="asistencia_minima_pct" min="0" max="100" step="0.01" placeholder="Opcional"></label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_mdg1" value="1"> Requiere MDG I</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_mdg2" value="1"> Requiere MDG II</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_informe_final" value="1"> Requiere informe final</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_tribunal" value="1"> Requiere tribunal</label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_defensa" value="1"> Requiere defensa</label>
            <label>Máximo de defensas<input class="form-control" type="number" name="max_defensas" min="1" max="20" placeholder="Sin límite configurado"></label>
            <label>Avance requerido para defensa (%)<input class="form-control" type="number" name="avance_requerido_defensa" min="0" max="100" step="0.01"></label>
            <label>Mínimo de miembros del tribunal<input class="form-control" type="number" name="miembros_minimos_tribunal" min="2" max="20"></label>
            <label class="mg-checkbox"><input type="hidden" name="impide_tutor_tribunal" value="0"><input type="checkbox" name="impide_tutor_tribunal" value="1" checked> Impedir que el tutor integre su tribunal</label>
            </details>
            <button class="btn btn-primary" type="submit">Agregar modalidad</button>
        </form>
    </section>
    <section class="card mg-panel">
        <div class="section-heading"><div><h2>Catálogo</h2><p>Se puede ajustar el nombre, código y requerimiento de tutor.</p></div></div>
        <?php if ($modalities): ?><div class="table-wrapper"><table><thead><tr><th>Código</th><th>Nombre</th><th>Tutor requerido</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach ($modalities as $modality): ?><tr>
                <td colspan="4">
                    <form class="mg-modality-edit" method="post" action="<?= e(app_url('modalidades-grado/modalidades.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id_modalidad" value="<?= (int) $modality['id_modalidad'] ?>">
                        <input class="form-control" aria-label="Código" name="codigo" required maxlength="60" value="<?= e($modality['codigo']) ?>">
                        <input class="form-control" aria-label="Nombre" name="nombre" required maxlength="150" value="<?= e($modality['nombre']) ?>">
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_tutor" value="1" <?= (int) $modality['requiere_tutor'] === 1 ? 'checked' : '' ?>> Sí</label>
                        <textarea class="form-control" aria-label="Descripción" name="descripcion" maxlength="1000" rows="1" placeholder="Descripción"><?= e($modality['descripcion'] ?? '') ?></textarea>
                        <details class="mg-advanced-rules"><summary>Reglas avanzadas</summary>
                        <label class="mg-checkbox"><input type="checkbox" name="permite_trabajo_grupal" value="1" <?= (int) $modality['permite_trabajo_grupal'] === 1 ? 'checked' : '' ?>> Permite grupo</label>
                        <input class="form-control" aria-label="Máximo integrantes" type="number" name="max_integrantes" min="2" max="20" value="<?= e($modality['max_integrantes'] ?? '') ?>" placeholder="Máx. grupo">
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_tema_preliminar" value="1" <?= (int) $modality['requiere_tema_preliminar'] === 1 ? 'checked' : '' ?>> Tema</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_descripcion" value="1" <?= (int) $modality['requiere_descripcion'] === 1 ? 'checked' : '' ?>> Descripción</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_informes" value="1" <?= (int) $modality['requiere_informes'] === 1 ? 'checked' : '' ?>> Informes</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_asistencia" value="1" <?= (int) $modality['requiere_asistencia'] === 1 ? 'checked' : '' ?>> Asistencia</label>
                        <input class="form-control" aria-label="Asistencia mínima porcentual" type="number" name="asistencia_minima_pct" min="0" max="100" step="0.01" value="<?= e($modality['asistencia_minima_pct'] ?? '') ?>" placeholder="Mín. asistencia %">
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_mdg1" value="1" <?= (int) $modality['requiere_mdg1'] === 1 ? 'checked' : '' ?>> MDG I</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_mdg2" value="1" <?= (int) $modality['requiere_mdg2'] === 1 ? 'checked' : '' ?>> MDG II</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_informe_final" value="1" <?= (int) $modality['requiere_informe_final'] === 1 ? 'checked' : '' ?>> Informe final</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_tribunal" value="1" <?= (int) $modality['requiere_tribunal'] === 1 ? 'checked' : '' ?>> Tribunal</label>
                        <label class="mg-checkbox"><input type="checkbox" name="requiere_defensa" value="1" <?= (int) $modality['requiere_defensa'] === 1 ? 'checked' : '' ?>> Defensa</label>
                        <input class="form-control" aria-label="Máximo de defensas" type="number" name="max_defensas" min="1" max="20" value="<?= e($modality['max_defensas'] ?? '') ?>" placeholder="Máx. defensas">
                        <input class="form-control" aria-label="Avance requerido para defensa" type="number" name="avance_requerido_defensa" min="0" max="100" step="0.01" value="<?= e($modality['avance_requerido_defensa'] ?? '') ?>" placeholder="Avance % defensa">
                        <input class="form-control" aria-label="Miembros mínimos del tribunal" type="number" name="miembros_minimos_tribunal" min="2" max="20" value="<?= e($modality['miembros_minimos_tribunal'] ?? '') ?>" placeholder="Miembros tribunal">
                        <label class="mg-checkbox"><input type="hidden" name="impide_tutor_tribunal" value="0"><input type="checkbox" name="impide_tutor_tribunal" value="1" <?= (int) $modality['impide_tutor_tribunal'] === 1 ? 'checked' : '' ?>> Tutor fuera tribunal</label>
                        </details>
                        <span class="status <?= $modality['estado'] === 'activa' ? 'status-activo' : 'status-inactivo' ?>"><?= e($modality['estado']) ?></span>
                        <button class="btn btn-sm btn-outline-primary" type="submit">Guardar</button>
                    </form>
                </td>
                <td><form method="post" action="<?= e(app_url('modalidades-grado/modalidades.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_modalidad" value="<?= (int) $modality['id_modalidad'] ?>"><input type="hidden" name="estado" value="<?= $modality['estado'] === 'activa' ? 'inactiva' : 'activa' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= $modality['estado'] === 'activa' ? 'Desactivar' : 'Activar' ?></button></form></td>
            </tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">No hay modalidades registradas.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
