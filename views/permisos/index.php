<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Permisos</h1><p>Configure los accesos heredados por rol y las excepciones individuales.</p></div></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="card permission-selector">
        <form method="get" action="<?= e(app_url('permisos/')) ?>">
            <div class="form-grid">
                <div><label for="rol">Configurar rol</label><select id="rol" name="rol" onchange="this.form.submit()"><?php foreach ($data['roles'] as $role): ?><option value="<?= (int) $role['id_rol'] ?>" <?= (int) $data['role_id'] === (int) $role['id_rol'] ? 'selected' : '' ?>><?= e($role['nombre_rol']) ?></option><?php endforeach; ?></select></div>
                <div><label for="usuario">Configurar usuario</label><select id="usuario" name="usuario" onchange="this.form.submit()"><option value="">Seleccione</option><?php foreach ($data['users'] as $userOption): ?><option value="<?= (int) $userOption['id_usuario'] ?>" <?= (int) $data['user_id'] === (int) $userOption['id_usuario'] ? 'selected' : '' ?>><?= e($userOption['apellido'] . ', ' . $userOption['nombre'] . ' (' . $userOption['usuario'] . ')') ?></option><?php endforeach; ?></select></div>
            </div>
        </form>
    </section>

    <section class="permission-columns">
        <div class="card"><div class="section-heading"><h2>Permisos del rol</h2><span class="eyebrow">Predeterminados</span></div>
            <form method="post" action="<?= e(app_url('permisos/save_role.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_rol" value="<?= (int) $data['role_id'] ?>"><input type="hidden" name="id_usuario" value="<?= (int) $data['user_id'] ?>">
                <div class="permission-list"><?php foreach ($data['role_matrix'] as $module): ?><label class="permission-row"><span><strong><?= e($module['nombre']) ?></strong><small><?= e($module['descripcion']) ?></small></span><input type="hidden" name="permiso[<?= (int) $module['id_modulo'] ?>]" value="0"><input type="checkbox" name="permiso[<?= (int) $module['id_modulo'] ?>]" value="1" <?= (int) $module['permitido'] === 1 ? 'checked' : '' ?>></label><?php endforeach; ?></div>
                <button type="submit">Guardar rol</button>
            </form>
        </div>
        <div class="card"><div class="section-heading"><h2>Excepciones del usuario</h2><span class="eyebrow">Heredar / permitir / denegar</span></div>
            <?php if ($data['user_id']): ?><form method="post" action="<?= e(app_url('permisos/save_user.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_usuario" value="<?= (int) $data['user_id'] ?>"><input type="hidden" name="id_rol" value="<?= (int) $data['role_id'] ?>"><div class="permission-list"><?php foreach ($data['user_matrix'] as $module): ?><label class="permission-row"><span><strong><?= e($module['nombre']) ?></strong><small>Rol: <?= (int) $module['rol_permitido'] === 1 ? 'Permitido' : 'Denegado' ?></small></span><select name="permiso[<?= (int) $module['id_modulo'] ?>]"><option value="heredar" <?= $module['usuario_permitido'] === null ? 'selected' : '' ?>>Heredar del rol</option><option value="permitir" <?= (int) $module['usuario_permitido'] === 1 ? 'selected' : '' ?>>Permitir</option><option value="denegar" <?= $module['usuario_permitido'] !== null && (int) $module['usuario_permitido'] === 0 ? 'selected' : '' ?>>Denegar</option></select></label><?php endforeach; ?></div><button type="submit">Guardar usuario</button></form><?php else: ?><p class="empty-state">Seleccione un usuario para configurar excepciones.</p><?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
