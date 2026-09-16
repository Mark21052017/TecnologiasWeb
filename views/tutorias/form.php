<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container narrow-wide"><section class="card"><h1>Nueva solicitud de tutoria</h1><p class="form-intro">Seleccione una materia y un tutor asignado.</p>
    <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= e(app_url('tutorias/create.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid">
        <div class="form-full"><label for="id_oferta">Materia y tutor</label><select id="id_oferta" name="id_oferta" required><option value="">Seleccione</option><?php foreach ($offerings as $offering): ?><option value="<?= (int) $offering['id_tutor'] . ':' . (int) $offering['id_materia'] ?>" <?= ($data['id_oferta'] ?? '') === ((int) $offering['id_tutor'] . ':' . (int) $offering['id_materia']) ? 'selected' : '' ?>><?= e($offering['nombre_materia'] . ' - ' . $offering['tutor'] . ' | ' . $offering['disponibilidad']) ?></option><?php endforeach; ?></select><?php if (!$offerings): ?><small>No hay materias con disponibilidad registrada.</small><?php endif; ?></div>
        <div><label for="fecha">Fecha</label><input id="fecha" name="fecha" type="date" min="<?= e(date('Y-m-d')) ?>" required value="<?= e($data['fecha'] ?? '') ?>"></div>
        <div><label for="modalidad">Modalidad</label><select id="modalidad" name="modalidad" required><option value="presencial" <?= ($data['modalidad'] ?? '') === 'presencial' ? 'selected' : '' ?>>Presencial</option><option value="virtual" <?= ($data['modalidad'] ?? '') === 'virtual' ? 'selected' : '' ?>>Virtual</option></select></div>
        <div><label for="hora_inicio">Hora de inicio</label><input id="hora_inicio" name="hora_inicio" type="time" required data-time-start value="<?= e($data['hora_inicio'] ?? '') ?>"></div>
        <div><label for="hora_fin">Hora de fin</label><input id="hora_fin" name="hora_fin" type="time" required data-time-end value="<?= e($data['hora_fin'] ?? '') ?>"></div>
        <div class="form-full"><label for="lugar_o_enlace">Lugar o enlace</label><input id="lugar_o_enlace" name="lugar_o_enlace" type="text" maxlength="200" value="<?= e($data['lugar_o_enlace'] ?? '') ?>"></div>
        <div class="form-full"><label for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="4" maxlength="2000"><?= e($data['observaciones'] ?? '') ?></textarea></div>
    </div><button type="submit">Enviar solicitud</button><a class="button secondary" href="<?= e(app_url('tutorias/')) ?>">Cancelar</a></form>
</section></main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
