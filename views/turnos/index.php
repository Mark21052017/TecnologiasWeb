<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container">
    <div class="page-heading"><div><h1>Turnos</h1><p>La universidad define estos turnos; los tutores solo seleccionan su disponibilidad.</p></div><a class="button btn btn-primary" href="<?= e(app_url('turnos/create.php')) ?>">Nuevo turno</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?><?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <div class="table-wrapper card"><table><thead><tr><th>Turno</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($turnos as $turno): ?><tr><td><?= e($turno['nombre_turno']) ?></td><td><?= e(substr($turno['hora_inicio'], 0, 5)) ?></td><td><?= e(substr($turno['hora_fin'], 0, 5)) ?></td><td><span class="status status-<?= e($turno['estado']) ?>"><?= e($turno['estado']) ?></span></td><td class="actions"><a class="icon-action" href="<?= e(app_url('turnos/edit.php?id=' . (int) $turno['id_turno'])) ?>" title="Editar turno" aria-label="Editar turno"><i class="bi bi-pencil-square" aria-hidden="true"></i></a><form method="post" action="<?= e(app_url('turnos/delete.php')) ?>" onsubmit="return confirm('Eliminar este turno?');"><input type="hidden" name="id" value="<?= (int) $turno['id_turno'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-danger" type="submit" title="Eliminar turno" aria-label="Eliminar turno"><i class="bi bi-trash3" aria-hidden="true"></i></button></form></td></tr><?php endforeach; ?>
        <?php if (!$turnos): ?><tr><td colspan="5">No hay turnos registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
    <p class="form-hint">Las aulas se administran desde <a href="<?= e(app_url('aulas/')) ?>">Aulas</a>.</p>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
