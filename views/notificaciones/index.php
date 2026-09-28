<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container">
    <div class="page-heading"><div><span class="hero-kicker">Avisos de tu cuenta</span><h1>Notificaciones</h1><p>Notificaciones internas sobre tus registros, postulaciones y actividades relacionadas.</p></div><?php if ($unread > 0): ?><form method="post" action="<?= e(app_url('notificaciones/')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-outline-primary" name="accion" value="marcar_todas" type="submit">Marcar todas como leídas</button></form><?php endif; ?></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($notifications): ?><section class="notification-list" aria-label="Historial de notificaciones">
        <?php foreach ($notifications as $notification): ?><article class="card notification-card <?= $notification['leida_en'] ? 'is-read' : 'is-unread' ?>">
            <div class="notification-card-content"><span class="eyebrow"><?= e(str_replace('_', ' ', $notification['tipo'])) ?> · <?= e($notification['creada_en']) ?></span><h2><?= e($notification['titulo']) ?></h2><p><?= nl2br(e($notification['mensaje'])) ?></p></div>
            <div class="notification-card-actions"><?php if ($notification['ruta']): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url($notification['ruta'])) ?>">Abrir</a><?php endif; ?><?php if (!$notification['leida_en']): ?><form method="post" action="<?= e(app_url('notificaciones/')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_notificacion" value="<?= (int) $notification['id_notificacion'] ?>"><button class="btn btn-sm btn-outline-secondary" name="accion" value="marcar_leida" type="submit">Marcar leída</button></form><?php else: ?><span class="status status-activo">Leída</span><?php endif; ?></div>
        </article><?php endforeach; ?>
    </section><?php else: ?><section class="card"><p class="empty-state">No tienes notificaciones todavía.</p></section><?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
