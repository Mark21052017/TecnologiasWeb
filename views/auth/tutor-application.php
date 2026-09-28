<?php
$title = 'Postular como tutor';
require __DIR__ . '/../layouts/header.php';
?>

<main class="auth-container auth-register-container">
    <section class="auth-intro">
        <div><img class="brand-logo auth-brand-logo" src="<?= e(app_url('img/logo2.png')) ?>" alt="Universidad Privada Domingo Savio"></div>
        <div>
            <span class="hero-kicker">Comunidad de tutores</span>
            <h1>Comparte lo que sabes.</h1>
            <p>Envía tus datos y espera a que un administrador active tu cuenta desde Cuentas de acceso.</p>
        </div>
        <ul class="auth-points">
            <li>Tu cuenta tendra rol tutor.</li>
            <li>Podrás iniciar sesión cuando tu cuenta sea aprobada.</li>
        </ul>
    </section>

    <section class="auth-form-panel auth-register-panel">
        <div class="card shadow-sm border-0">
            <span class="eyebrow">Postulacion de tutor</span>
            <h2>Crear postulacion</h2>
            <p>Tu cuenta quedará pendiente hasta que el administrador la active en Cuentas de acceso.</p>
            <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="<?= e(app_url('postular-tutor.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-grid">
                    <div><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" type="text" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" required value="<?= e($data['nombre']) ?>"></div>
                    <div><label class="form-label" for="apellido">Apellido</label><input class="form-control" id="apellido" name="apellido" type="text" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" required value="<?= e($data['apellido']) ?>"></div>
                    <div><label class="form-label" for="correo">Correo</label><input class="form-control" id="correo" name="correo" type="email" maxlength="150" required value="<?= e($data['correo']) ?>"></div>
                    <div><label class="form-label" for="telefono">Telefono</label><input class="form-control" id="telefono" name="telefono" type="tel" inputmode="numeric" pattern="[0-9]{7,20}" maxlength="20" value="<?= e($data['telefono']) ?>"></div>
                    <div><label class="form-label" for="usuario">Usuario</label><input class="form-control" id="usuario" name="usuario" type="text" minlength="4" maxlength="50" pattern="(?=.*[A-Za-z])[A-Za-z0-9._-]{4,50}" title="Use entre 4 y 50 caracteres, incluyendo al menos una letra. Puede usar numeros, punto, guion o guion bajo." required value="<?= e($data['usuario']) ?>"></div>
                    <div><label class="form-label" for="especialidad">Especialidad</label><input class="form-control" id="especialidad" name="especialidad" type="text" maxlength="150" required value="<?= e($data['especialidad']) ?>"></div>
                    <div><label class="form-label" for="contrasena">Contrasena</label><input class="form-control" id="contrasena" name="contrasena" type="password" minlength="6" required data-password-field></div>
                    <div><label class="form-label" for="confirmacion">Confirmar contrasena</label><input class="form-control" id="confirmacion" name="confirmacion" type="password" minlength="6" required data-password-confirmation></div>
                    <div class="form-full"><label class="form-label" for="biografia">Biografia profesional</label><textarea class="form-control" id="biografia" name="biografia" rows="5" maxlength="2000"><?= e($data['biografia']) ?></textarea></div>
                </div>
                <button class="btn btn-primary" type="submit">Enviar postulacion</button>
                <a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('login.php')) ?>">Volver al login</a>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
