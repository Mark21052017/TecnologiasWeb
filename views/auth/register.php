<?php
$title = 'Crear cuenta de estudiante';
require __DIR__ . '/../layouts/header.php';
?>

<main class="auth-container auth-register-container">
    <section class="auth-intro">
        <div><img class="brand-logo auth-brand-logo" src="<?= e(app_url('img/logo2.png')) ?>" alt="Universidad Privada Domingo Savio"></div>
        <div>
            <span class="hero-kicker">Registro estudiantil</span>
            <h1>Tu apoyo academico comienza aqui.</h1>
            <p>Completa tus datos para solicitar tutorias despues de la aprobacion administrativa.</p>
        </div>
        <ul class="auth-points">
            <li>Tu cuenta se crea con rol estudiante.</li>
            <li>Un administrador revisara tu informacion.</li>
        </ul>
    </section>

    <section class="auth-form-panel auth-register-panel">
        <div class="card shadow-sm border-0">
            <span class="eyebrow">Cuenta de estudiante</span>
            <h2>Crear cuenta</h2>
            <p>Los campos academicos se guardan junto con tu perfil. El registro universitario se generara automaticamente.</p>
            <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form method="post" action="<?= e(app_url('register.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-grid">
                    <div><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" type="text" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" title="Use solo letras, espacios y guiones." required value="<?= e($data['nombre']) ?>"></div>
                    <div><label class="form-label" for="apellido">Apellido</label><input class="form-control" id="apellido" name="apellido" type="text" maxlength="100" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+([ '-][A-Za-zÁÉÍÓÚáéíóúÑñÜüÀ-ÿ]+)*" title="Use solo letras, espacios y guiones." required value="<?= e($data['apellido']) ?>"></div>
                    <div><label class="form-label" for="correo">Correo</label><input class="form-control" id="correo" name="correo" type="email" maxlength="150" required value="<?= e($data['correo']) ?>"></div>
                    <div><label class="form-label" for="telefono">Telefono</label><input class="form-control" id="telefono" name="telefono" type="tel" inputmode="numeric" pattern="[0-9]{7,20}" maxlength="20" title="Ingrese entre 7 y 20 numeros." value="<?= e($data['telefono']) ?>"></div>
                    <div><label class="form-label" for="usuario">Usuario</label><input class="form-control" id="usuario" name="usuario" type="text" minlength="4" maxlength="50" pattern="(?=.*[A-Za-z])[A-Za-z0-9._-]{4,50}" title="Use entre 4 y 50 caracteres, incluyendo al menos una letra. Puede usar numeros, punto, guion o guion bajo." required autocomplete="username" value="<?= e($data['usuario']) ?>"></div>
                    <div><label class="form-label" for="id_carrera">Carrera</label><select class="form-select" id="id_carrera" name="id_carrera" required><option value="">Seleccione</option><?php foreach ($careers as $career): ?><option value="<?= (int) $career['id_carrera'] ?>" <?= (string) $data['id_carrera'] === (string) $career['id_carrera'] ? 'selected' : '' ?>><?= e($career['nombre_carrera']) ?></option><?php endforeach; ?></select></div>
                    <div><label class="form-label" for="semestre">Semestre</label><input class="form-control" id="semestre" name="semestre" type="number" min="1" max="20" required value="<?= e($data['semestre']) ?>"></div>
                    <div><label class="form-label" for="contrasena">Contrasena</label><input class="form-control" id="contrasena" name="contrasena" type="password" minlength="6" required autocomplete="new-password" data-password-field></div>
                    <div><label class="form-label" for="confirmacion">Confirmar contrasena</label><input class="form-control" id="confirmacion" name="confirmacion" type="password" minlength="6" required autocomplete="new-password" data-password-confirmation></div>
                </div>
                <button class="btn btn-primary" type="submit">Enviar registro</button>
                <a class="button secondary btn btn-outline-secondary" href="<?= e(app_url('login.php')) ?>">Volver al login</a>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
