<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar materia' : 'Nueva materia';
$action = $isEditing ? app_url('materias/edit.php?id=' . (int) $data['id_materia']) : app_url('materias/create.php');
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
            <label class="form-label" for="nombre_materia">Nombre de la materia</label>
            <input class="form-control" id="nombre_materia" name="nombre_materia" type="text" minlength="2" maxlength="150" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" title="Use solo letras, espacios, apostrofes y guiones." required value="<?= e($data['nombre_materia'] ?? '') ?>">
            <label class="form-label" for="id_carrera">Carrera</label>
            <select class="form-select" id="id_carrera" name="id_carrera">
                <option value="">Sin carrera</option>
                <?php foreach ($carreras as $career): ?>
                    <option value="<?= (int) $career['id_carrera'] ?>" <?= (string) ($data['id_carrera'] ?? '') === (string) $career['id_carrera'] ? 'selected' : '' ?>>
                        <?= e($career['nombre_carrera']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('materias/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
