<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar rol' : 'Nuevo rol';
$action = $isEditing ? app_url('roles/edit.php?id=' . (int) $data['id_rol']) : app_url('roles/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container narrow">
    <section class="card">
        <h1><?= e($title) ?></h1>
        <?php if (!empty($errors)): ?>
            <div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="nombre_rol">Nombre del rol</label>
            <input id="nombre_rol" name="nombre_rol" type="text" minlength="2" maxlength="30" required value="<?= e($data['nombre_rol'] ?? '') ?>">
            <button type="submit">Guardar</button>
            <a class="button secondary" href="<?= e(app_url('roles/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
