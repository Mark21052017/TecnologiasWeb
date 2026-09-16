<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container narrow-wide">
    <div class="page-heading">
        <div>
            <h1>Roles</h1>
            <p>Roles disponibles para los usuarios.</p>
        </div>
        <a class="button" href="<?= e(app_url('roles/create.php')) ?>">Nuevo rol</a>
    </div>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <div class="table-toolbar">
        <div class="search-field">
            <span class="search-icon" aria-hidden="true">/</span>
            <label class="sr-only" for="role-search">Buscar roles</label>
            <input id="role-search" type="search" placeholder="Buscar rol..." data-table-search>
        </div>
        <span class="table-meta" data-table-count><?= count($roles) ?> resultado<?= count($roles) === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrapper card">
        <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr data-row>
                        <td><?= (int) $role['id_rol'] ?></td>
                        <td><?= e($role['nombre_rol']) ?></td>
                        <td class="actions">
                            <a href="<?= e(app_url('roles/edit.php?id=' . (int) $role['id_rol'])) ?>">Editar</a>
                            <form method="post" action="<?= e(app_url('roles/delete.php')) ?>" onsubmit="return confirm('Eliminar este rol?');">
                                <input type="hidden" name="id" value="<?= (int) $role['id_rol'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="link-button" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr data-search-empty hidden><td colspan="3" class="empty-state">No se encontraron roles.</td></tr>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
