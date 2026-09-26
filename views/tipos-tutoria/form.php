<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar tipo de tutoría' : 'Nuevo tipo de tutoría';
$action = $isEditing ? app_url('tipos-tutoria/edit.php?id=' . (int) $data['id_tipo_tutoria']) : app_url('tipos-tutoria/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container narrow">
    <section class="card shadow-sm border-0">
        <h1><?= e($title) ?></h1>
        <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="form-label" for="nombre">Nombre</label>
            <input class="form-control" id="nombre" name="nombre" type="text" maxlength="100" required value="<?= e($data['nombre'] ?? '') ?>">
            <label class="form-label" for="descripcion">Descripcion</label>
            <textarea class="form-control" id="descripcion" name="descripcion" maxlength="500" rows="4"><?= e($data['descripcion'] ?? '') ?></textarea>
            <?php if ($isEditing): ?><label class="form-label" for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="activo" <?= ($data['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= ($data['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select><?php endif; ?>
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('tipos-tutoria/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
