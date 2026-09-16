<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Horarios disponibles</h1><p>Consulta los espacios registrados por los tutores.</p></div><a class="button" href="<?= e(app_url('tutorias/create.php')) ?>">Solicitar tutoria</a></div>
    <div class="table-wrapper card"><table><thead><tr><th>Materia</th><th>Tutor</th><th>Dia</th><th>Horario</th></tr></thead><tbody>
        <?php foreach ($availability as $item): ?><tr><td><?= e($item['nombre_materia']) ?></td><td><?= e($item['tutor']) ?></td><td><?= e($item['dia_semana']) ?></td><td><?= e(substr($item['hora_inicio'], 0, 5) . ' - ' . substr($item['hora_fin'], 0, 5)) ?></td></tr><?php endforeach; ?>
        <?php if (!$availability): ?><tr><td colspan="4">No hay horarios disponibles registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
