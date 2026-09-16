<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Disponibilidad</h1><p>Horarios en los que los tutores pueden atender solicitudes.</p></div><a class="button" href="<?= e(app_url('disponibilidad/create.php')) ?>">Nuevo horario</a></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <div class="table-wrapper card"><table><thead><tr><th>Tutor</th><th>Dia</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($availability as $item): ?><tr><td><?= e($item['tutor']) ?></td><td><?= e($item['dia_semana']) ?></td><td><?= e(substr($item['hora_inicio'], 0, 5)) ?></td><td><?= e(substr($item['hora_fin'], 0, 5)) ?></td><td class="actions"><a href="<?= e(app_url('disponibilidad/edit.php?id=' . (int) $item['id_disponibilidad'])) ?>">Editar</a><form method="post" action="<?= e(app_url('disponibilidad/delete.php')) ?>" onsubmit="return confirm('Eliminar este horario?');"><input type="hidden" name="id" value="<?= (int) $item['id_disponibilidad'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
        <?php if (!$availability): ?><tr><td colspan="5">No hay horarios registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
