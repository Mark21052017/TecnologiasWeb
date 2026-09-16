<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Mis materias</h1><p>Agrega las materias que puedes atender y mantenlas actualizadas.</p></div></div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="card form-card">
        <h2>Agregar materia</h2>
        <p class="form-hint">La materia se agregara inmediatamente a tu perfil profesional.</p>
        <?php if ($availableSubjects): ?>
            <form method="post" action="<?= e(app_url('tutor/mis_materias/agregar.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-grid">
                    <div>
                        <label for="id_materia">Materia</label>
                        <select id="id_materia" name="id_materia" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($availableSubjects as $subject): ?>
                                <option value="<?= (int) $subject['id_materia'] ?>"><?= e($subject['nombre_materia'] . ' - ' . ($subject['nombre_carrera'] ?: 'General')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">Agregar materia</button>
            </form>
        <?php else: ?>
            <p class="empty-state">Ya tienes todas las materias del catalogo asignadas.</p>
        <?php endif; ?>
    </section>

    <div class="table-wrapper card">
        <table>
            <thead><tr><th>Materia</th><th>Carrera</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td><?= e($subject['nombre_materia']) ?></td>
                        <td><?= e($subject['nombre_carrera'] ?: 'General') ?></td>
                        <td class="actions">
                            <form method="post" action="<?= e(app_url('tutor/mis_materias/eliminar.php')) ?>" onsubmit="return confirm('Quitar esta materia de tu perfil?');">
                                <input type="hidden" name="id_materia" value="<?= (int) $subject['id_materia'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button class="link-button" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$subjects): ?><tr><td colspan="3">Todavia no tienes materias asignadas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
