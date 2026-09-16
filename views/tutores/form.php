<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar tutor' : 'Nuevo tutor';
$action = $isEditing ? app_url('tutores/edit.php?id=' . (int) $data['id_tutor']) : app_url('tutores/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container narrow-wide">
    <section class="card">
        <h1><?= e($title) ?></h1>
        <p class="form-intro">Complete el perfil profesional del tutor.</p>
        <?php if (!empty($errors)): ?>
            <div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid">
                <div>
                    <label for="id_usuario">Usuario tutor</label>
                    <select id="id_usuario" name="id_usuario" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($users as $option): ?>
                            <option value="<?= (int) $option['id_usuario'] ?>" <?= (string) ($data['id_usuario'] ?? '') === (string) $option['id_usuario'] ? 'selected' : '' ?>><?= e($option['apellido'] . ', ' . $option['nombre'] . ' - ' . $option['usuario']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$users): ?><small>No hay usuarios tutor disponibles.</small><?php endif; ?>
                </div>
                <div>
                    <label for="especialidad">Especialidad</label>
                    <input id="especialidad" name="especialidad" type="text" maxlength="150" value="<?= e($data['especialidad'] ?? '') ?>">
                </div>
                <div class="form-full">
                    <label for="biografia">Biografia</label>
                    <textarea id="biografia" name="biografia" rows="5" maxlength="2000"><?= e($data['biografia'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit">Guardar</button>
            <a class="button secondary" href="<?= e(app_url('tutores/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
