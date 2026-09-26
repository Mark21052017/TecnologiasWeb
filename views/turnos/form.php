<?php
$isEditing = ($mode ?? 'create') === 'edit';
require __DIR__ . '/../layouts/header.php';
?>
<main class="container narrow"><section class="card"><h1><?= $isEditing ? 'Editar turno' : 'Nuevo turno' ?></h1>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= e($isEditing ? app_url('turnos/edit.php?id=' . (int) $data['id_turno']) : app_url('turnos/create.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid">
        <div class="form-full"><label for="nombre_turno">Nombre del turno</label><input class="form-control" id="nombre_turno" name="nombre_turno" maxlength="80" required value="<?= e($data['nombre_turno']) ?>"></div>
        <div><label for="hora_inicio">Hora inicial</label><input class="form-control" id="hora_inicio" name="hora_inicio" type="time" required value="<?= e($data['hora_inicio']) ?>"></div>
        <div><label for="hora_fin">Hora final</label><input class="form-control" id="hora_fin" name="hora_fin" type="time" required value="<?= e($data['hora_fin']) ?>"></div>
        <div><label for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="activo" <?= ($data['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= ($data['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
    </div><?php if ($isEditing): ?><p class="form-hint">Si este turno ya se usa en una oferta, solo puede cambiar su nombre o estado. Para modificar las horas, cree un turno nuevo.</p><?php endif; ?><button class="btn btn-primary" type="submit">Guardar</button><a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('turnos/')) ?>">Cancelar</a></form>
</section></main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
