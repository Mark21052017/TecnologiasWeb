<?php
$title = 'Iniciar sesion';
require __DIR__ . '/../layouts/header.php';
?>

<main class="auth-container">
    <section class="auth-intro">
        <div>
            <img class="brand-logo auth-brand-logo" src="<?= e(app_url('img/logo2.png')) ?>" alt="Universidad Privada Domingo Savio">
        </div>
        <div>
            <span class="hero-kicker">Tutoria academica</span>
            <h1>Aprender mejor, acompañado.</h1>
            <p>Organiza tus sesiones de apoyo, conecta con tutores y sigue tu avance academico desde un solo lugar.</p>
        </div>
        <ul class="auth-points">
            <li>Agenda tutorias segun tu disponibilidad.</li>
            <li>Encuentra apoyo por materia y carrera.</li>
        </ul>
    </section>

    <section class="auth-form-panel">
            <div class="card shadow-sm border-0">
            <span class="eyebrow">Acceso seguro</span>
            <h2>Bienvenido de nuevo</h2>
            <p>Ingresa tus datos para continuar.</p>

            <?php if (!empty($error)): ?>
                <p class="alert" role="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <?php if (!empty($success)): ?><p class="success" role="status"><?= e($success) ?></p><?php endif; ?>

            <form method="post" action="<?= e(app_url('login.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label class="form-label" for="usuario">Usuario</label>
                <input class="form-control" id="usuario" name="usuario" type="text" minlength="4" maxlength="50" pattern="[A-Za-z0-9._-]{4,50}" required autocomplete="username" value="<?= e($username ?? '') ?>">

                <label class="form-label" for="contrasena">Contrasena</label>
                <input class="form-control" id="contrasena" name="contrasena" type="password" required autocomplete="current-password">

                <button class="btn btn-primary w-100" type="submit">Iniciar sesion <span aria-hidden="true">-&gt;</span></button>
            </form>
            <p class="auth-switch">¿Aun no tienes una cuenta? <a href="<?= e(app_url('register.php')) ?>">Registrate como estudiante</a></p>
            <p class="auth-switch"><a href="<?= e(app_url('postular-tutor.php')) ?>">Postular como tutor</a></p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
