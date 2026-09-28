<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Administración MG</span><h1>Modalidades disponibles por carrera</h1><p>Esta configuración determina la oferta que podrá aparecer en la futura solicitud estudiantil.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/configuracion.php')) ?>">Volver a Configuración</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="card mg-panel">
        <p class="form-hint">Solo se puede ofrecer una modalidad activa. Deshabilitar una relación conserva el registro y no elimina información histórica.</p>
        <?php if ($pairs): ?><div class="table-wrapper"><table><thead><tr><th>Carrera</th><th>Modalidad</th><th>Estado catálogo</th><th>Disponible para solicitudes</th><th>Último cambio</th></tr></thead><tbody>
            <?php foreach ($pairs as $pair): ?><tr><td><?= e($pair['nombre_carrera']) ?></td><td><?= e($pair['modalidad']) ?></td><td><?= e($pair['estado_modalidad']) ?></td><td>
                <form class="mg-actions" method="post" action="<?= e(app_url('modalidades-grado/oferta-carreras.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_carrera" value="<?= (int) $pair['id_carrera'] ?>"><input type="hidden" name="id_modalidad" value="<?= (int) $pair['id_modalidad'] ?>">
                    <input type="hidden" name="disponible" value="<?= (int) $pair['disponible'] === 1 ? '0' : '1' ?>">
                    <span class="status <?= (int) $pair['disponible'] === 1 ? 'status-activo' : 'status-inactivo' ?>"><?= (int) $pair['disponible'] === 1 ? 'Disponible' : 'No disponible' ?></span>
                    <button class="btn btn-sm btn-outline-primary" type="submit" <?= $pair['estado_modalidad'] !== 'activa' && (int) $pair['disponible'] === 0 ? 'disabled' : '' ?>><?= (int) $pair['disponible'] === 1 ? 'Deshabilitar' : 'Habilitar' ?></button>
                </form>
            </td><td><?= $pair['actualizado_en'] ? e($pair['actualizado_en']) . '<br><small>' . e($pair['actualizado_por'] ?? 'Usuario eliminado') . '</small>' : '—' ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">Registre carreras y modalidades antes de configurar la oferta.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
