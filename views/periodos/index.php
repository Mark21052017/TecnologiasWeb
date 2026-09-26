<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container">
    <div class="page-heading"><div><h1>Periodos de tutorias</h1><p>Define los rangos generales. El calendario detallado se configura en cada oferta academica.</p></div><a class="button btn btn-primary" href="<?= e(app_url('periodos/create.php')) ?>">Nuevo periodo</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <div class="table-wrapper card"><table><thead><tr><th>Periodo</th><th>Tipo de tutoria</th><th>Actividad</th><th>Inscripciones</th><th>Estado</th><th>Ofertas</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach ($periodos as $periodo): ?><tr><td><strong><?= e($periodo['nombre_periodo']) ?></strong></td><td><?= e($periodo['nombre_tipo_tutoria'] ?? 'Sin tipo') ?></td><td><?= e($periodo['fecha_inicio']) ?> a <?= e($periodo['fecha_fin']) ?></td><td><?= e($periodo['inscripcion_inicio']) ?> a <?= e($periodo['inscripcion_fin']) ?></td><td><span class="status status-<?= e($periodo['estado']) ?>"><?= e($periodo['estado']) ?></span></td><td><?= (int) $periodo['total_ofertas'] ?></td><td class="actions"><a class="icon-action" href="<?= e(app_url('periodos/edit.php?id=' . (int) $periodo['id_periodo'])) ?>" title="Editar periodo" aria-label="Editar periodo"><i class="bi bi-pencil-square" aria-hidden="true"></i></a><form method="post" action="<?= e(app_url('periodos/delete.php')) ?>" onsubmit="return confirm('Eliminar este periodo?');"><input type="hidden" name="id" value="<?= (int) $periodo['id_periodo'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-danger" type="submit" title="Eliminar periodo" aria-label="Eliminar periodo"><i class="bi bi-trash3" aria-hidden="true"></i></button></form></td></tr><?php endforeach; ?>
        <?php if (!$periodos): ?><tr><td colspan="7">No hay periodos registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
