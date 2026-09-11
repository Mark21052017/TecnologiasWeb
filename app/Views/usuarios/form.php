<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar usuario' : 'Nuevo usuario';
$action = $isEditing
    ? app_url('usuarios/edit.php?id=' . (int) $data['id_usuario'])
    : app_url('usuarios/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container narrow-wide">
    <section class="card">
        <h1><?= e($title) ?></h1>

        <?php if (!empty($errors)): ?>
            <div class="alert" role="alert">
                <ul>
                    <?php foreach ($errors as $formError): ?>
                        <li><?= e($formError) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e($action) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-grid">
                <div>
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" type="text" maxlength="100" required value="<?= e($data['nombre'] ?? '') ?>">
                </div>
                <div>
                    <label for="apellido">Apellido</label>
                    <input id="apellido" name="apellido" type="text" maxlength="100" required value="<?= e($data['apellido'] ?? '') ?>">
                </div>
                <div>
                    <label for="correo">Correo</label>
                    <input id="correo" name="correo" type="email" maxlength="150" required value="<?= e($data['correo'] ?? '') ?>">
                </div>
                <div>
                    <label for="usuario">Usuario</label>
                    <input id="usuario" name="usuario" type="text" maxlength="50" required value="<?= e($data['usuario'] ?? '') ?>">
                </div>
                <div>
                    <label for="contrasena">Contrasena <?= $isEditing ? '(opcional)' : '' ?></label>
                    <input id="contrasena" name="contrasena" type="password" minlength="6" <?= $isEditing ? '' : 'required' ?> autocomplete="new-password">
                </div>
                <div>
                    <label for="telefono">Telefono</label>
                    <input id="telefono" name="telefono" type="text" maxlength="20" value="<?= e($data['telefono'] ?? '') ?>">
                </div>
                <div>
                    <label for="id_rol">Rol</label>
                    <select id="id_rol" name="id_rol" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['id_rol'] ?>" <?= (string) ($data['id_rol'] ?? '') === (string) $role['id_rol'] ? 'selected' : '' ?>>
                                <?= e($role['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <option value="activo" <?= ($data['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= ($data['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <button type="submit">Guardar</button>
            <a class="button secondary" href="<?= e(app_url('usuarios/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
