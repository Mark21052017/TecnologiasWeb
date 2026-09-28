<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar cuenta de acceso' : 'Nueva cuenta de acceso';
$returnQuery = '&return_to=' . rawurlencode((string) ($returnTo ?? 'usuarios'));
if (($returnTo ?? 'usuarios') === 'usuarios' && !empty($returnRoleFilter)) {
    $returnQuery .= '&return_role=' . rawurlencode((string) $returnRoleFilter);
}
$action = $isEditing
    ? app_url('usuarios/edit.php?id=' . (int) $data['id_usuario'] . $returnQuery)
    : app_url('usuarios/create.php');
$selectedRoleName = '';
foreach ($roles as $roleOption) {
    if ((string) ($data['id_rol'] ?? '') === (string) $roleOption['id_rol']) {
        $selectedRoleName = (string) $roleOption['nombre_rol'];
        break;
    }
}
require __DIR__ . '/../layouts/header.php';
?>

<main class="container">
    <section class="card shadow-sm border-0">
        <h1><?= e($title) ?></h1>
        <p class="form-intro">El rol determina el perfil requerido. Al crear una cuenta de estudiante o tutor, sus datos de perfil se guardan junto con la cuenta.</p>
        <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>

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
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control" id="nombre" name="nombre" type="text" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" title="Use solo letras, espacios y guiones." required value="<?= e($data['nombre'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label" for="apellido">Apellido</label>
                    <input class="form-control" id="apellido" name="apellido" type="text" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" title="Use solo letras, espacios y guiones." required value="<?= e($data['apellido'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label" for="correo">Correo</label>
                    <input class="form-control" id="correo" name="correo" type="email" maxlength="150" required value="<?= e($data['correo'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label" for="usuario">Usuario</label>
                    <input class="form-control" id="usuario" name="usuario" type="text" minlength="4" maxlength="50" pattern="(?=.*[A-Za-z])[A-Za-z0-9._-]{4,50}" title="Use entre 4 y 50 caracteres, incluyendo al menos una letra. Puede usar numeros, punto, guion o guion bajo." required value="<?= e($data['usuario'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label" for="contrasena">Contrasena <?= $isEditing ? '(opcional)' : '' ?></label>
                    <input class="form-control" id="contrasena" name="contrasena" type="password" minlength="6" <?= $isEditing ? '' : 'required' ?> autocomplete="new-password" data-password-field>
                </div>
                <div>
                    <label class="form-label" for="confirmacion">Confirmar contrasena <?= $isEditing ? '(opcional)' : '' ?></label>
                    <input class="form-control" id="confirmacion" name="confirmacion" type="password" minlength="6" <?= $isEditing ? '' : 'required' ?> autocomplete="new-password" data-password-confirmation>
                </div>
                <div>
                    <label class="form-label" for="telefono">Telefono</label>
                    <input class="form-control" id="telefono" name="telefono" type="tel" inputmode="numeric" pattern="[0-9]{7,20}" maxlength="20" title="Ingrese entre 7 y 20 numeros." value="<?= e($data['telefono'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label" for="id_rol">Rol</label>
                    <select class="form-select" id="id_rol" name="id_rol" required data-account-role>
                        <option value="">Seleccione</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['id_rol'] ?>" data-role-name="<?= e($role['nombre_rol']) ?>" <?= (string) ($data['id_rol'] ?? '') === (string) $role['id_rol'] ? 'selected' : '' ?>>
                                <?= e($role['nombre_rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <fieldset class="account-profile-section form-full" data-account-profile-section="estudiante" <?= ($selectedRoleName ?? '') === 'estudiante' ? '' : 'hidden' ?>>
                    <legend>Perfil de estudiante</legend>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="id_carrera">Carrera</label>
                            <select class="form-select" id="id_carrera" name="id_carrera" data-account-profile-field data-account-profile-required="true" <?= ($selectedRoleName ?? '') === 'estudiante' ? 'required' : 'disabled' ?>>
                                <option value="">Seleccione</option>
                                <?php foreach ($careers as $career): ?><option value="<?= (int) $career['id_carrera'] ?>" <?= (string) ($data['id_carrera'] ?? '') === (string) $career['id_carrera'] ? 'selected' : '' ?>><?= e($career['nombre_carrera']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="semestre">Semestre</label>
                            <input class="form-control" id="semestre" name="semestre" type="number" min="1" max="20" step="1" value="<?= e((string) ($data['semestre'] ?? '')) ?>" data-account-profile-field data-account-profile-required="true" <?= ($selectedRoleName ?? '') === 'estudiante' ? 'required' : 'disabled' ?>>
                        </div>
                    </div>
                    <?php if (!empty($data['registro_universitario'])): ?><small class="form-hint">Registro universitario: <?= e($data['registro_universitario']) ?> (no editable)</small><?php endif; ?>
                </fieldset>
                <fieldset class="account-profile-section form-full" data-account-profile-section="tutor" <?= ($selectedRoleName ?? '') === 'tutor' ? '' : 'hidden' ?>>
                    <legend>Perfil de tutor</legend>
                    <div class="form-grid">
                        <div><label class="form-label" for="especialidad">Especialidad</label><input class="form-control" id="especialidad" name="especialidad" type="text" maxlength="150" value="<?= e($data['especialidad'] ?? '') ?>" data-account-profile-field <?= ($selectedRoleName ?? '') === 'tutor' ? '' : 'disabled' ?>></div>
                        <div class="form-full"><label class="form-label" for="biografia">Biografía</label><textarea class="form-control" id="biografia" name="biografia" rows="3" maxlength="2000" data-account-profile-field <?= ($selectedRoleName ?? '') === 'tutor' ? '' : 'disabled' ?>><?= e($data['biografia'] ?? '') ?></textarea></div>
                    </div>
                </fieldset>
                <div>
                    <label class="form-label" for="estado">Estado</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <option value="pendiente" <?= ($data['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="activo" <?= ($data['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= ($data['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" type="submit">Guardar</button>
            <a class="button secondary btn btn-outline-secondary" href="<?= e($returnUrl ?? app_url('usuarios/')) ?>">Cancelar</a>
        </form>
        <?php if ($isEditing && !empty($canManagePhoto)): ?>
            <div class="profile-photo-section account-photo-editor">
                <h3><i class="bi bi-camera" aria-hidden="true"></i> Foto de perfil</h3>
                <div class="profile-photo-frame" data-photo-frame>
                    <?php if (!empty($account['foto_perfil'])): ?>
                        <img class="profile-photo-image" src="<?= e(app_url('mi-perfil/foto.php?id=' . (int) $account['id_usuario'] . '&v=' . rawurlencode($account['foto_perfil']))) ?>" alt="Fotografía de <?= e($account['nombre'] . ' ' . $account['apellido']) ?>" width="112" height="112" data-photo-preview>
                    <?php else: ?>
                        <span data-photo-initials><?= e(strtoupper(substr((string) $account['nombre'], 0, 1) . substr((string) $account['apellido'], 0, 1))) ?></span>
                        <img class="profile-photo-image" src="" alt="Vista previa de la fotografía" width="112" height="112" data-photo-preview hidden>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-photo-form>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="photo">
                    <label class="profile-file-control" for="foto_perfil"><i class="bi bi-image" aria-hidden="true"></i><span data-photo-filename>Elegir fotografía</span></label>
                    <input class="profile-file-input" id="foto_perfil" name="foto_perfil" type="file" accept="image/jpeg,image/png,image/webp" data-photo-input required>
                    <button class="button profile-upload-button" type="submit"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> <?= !empty($account['foto_perfil']) ? 'Reemplazar foto' : 'Subir foto' ?></button>
                </form>
                <small>JPG, PNG o WebP. Máximo 2 MB.</small>
                <?php if (!empty($account['foto_perfil'])): ?>
                    <form method="post" action="<?= e($action) ?>" onsubmit="return confirm('¿Eliminar la foto de perfil?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="remove-photo">
                        <button class="profile-remove-photo" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i> Eliminar foto</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
