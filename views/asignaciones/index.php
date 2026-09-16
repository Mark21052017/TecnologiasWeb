<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading">
        <div><h1>Asignaciones</h1><p>Materias que cada tutor puede atender.</p></div>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="card form-card">
        <h2>Nueva asignacion</h2>
        <form method="post" action="<?= e(app_url('asignaciones/create.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid">
                <div><label for="id_tutor">Tutor</label><select id="id_tutor" name="id_tutor" required><option value="">Seleccione</option><?php foreach ($options['tutors'] as $tutor): ?><option value="<?= (int) $tutor['id_tutor'] ?>"><?= e($tutor['tutor']) ?></option><?php endforeach; ?></select></div>
                <div><label for="id_materia">Materia</label><select id="id_materia" name="id_materia" required><option value="">Seleccione</option><?php foreach ($options['subjects'] as $subject): ?><option value="<?= (int) $subject['id_materia'] ?>"><?= e($subject['nombre_materia']) ?></option><?php endforeach; ?></select></div>
            </div>
            <button type="submit">Asignar materia</button>
        </form>
    </section>

    <div class="table-wrapper card">
        <table><thead><tr><th>Tutor</th><th>Materia</th><th>Carrera</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach ($assignments as $assignment): ?><tr><td><?= e($assignment['tutor']) ?></td><td><?= e($assignment['nombre_materia']) ?></td><td><?= e($assignment['nombre_carrera'] ?: 'General') ?></td><td class="actions"><form method="post" action="<?= e(app_url('asignaciones/delete.php')) ?>" onsubmit="return confirm('Eliminar esta asignacion?');"><input type="hidden" name="id_tutor" value="<?= (int) $assignment['id_tutor'] ?>"><input type="hidden" name="id_materia" value="<?= (int) $assignment['id_materia'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button" type="submit">Eliminar</button></form></td></tr><?php endforeach; ?>
            <?php if (!$assignments): ?><tr><td colspan="4">No hay materias asignadas a tutores.</td></tr><?php endif; ?>
        </tbody></table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
