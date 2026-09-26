<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading">
        <div>
            <h1>Tutores</h1>
            <p>Perfiles de los usuarios que brindan apoyo academico.</p>
        </div>
        <a class="button" href="<?= e(app_url('tutores/create.php')) ?>">Nuevo tutor</a>
    </div>

    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <div class="table-toolbar">
        <div class="search-field">
            <span class="search-icon" aria-hidden="true">/</span>
            <label class="sr-only" for="tutor-search">Buscar tutores</label>
            <input id="tutor-search" type="search" placeholder="Buscar tutor..." data-table-search>
        </div>
        <span class="table-meta" data-table-count><?= count($tutors) ?> resultado<?= count($tutors) === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrapper card">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Tutor</th><th>Correo</th><th>Especialidad</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($tutors as $tutor): ?>
                    <tr data-row>
                        <td><?= e($tutor['nombre'] . ' ' . $tutor['apellido']) ?></td>
                        <td><?= e($tutor['correo']) ?></td>
                        <td><?= e($tutor['especialidad'] ?: 'Sin especialidad') ?></td>
                        <td><span class="status status-<?= e($tutor['estado']) ?>"><?= e($tutor['estado']) ?></span></td>
                        <td class="actions">
                            <a class="icon-action" href="<?= e(app_url('tutores/edit.php?id=' . (int) $tutor['id_tutor'])) ?>" title="Editar tutor" aria-label="Editar tutor"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="visually-hidden">Editar tutor</span></a>
                            <form method="post" action="<?= e(app_url('tutores/delete.php')) ?>" onsubmit="return confirm('Eliminar este perfil?');">
                                <input type="hidden" name="id" value="<?= (int) $tutor['id_tutor'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="link-button icon-action icon-action-danger" type="submit" title="Eliminar tutor" aria-label="Eliminar tutor"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Eliminar tutor</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tutors): ?><tr><td colspan="5">No hay tutores registrados. Cree primero un usuario con rol tutor.</td></tr><?php endif; ?>
                <tr data-search-empty hidden><td colspan="5" class="empty-state">No se encontraron tutores.</td></tr>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
