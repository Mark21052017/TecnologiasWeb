<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar estudiante' : 'Nuevo estudiante';
$action = $isEditing ? app_url('estudiantes/edit.php?id=' . (int) $data['id_estudiante']) : app_url('estudiantes/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container">
    <section class="card shadow-sm border-0">
        <h1><?= e($title) ?></h1>
        <p class="form-intro">Complete los datos academicos del perfil. El registro universitario se genera automaticamente.</p>
        <?php if (!empty($errors)): ?>
            <div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid">
                <div>
                    <label class="form-label" for="id_usuario">Usuario estudiante</label>
                    <select class="form-select" id="id_usuario" name="id_usuario" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($options['users'] as $option): ?>
                            <option value="<?= (int) $option['id_usuario'] ?>" <?= (string) ($data['id_usuario'] ?? '') === (string) $option['id_usuario'] ? 'selected' : '' ?>><?= e($option['apellido'] . ', ' . $option['nombre'] . ' - ' . $option['usuario']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$options['users']): ?><small>No hay usuarios estudiante disponibles.</small><?php endif; ?>
                </div>
                <div>
                    <label class="form-label" for="id_carrera">Carrera</label>
                    <select class="form-select" id="id_carrera" name="id_carrera" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($options['careers'] as $career): ?>
                            <option value="<?= (int) $career['id_carrera'] ?>" <?= (string) ($data['id_carrera'] ?? '') === (string) $career['id_carrera'] ? 'selected' : '' ?>><?= e($career['nombre_carrera']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="semestre">Semestre</label>
                    <input class="form-control" id="semestre" name="semestre" type="number" min="1" max="20" required value="<?= e($data['semestre'] ?? '') ?>">
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('estudiantes/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
