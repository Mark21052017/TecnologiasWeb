<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Materias disponibles</h1><p>Asignaturas que tienen tutores activos asignados.</p></div><a class="button" href="<?= e(app_url('tutorias/create.php')) ?>">Solicitar tutoria</a></div>
    <div class="table-toolbar"><div class="search-field"><label class="sr-only" for="subject-search">Buscar materias</label><input id="subject-search" type="search" placeholder="Buscar materia..." data-table-search></div><span class="table-meta" data-table-count><?= count($subjects) ?> resultado<?= count($subjects) === 1 ? '' : 's' ?></span></div>
    <div class="table-wrapper card"><table><thead><tr><th>Materia</th><th>Carrera</th><th>Tutores disponibles</th></tr></thead><tbody>
        <?php foreach ($subjects as $subject): ?><tr data-row><td><?= e($subject['nombre_materia']) ?></td><td><?= e($subject['nombre_carrera'] ?: 'General') ?></td><td><?= (int) $subject['total_tutores'] ?></td></tr><?php endforeach; ?>
        <?php if (!$subjects): ?><tr><td colspan="3">No hay materias con tutores asignados.</td></tr><?php endif; ?><tr data-search-empty hidden><td colspan="3" class="empty-state">No se encontraron materias.</td></tr>
    </tbody></table></div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
