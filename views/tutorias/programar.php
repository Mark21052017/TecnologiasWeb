<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <section class="card">
        <div class="page-heading">
            <div>
                <h1>Programar sesion</h1>
                <p>Selecciona una inscripción y un horario publicado de la materia para esta sesión.</p>
            </div>
        </div>
        <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if (!$enrollments): ?><p class="empty-state">No tiene estudiantes inscritos con tutor asignado y horarios confirmados.</p><?php endif; ?>
        <form method="post" action="<?= e(app_url('tutorias/programar.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="form-grid">
                <div class="form-full">
                    <label for="id_inscripcion_horario">Estudiante, materia y horario</label>
                    <select id="id_inscripcion_horario" name="id_inscripcion_horario" required <?= !$enrollments ? 'disabled' : '' ?>>
                        <option value="">Seleccione</option>
                        <?php $legacySelectionUsed = false; foreach ($enrollments as $enrollment): $choice = (int) $enrollment['id_inscripcion'] . ':' . (int) $enrollment['id_oferta_horario']; $selected = $choice === $data['id_inscripcion_horario']; if (!$selected && $data['id_inscripcion_horario'] === '' && !$legacySelectionUsed && (int) $data['id_inscripcion'] > 0 && (int) $data['id_inscripcion'] === (int) $enrollment['id_inscripcion']): $selected = true; $legacySelectionUsed = true; endif; ?>
                            <option value="<?= e($choice) ?>" <?= $selected ? 'selected' : '' ?>><?= e($enrollment['estudiante'] . ' · ' . $enrollment['nombre_materia'] . ' · ' . $enrollment['nombre_periodo'] . ' · ' . $enrollment['dia_semana'] . ' ' . substr($enrollment['hora_inicio'], 0, 5) . '–' . substr($enrollment['hora_fin'], 0, 5) . ($enrollment['nombre_aula'] ? ' · ' . $enrollment['nombre_aula'] : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>El estudiante se inscribe en la materia; tú eliges uno de sus horarios publicados para programar cada sesión.</small>
                </div>
                <div>
                    <label for="fecha">Fecha de la sesion</label>
                    <input id="fecha" name="fecha" type="date" min="<?= e(date('Y-m-d')) ?>" required value="<?= e($data['fecha']) ?>">
                </div>
                <div>
                    <label for="modalidad">Modalidad</label>
                    <select id="modalidad" name="modalidad" required>
                        <option value="presencial" <?= $data['modalidad'] === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                        <option value="virtual" <?= $data['modalidad'] === 'virtual' ? 'selected' : '' ?>>Virtual</option>
                    </select>
                </div>
                <div class="form-full">
                    <label for="lugar_o_enlace">Enlace virtual</label>
                    <input id="lugar_o_enlace" name="lugar_o_enlace" type="text" maxlength="200" value="<?= e($data['lugar_o_enlace']) ?>">
                    <small>Obligatorio solamente para sesiones virtuales. El aula presencial se toma de la inscripcion.</small>
                </div>
                <div class="form-full">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="4" maxlength="2000"><?= e($data['observaciones']) ?></textarea>
                </div>
            </div>
            <button type="submit" <?= !$enrollments ? 'disabled' : '' ?>>Programar sesion</button>
            <a class="button secondary" href="<?= e(app_url('tutorias/')) ?>">Cancelar</a>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
