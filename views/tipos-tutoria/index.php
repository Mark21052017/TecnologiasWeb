<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading">
        <div>
            <h1>Tipos de Tutoría</h1>
            <p>Tipos de tutoria disponibles en el catalogo academico.</p>
        </div>
        <a class="button btn btn-primary" href="<?= e(app_url('tipos-tutoria/create.php')) ?>">Nuevo tipo</a>
    </div>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <div class="table-toolbar">
        <div class="search-field">
            <span class="search-icon" aria-hidden="true">/</span>
            <label class="sr-only" for="tutoring-type-search">Buscar tipos de tutoria</label>
            <input class="form-control" id="tutoring-type-search" type="search" placeholder="Buscar tipo..." data-table-search>
        </div>
        <span class="table-meta" data-table-count><?= count($tiposTutoria) ?> resultado<?= count($tiposTutoria) === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrapper card shadow-sm border-0">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>ID</th><th>Nombre</th><th>Descripcion</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($tiposTutoria as $type): ?>
                    <tr data-row>
                        <td><?= (int) $type['id_tipo_tutoria'] ?></td>
                        <td><?= e($type['nombre']) ?></td>
                        <td><?= e($type['descripcion'] ?: 'Sin descripcion') ?></td>
                        <td><span class="status status-<?= e($type['estado']) ?>"><?= e($type['estado']) ?></span></td>
                        <td class="actions">
                            <a class="icon-action" href="<?= e(app_url('tipos-tutoria/edit.php?id=' . (int) $type['id_tipo_tutoria'])) ?>" title="Editar tipo de tutoria" aria-label="Editar tipo de tutoria"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="visually-hidden">Editar tipo de tutoria</span></a>
                            <?php if ($type['estado'] === 'activo'): ?><form method="post" action="<?= e(app_url('tipos-tutoria/deactivate.php')) ?>" onsubmit="return confirm('Desactivar este tipo de tutoria?');"><input type="hidden" name="id" value="<?= (int) $type['id_tipo_tutoria'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-danger" type="submit" title="Desactivar tipo de tutoria" aria-label="Desactivar tipo de tutoria"><i class="bi bi-toggle-off" aria-hidden="true"></i><span class="visually-hidden">Desactivar tipo de tutoria</span></button></form><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr data-search-empty hidden><td colspan="5" class="empty-state">No se encontraron tipos de tutoria.</td></tr>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
