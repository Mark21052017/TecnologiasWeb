<?php $isProtectedAdminRole = $data['role_name'] === 'administrador'; ?>
<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Permisos</h1><p>Configure los accesos del sistema exclusivamente por rol.</p></div></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="card permission-selector">
        <form method="get" action="<?= e(app_url('permisos/')) ?>">
            <div><label for="rol">Configurar rol</label><select id="rol" name="rol" onchange="this.form.submit()"><?php foreach ($data['roles'] as $role): ?><option value="<?= e($role['nombre_rol']) ?>" <?= $data['role_name'] === $role['nombre_rol'] ? 'selected' : '' ?>><?= e(ucfirst($role['nombre_rol'])) ?></option><?php endforeach; ?></select></div>
        </form>
    </section>

    <section class="card">
        <div class="section-heading"><h2>Permisos de <?= e(ucfirst($data['role_name'])) ?></h2><span class="eyebrow">Por rol</span></div>
        <?php if ($isProtectedAdminRole): ?>
            <p class="success" role="status">El Administrador tiene acceso total al sistema y sus permisos no son editables.</p>
            <div class="permission-list"><?php foreach ($data['role_matrix'] as $module): ?><label class="permission-row"><span><strong><?= e($module['nombre']) ?></strong><small><?= e($module['descripcion']) ?></small></span><input type="checkbox" checked disabled></label><?php endforeach; ?></div>
        <?php else: ?>
            <form method="post" action="<?= e(app_url('permisos/save_role.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="rol" value="<?= e($data['role_name']) ?>">
                <div class="permission-list"><?php foreach ($data['role_matrix'] as $module): ?><label class="permission-row"><span><strong><?= e($module['nombre']) ?></strong><small><?= e($module['descripcion']) ?></small></span><input type="checkbox" name="permiso[]" value="<?= e($module['clave']) ?>" <?= (int) $module['permitido'] === 1 ? 'checked' : '' ?>></label><?php endforeach; ?></div>
                <button type="submit">Guardar permisos</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
