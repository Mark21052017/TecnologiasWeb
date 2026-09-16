<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Tutores disponibles</h1><p>Conoce las materias y horarios de atención.</p></div><a class="button" href="<?= e(app_url('tutorias/create.php')) ?>">Solicitar tutoria</a></div>
    <div class="table-toolbar"><div class="search-field"><label class="sr-only" for="tutor-search">Buscar tutores</label><input id="tutor-search" type="search" placeholder="Buscar tutor..." data-table-search></div><span class="table-meta" data-table-count><?= count($tutors) ?> resultado<?= count($tutors) === 1 ? '' : 's' ?></span></div>
    <div class="table-wrapper card"><table><thead><tr><th>Tutor</th><th>Especialidad</th><th>Materias</th><th>Horarios registrados</th></tr></thead><tbody>
        <?php foreach ($tutors as $tutor): ?><tr data-row><td><?= e($tutor['tutor']) ?></td><td><?= e($tutor['especialidad'] ?: 'No especificada') ?></td><td><?= e($tutor['materias']) ?></td><td><?= (int) $tutor['total_horarios'] ?></td></tr><?php endforeach; ?>
        <?php if (!$tutors): ?><tr><td colspan="4">No hay tutores disponibles.</td></tr><?php endif; ?><tr data-search-empty hidden><td colspan="4" class="empty-state">No se encontraron tutores.</td></tr>
    </tbody></table></div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
