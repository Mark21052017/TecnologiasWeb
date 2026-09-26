<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar carrera' : 'Nueva carrera';
$action = $isEditing ? app_url('carreras/edit.php?id=' . (int) $data['id_carrera']) : app_url('carreras/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container narrow">
    <section class="card shadow-sm border-0">
        <h1><?= e($title) ?></h1>
        <?php if (!empty($errors)): ?>
            <div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="form-label" for="nombre_carrera">Nombre de la carrera</label>
            <input class="form-control" id="nombre_carrera" name="nombre_carrera" type="text" minlength="2" maxlength="150" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" title="Use solo letras, espacios, apostrofes y guiones." required value="<?= e($data['nombre_carrera'] ?? '') ?>">
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('carreras/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
