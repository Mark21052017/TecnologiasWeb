<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading">
        <div>
            <h1>Cuentas de acceso</h1>
            <p>Gestiona cuentas, perfiles, fotos y contraseñas desde una sola lista.</p>
        </div>
        <a class="button btn btn-primary" href="<?= e(app_url('usuarios/create.php')) ?>">Nuevo usuario</a>
    </div>

    <?php if (!empty($message)): ?>
        <p class="success" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p class="alert" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <div class="table-toolbar">
        <div class="search-field">
            <span class="search-icon" aria-hidden="true">/</span>
            <label class="sr-only" for="user-search">Buscar cuentas</label>
            <input class="form-control" id="user-search" type="search" placeholder="Buscar cuenta..." value="<?= e($searchQuery) ?>" data-table-search>
        </div>
        <form class="account-role-filter" method="get" action="<?= e(app_url('usuarios/')) ?>" data-account-role-filter>
            <input type="hidden" name="q" value="<?= e($searchQuery) ?>" data-account-role-search>
            <label for="account-role-filter">Ver</label>
            <select class="form-select form-select-sm" id="account-role-filter" name="rol">
                <option value="" <?= $selectedRoleFilter === '' ? 'selected' : '' ?>>Todas las cuentas</option>
                <option value="estudiante" <?= $selectedRoleFilter === 'estudiante' ? 'selected' : '' ?>>Estudiantes</option>
                <option value="tutor" <?= $selectedRoleFilter === 'tutor' ? 'selected' : '' ?>>Tutores</option>
                <option value="otros" <?= $selectedRoleFilter === 'otros' ? 'selected' : '' ?>>Otros roles</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" type="submit">Filtrar</button>
        </form>
        <span class="table-meta" data-table-count><?= count($usuarios) ?> resultado<?= count($usuarios) === 1 ? '' : 's' ?></span>
    </div>

    <div class="table-wrapper card">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th data-row-number="true" data-sortable="false">N.º</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $rowNumber => $usuario):
                    $hasRoleProfile = $usuario['nombre_rol'] === 'estudiante'
                        ? !empty($usuario['id_estudiante'])
                        : ($usuario['nombre_rol'] === 'tutor' ? !empty($usuario['id_tutor']) : null);
                    $missingRoleProfile = in_array($usuario['nombre_rol'], ['estudiante', 'tutor'], true) && !$hasRoleProfile;
                    $editLabel = $missingRoleProfile
                        ? 'Completar perfil'
                        : 'Editar cuenta y perfil';
                    $editPath = 'usuarios/edit.php?id=' . (int) $usuario['id_usuario'] . '&return_to=usuarios';
                    if ($returnRoleFilter !== '') {
                        $editPath .= '&return_role=' . rawurlencode($returnRoleFilter);
                    }
                ?>
                    <tr data-row>
                        <td><?= $rowNumber + 1 ?></td>
                        <td><?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?></td>
                        <td><?= e($usuario['usuario']) ?></td>
                        <td><?= e($usuario['correo']) ?></td>
                        <td><?= e($usuario['nombre_rol']) ?></td>
                        <td><span class="status status-<?= e($usuario['estado']) ?>"><?= e($usuario['estado']) ?></span></td>
                        <td class="actions">
                            <?php if ((int) $usuario['id_usuario'] === (int) ($protectedAdminId ?? 0)): ?>
                                <span class="table-muted" title="Cuenta protegida"><i class="bi bi-shield-lock" aria-hidden="true"></i><span class="visually-hidden">Cuenta admin protegida</span></span>
                            <?php else: ?>
                                <a class="icon-action" href="<?= e(app_url($editPath)) ?>" title="<?= e($editLabel) ?>" aria-label="<?= e($editLabel) ?>"><i class="bi <?= $missingRoleProfile ? 'bi-person-plus' : 'bi-pencil-square' ?>" aria-hidden="true"></i><span class="visually-hidden"><?= e($editLabel) ?></span></a>
                                <?php if ($usuario['estado'] === 'activo'): ?>
                                    <form method="post" action="<?= e(app_url('usuarios/delete.php')) ?>" onsubmit="return confirm('Desactivar este usuario?');">
                                        <input type="hidden" name="id" value="<?= (int) $usuario['id_usuario'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <button class="link-button icon-action icon-action-danger" type="submit" title="Desactivar usuario" aria-label="Desactivar usuario"><i class="bi bi-person-dash" aria-hidden="true"></i><span class="visually-hidden">Desactivar usuario</span></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= e(app_url('usuarios/activate.php')) ?>" onsubmit="return confirm('Activar esta cuenta?');">
                                        <input type="hidden" name="id" value="<?= (int) $usuario['id_usuario'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <button class="link-button icon-action icon-action-success" type="submit" title="<?= $usuario['estado'] === 'pendiente' ? 'Aprobar usuario' : 'Activar usuario' ?>" aria-label="<?= $usuario['estado'] === 'pendiente' ? 'Aprobar usuario' : 'Activar usuario' ?>"><i class="bi bi-person-check" aria-hidden="true"></i><span class="visually-hidden"><?= $usuario['estado'] === 'pendiente' ? 'Aprobar usuario' : 'Activar usuario' ?></span></button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$usuarios): ?>
                    <tr>
                        <td colspan="7"><?= $selectedRoleFilter !== '' ? 'No hay cuentas para este filtro.' : 'No hay cuentas registradas.' ?></td>
                    </tr>
                <?php endif; ?>
                <tr data-search-empty hidden><td colspan="7" class="empty-state">No se encontraron cuentas.</td></tr>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
