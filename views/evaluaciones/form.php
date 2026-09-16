<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container narrow-wide"><section class="card"><h1>Nueva evaluacion</h1><p class="form-intro">Evalua una tutoria que ya fue marcada como realizada.</p>
    <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= e(app_url('evaluaciones/create.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid">
        <div class="form-full"><label for="id_tutoria">Tutoria</label><select id="id_tutoria" name="id_tutoria" required><option value="">Seleccione</option><?php foreach ($options as $option): ?><option value="<?= (int) $option['id_tutoria'] ?>" <?= (string) ($data['id_tutoria'] ?? '') === (string) $option['id_tutoria'] ? 'selected' : '' ?>><?= e($option['fecha'] . ' - ' . $option['nombre_materia'] . ' - ' . $option['tutor']) ?></option><?php endforeach; ?></select><?php if (!$options): ?><small>No hay tutorias realizadas pendientes de evaluar.</small><?php endif; ?></div>
        <div><label for="calificacion">Calificacion</label><select id="calificacion" name="calificacion" required><option value="">Seleccione</option><?php for ($rating = 1; $rating <= 5; $rating++): ?><option value="<?= $rating ?>" <?= (string) ($data['calificacion'] ?? '') === (string) $rating ? 'selected' : '' ?>><?= $rating ?>/5</option><?php endfor; ?></select></div>
        <div class="form-full"><label for="comentario">Comentario</label><textarea id="comentario" name="comentario" rows="5" maxlength="1000"><?= e($data['comentario'] ?? '') ?></textarea></div>
    </div><button type="submit">Guardar evaluacion</button><a class="button secondary" href="<?= e(app_url('evaluaciones/')) ?>">Cancelar</a></form>
</section></main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
