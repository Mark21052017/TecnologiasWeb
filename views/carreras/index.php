<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container narrow-wide">
    <div class="page-heading">
        <div>
            <h1>Carreras</h1>
            <p>Carreras disponibles en el sistema.</p>
        </div>
        <a class="button" href="<?= e(app_url('carreras/create.php')) ?>">Nueva carrera</a>
    </div>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <div class="table-toolbar">
        <div class="search-field">
            <span class="search-icon" aria-hidden="true">/</span>
            <label class="sr-only" for="career-search">Buscar carreras</label>
            <input id="career-search" type="search" placeholder="Buscar carrera..." data-table-search>
        </div>
        <span class="table-meta" data-table-count><?= count($carreras) ?> resultado<?= count($carreras) === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrapper card">
        <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($carreras as $career): ?>
                    <tr data-row>
                        <td><?= (int) $career['id_carrera'] ?></td>
                        <td><?= e($career['nombre_carrera']) ?></td>
                        <td class="actions">
                            <a href="<?= e(app_url('carreras/edit.php?id=' . (int) $career['id_carrera'])) ?>">Editar</a>
                            <form method="post" action="<?= e(app_url('carreras/delete.php')) ?>" onsubmit="return confirm('Eliminar esta carrera?');">
                                <input type="hidden" name="id" value="<?= (int) $career['id_carrera'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="link-button" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr data-search-empty hidden><td colspan="3" class="empty-state">No se encontraron carreras.</td></tr>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
