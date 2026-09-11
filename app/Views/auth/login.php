<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container narrow">
    <section class="card">
        <h1>Sistema de apoyo academico</h1>
        <p>Ingrese con sus credenciales para continuar.</p>

        <?php if (!empty($error)): ?>
            <p class="alert" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= e(app_url('login.php')) ?>">
            <label for="usuario">Usuario</label>
            <input id="usuario" name="usuario" type="text" required autocomplete="username" value="<?= e($username ?? '') ?>">

            <label for="contrasena">Contrasena</label>
            <input id="contrasena" name="contrasena" type="password" required autocomplete="current-password">

            <button type="submit">Iniciar sesion</button>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
