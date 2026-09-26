<?php
$isEditing = ($mode ?? 'create') === 'edit';
$title = $isEditing ? 'Editar horario' : 'Nuevo horario';
$action = $isEditing ? app_url('disponibilidad/edit.php?id=' . (int) $data['id_disponibilidad']) : app_url('disponibilidad/create.php');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container"><section class="card"><h1><?= e($title) ?></h1>
    <?php if (!empty($errors)): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $formError): ?><li><?= e($formError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= e($action) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid">
        <?php if (($user['nombre_rol'] ?? '') === 'administrador'): ?><div><label for="id_tutor">Tutor</label><select id="id_tutor" name="id_tutor" required><option value="">Seleccione</option><?php foreach ($tutors as $tutor): ?><option value="<?= (int) $tutor['id_tutor'] ?>" <?= (string) ($data['id_tutor'] ?? '') === (string) $tutor['id_tutor'] ? 'selected' : '' ?>><?= e($tutor['tutor']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div><label for="dia_semana">Dia</label><select id="dia_semana" name="dia_semana" required><?php foreach (['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'] as $day): ?><option value="<?= e($day) ?>" <?= ($data['dia_semana'] ?? '') === $day ? 'selected' : '' ?>><?= e($day) ?></option><?php endforeach; ?></select></div>
        <div><label for="id_turno">Turno horario</label><select id="id_turno" name="id_turno" required><option value="">Seleccione</option><?php foreach (($turns ?? []) as $turn): ?><option value="<?= (int) $turn['id_turno'] ?>" <?= (string) ($data['id_turno'] ?? '') === (string) $turn['id_turno'] ? 'selected' : '' ?>><?= e($turn['nombre_turno'] . ' (' . substr($turn['hora_inicio'], 0, 5) . ' - ' . substr($turn['hora_fin'], 0, 5) . ')') ?></option><?php endforeach; ?></select></div>
    </div><button type="submit">Guardar</button><a class="button secondary" href="<?= e(app_url('disponibilidad/')) ?>">Cancelar</a></form>
</section></main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
