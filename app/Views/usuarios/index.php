<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading">
        <div>
            <h1>Usuarios</h1>
            <p>Administracion de usuarios del sistema.</p>
        </div>
        <a class="button" href="<?= e(app_url('usuarios/create.php')) ?>">Nuevo usuario</a>
    </div>

    <?php if (!empty($message)): ?>
        <p class="success" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p class="alert" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <div class="table-wrapper card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></td>
                        <td><?= e($usuario['usuario']) ?></td>
                        <td><?= e($usuario['correo']) ?></td>
                        <td><?= e($usuario['nombre_rol']) ?></td>
                        <td><span class="status status-<?= e($usuario['estado']) ?>"><?= e($usuario['estado']) ?></span></td>
                        <td class="actions">
                            <a href="<?= e(app_url('usuarios/edit.php?id=' . (int) $usuario['id_usuario'])) ?>">Editar</a>
                            <?php if ($usuario['estado'] === 'activo'): ?>
                                <form method="post" action="<?= e(app_url('usuarios/delete.php')) ?>" onsubmit="return confirm('Desactivar este usuario?');">
                                    <input type="hidden" name="id" value="<?= (int) $usuario['id_usuario'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <button class="link-button" type="submit">Desactivar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$usuarios): ?>
                    <tr>
                        <td colspan="6">No hay usuarios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
