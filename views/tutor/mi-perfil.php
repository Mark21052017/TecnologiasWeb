<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container narrow-wide">
    <section class="card"><div class="page-heading"><div><h1>Mi perfil de tutor</h1><p>Actualiza la información profesional que verán los estudiantes.</p></div><span class="status status-<?= e($profile['estado']) ?>"><?= e($profile['estado']) ?></span></div>
        <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
        <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <p><strong><?= e($profile['nombre'] . ' ' . $profile['apellido']) ?></strong><br><?= e($profile['correo']) ?></p>
        <form method="post" action="<?= e(app_url('mi-perfil-tutor/')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label for="especialidad">Especialidad</label><input id="especialidad" name="especialidad" type="text" maxlength="150" required value="<?= e($data['especialidad']) ?>"><label for="biografia">Biografia profesional</label><textarea id="biografia" name="biografia" maxlength="2000" rows="6"><?= e($data['biografia']) ?></textarea><button type="submit">Guardar cambios</button></form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
