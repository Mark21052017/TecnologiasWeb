<?php require __DIR__ . '/layouts/header.php'; ?>

<main class="container">
    <section class="card">
        <h1>Bienvenido, <?= e(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></h1>
        <p>Rol: <strong><?= e($user['nombre_rol'] ?? '') ?></strong></p>
        <p>Este es el punto de partida del panel del sistema.</p>
        <?php if (($user['nombre_rol'] ?? '') === 'administrador'): ?>
            <a class="button" href="<?= e(app_url('usuarios/')) ?>">Gestionar usuarios</a>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/layouts/footer.php'; ?>
