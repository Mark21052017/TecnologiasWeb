<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container profile-page">
    <div class="page-heading profile-heading">
        <div>
            <span class="profile-title-icon"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
            <div><h1>Mi Perfil</h1><p>Administra tus datos personales y la seguridad de tu cuenta.</p></div>
        </div>
        <a class="button secondary profile-back" href="<?= e(app_url('dashboard.php')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</a>
    </div>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <div class="profile-layout">
        <aside class="card profile-summary">
            <div class="profile-photo-frame" data-photo-frame>
                <?php if (!empty($profile['foto_perfil'])): ?>
                    <img class="profile-photo-image" src="<?= e(app_url('mi-perfil/foto.php?v=' . rawurlencode($profile['foto_perfil']))) ?>" alt="Fotografia de <?= e($profile['nombre'] . ' ' . $profile['apellido']) ?>" width="112" height="112" data-photo-preview>
                <?php else: ?>
                    <span data-photo-initials><?= e($initials) ?></span>
                    <img class="profile-photo-image" src="" alt="Vista previa de la fotografia" width="112" height="112" data-photo-preview hidden>
                <?php endif; ?>
            </div>
            <h2><?= e($profile['nombre'] . ' ' . $profile['apellido']) ?></h2>
            <span class="profile-role"><?= e($role === 'tutor' ? 'Docente tutor' : 'Estudiante') ?></span>
            <div class="profile-account-meta">
                <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($profile['usuario']) ?></span>
                <?php if ($memberSince !== ''): ?><span><i class="bi bi-calendar3" aria-hidden="true"></i> Desde <?= e($memberSince) ?></span><?php endif; ?>
            </div>

            <div class="profile-photo-section">
                <h3><i class="bi bi-camera" aria-hidden="true"></i> Foto de perfil</h3>
                <form method="post" action="<?= e(app_url('mi-perfil/')) ?>" enctype="multipart/form-data" data-photo-form>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="photo">
                    <label class="profile-file-control" for="foto_perfil">
                        <i class="bi bi-image" aria-hidden="true"></i>
                        <span data-photo-filename>Elegir fotografia</span>
                    </label>
                    <input class="profile-file-input" id="foto_perfil" name="foto_perfil" type="file" accept="image/jpeg,image/png,image/webp" data-photo-input required>
                    <button class="button profile-upload-button" type="submit"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Subir foto</button>
                </form>
                <small>JPG, PNG o WebP. Maximo 2 MB.</small>
                <?php if (!empty($profile['foto_perfil'])): ?>
                    <form method="post" action="<?= e(app_url('mi-perfil/')) ?>" onsubmit="return confirm('Eliminar la fotografia de perfil?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="remove-photo">
                        <button class="profile-remove-photo" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i> Eliminar foto</button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>

        <section class="card profile-details">
            <form method="post" action="<?= e(app_url('mi-perfil/')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="personal">
                <div class="profile-section-heading"><i class="bi bi-pencil-square" aria-hidden="true"></i><div><h2>Datos personales</h2><p>Información visible en tu cuenta.</p></div></div>
                <div class="form-grid">
                    <div><label for="nombre">Nombre *</label><input id="nombre" name="nombre" type="text" maxlength="100" required value="<?= e($data['nombre']) ?>"></div>
                    <div><label for="apellido">Apellido *</label><input id="apellido" name="apellido" type="text" maxlength="100" required value="<?= e($data['apellido']) ?>"></div>
                    <div><label for="correo">Correo electronico *</label><input id="correo" name="correo" type="email" maxlength="150" required value="<?= e($data['correo']) ?>"></div>
                    <div><label for="telefono">Telefono</label><input id="telefono" name="telefono" type="tel" maxlength="20" inputmode="numeric" value="<?= e($data['telefono']) ?>"></div>
                </div>

                <?php if ($role === 'tutor'): ?>
                    <div class="profile-section-divider"></div>
                    <div class="profile-section-heading"><i class="bi bi-mortarboard" aria-hidden="true"></i><div><h2>Información profesional</h2><p>Estos datos ayudan a los estudiantes a conocerte.</p></div></div>
                    <label for="especialidad">Especialidad *</label>
                    <input id="especialidad" name="especialidad" type="text" maxlength="150" required value="<?= e($data['especialidad']) ?>">
                    <label for="biografia">Biografia profesional</label>
                    <textarea id="biografia" name="biografia" maxlength="2000" rows="5"><?= e($data['biografia']) ?></textarea>
                <?php else: ?>
                    <div class="profile-section-divider"></div>
                    <div class="profile-section-heading"><i class="bi bi-mortarboard" aria-hidden="true"></i><div><h2>Información académica</h2><p>Datos administrados por la universidad.</p></div></div>
                    <div class="form-grid">
                        <div><label for="carrera">Carrera</label><input id="carrera" type="text" readonly value="<?= e($profile['nombre_carrera'] ?? 'Sin carrera asignada') ?>"></div>
                        <div><label for="semestre">Semestre</label><input id="semestre" type="text" readonly value="<?= e($profile['semestre'] ?? '') ?>"></div>
                        <div class="form-full"><label for="registro">Registro universitario</label><input id="registro" type="text" readonly value="<?= e($profile['registro_universitario'] ?? '') ?>"></div>
                    </div>
                <?php endif; ?>

                <div class="profile-form-actions"><button type="submit"><i class="bi bi-check-circle" aria-hidden="true"></i> Guardar cambios</button></div>
            </form>

            <div class="profile-section-divider"></div>
            <form method="post" action="<?= e(app_url('mi-perfil/')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="password">
                <div class="profile-section-heading"><i class="bi bi-shield-lock" aria-hidden="true"></i><div><h2>Cambiar contraseña</h2><p>Confirma tu contraseña actual antes de establecer una nueva.</p></div></div>
                <div class="form-grid profile-password-grid">
                    <div class="form-full"><label for="contrasena_actual">Contraseña actual *</label><input id="contrasena_actual" name="contrasena_actual" type="password" autocomplete="current-password" required></div>
                    <div><label for="contrasena_nueva">Nueva contraseña *</label><input id="contrasena_nueva" name="contrasena_nueva" type="password" minlength="8" autocomplete="new-password" data-password-field required></div>
                    <div><label for="confirmacion">Confirmar contraseña *</label><input id="confirmacion" name="confirmacion" type="password" minlength="8" autocomplete="new-password" data-password-confirmation required></div>
                </div>
                <small>Minimo 8 caracteres, incluyendo una letra y un numero.</small>
                <div class="profile-form-actions"><button type="submit"><i class="bi bi-shield-check" aria-hidden="true"></i> Actualizar contraseña</button></div>
            </form>
        </section>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
