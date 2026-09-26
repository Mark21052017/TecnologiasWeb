<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Configuración MG</span><h1>Modalidades de grado</h1><p>Las modalidades utilizadas por expedientes se desactivan; no se eliminan.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/')) ?>">Volver a MG</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="card mg-panel">
        <h2>Agregar modalidad</h2>
        <form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/modalidades.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="accion" value="guardar">
            <label>Código<input class="form-control" name="codigo" required maxlength="60" placeholder="PROYECTO_GRADO"></label>
            <label>Nombre<input class="form-control" name="nombre" required maxlength="150"></label>
            <label class="mg-checkbox"><input type="checkbox" name="requiere_tutor" value="1"> Requiere tutor</label>
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
