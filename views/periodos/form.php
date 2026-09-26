<?php
$isEditing = ($mode ?? 'create') === 'edit';
$lockedType = $isEditing && ((int) ($data['total_ofertas'] ?? 0) > 0);
require __DIR__ . '/../layouts/header.php';
?>
<main class="container narrow"><section class="card"><h1><?= $isEditing ? 'Editar periodo' : 'Nuevo periodo' ?></h1>
    <p class="form-intro">Cada periodo pertenece a un tipo de tutoria y define su rango general. Las ofertas academicas heredan el tipo y configuran su frecuencia, turnos y calendario.</p>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= e($isEditing ? app_url('periodos/edit.php?id=' . (int) $data['id_periodo']) : app_url('periodos/create.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <div class="form-full"><label for="nombre_periodo">Nombre del periodo</label><input class="form-control" id="nombre_periodo" name="nombre_periodo" maxlength="100" required value="<?= e($data['nombre_periodo'] ?? '') ?>"></div>
            <div class="form-full">
                <label for="id_tipo_tutoria">Tipo de tutoria</label>
                <?php if ($lockedType): ?>
                    <input type="hidden" name="id_tipo_tutoria" value="<?= (int) ($data['id_tipo_tutoria'] ?? 0) ?>">
                    <select class="form-select" id="id_tipo_tutoria" disabled><option selected><?= e($data['nombre_tipo_tutoria'] ?? '') ?></option></select>
                    <small class="form-hint">No se puede cambiar porque el periodo ya tiene ofertas asociadas.</small>
                <?php else: ?>
                    <select class="form-select" id="id_tipo_tutoria" name="id_tipo_tutoria" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($options['tipos'] as $type): if ($type['estado'] === 'inactivo') { continue; } ?><option value="<?= (int) $type['id_tipo_tutoria'] ?>" <?= (int) ($data['id_tipo_tutoria'] ?? 0) === (int) $type['id_tipo_tutoria'] ? 'selected' : '' ?>><?= e($type['nombre']) ?></option><?php endforeach; ?>
                    </select>
                    <small class="form-hint">Las ofertas de este periodo heredan automaticamente este tipo.</small>
                <?php endif; ?>
            </div>
            <div><label for="fecha_inicio">Inicio del periodo</label><input class="form-control" id="fecha_inicio" name="fecha_inicio" type="date" required value="<?= e($data['fecha_inicio'] ?? '') ?>"></div>
            <div><label for="fecha_fin">Fin del periodo</label><input class="form-control" id="fecha_fin" name="fecha_fin" type="date" required value="<?= e($data['fecha_fin'] ?? '') ?>"></div>
            <div><label for="inscripcion_inicio">Inicio de inscripciones</label><input class="form-control" id="inscripcion_inicio" name="inscripcion_inicio" type="date" required value="<?= e($data['inscripcion_inicio'] ?? '') ?>"></div>
            <div><label for="inscripcion_fin">Fin de inscripciones</label><input class="form-control" id="inscripcion_fin" name="inscripcion_fin" type="date" required value="<?= e($data['inscripcion_fin'] ?? '') ?>"></div>
            <div><label for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="borrador" <?= ($data['estado'] ?? '') === 'borrador' ? 'selected' : '' ?>>Borrador</option><option value="publicado" <?= ($data['estado'] ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option><option value="cerrado" <?= ($data['estado'] ?? '') === 'cerrado' ? 'selected' : '' ?>>Cerrado</option><option value="finalizado" <?= ($data['estado'] ?? '') === 'finalizado' ? 'selected' : '' ?>>Finalizado</option></select></div>
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button><a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('periodos/')) ?>">Cancelar</a>
    </form>
</section></main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
