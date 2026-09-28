<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading">
        <div><h1>Estudiantes inscritos</h1><p>Consulta los estudiantes relacionados con esta tutoría.</p></div>
        <a class="button secondary" href="<?= e(app_url('tutorias/')) ?>">Volver a tutorías</a>
    </div>

    <section class="card">
        <div class="section-heading"><h2><?= e($detail['nombre_materia']) ?></h2><span class="eyebrow">Detalle de tutoría</span></div>
        <div class="form-grid">
            <p><strong>Tutor</strong><br><?= e($detail['tutor'] ?: 'Por asignar') ?></p>
            <p><strong>Periodo</strong><br><?= e($detail['nombre_periodo']) ?><br><?= e($detail['fecha_inicio'] . ' a ' . $detail['fecha_fin']) ?></p>
            <p><strong>Horario</strong><br><?= $detail['dia_semana'] ? e($detail['dia_semana'] . ' | ' . substr($detail['hora_inicio'], 0, 5) . ' - ' . substr($detail['hora_fin'], 0, 5)) : 'Según horario publicado' ?></p>
            <p><strong>Aula</strong><br><?= e($detail['nombre_aula'] ?: 'Según horario publicado') ?><?php if ($detail['ubicacion']): ?><br><?= e($detail['ubicacion']) ?><?php endif; ?></p>
            <p><strong>Grupo</strong><br><?= e($detail['nombre_grupo']) ?></p>
            <p><strong>Cupos</strong><br><?= (int) $detail['inscritos'] ?> inscritos de <?= (int) $detail['cupo'] ?><br><?= (int) $availableSeats ?> disponibles</p>
        </div>
    </section>

    <section class="card">
        <div class="section-heading"><h2>Listado de estudiantes</h2><span class="eyebrow"><?= count($students) ?> registros</span></div>
        <div class="table-wrapper"><table><thead><tr><th>Registro universitario</th><th>Nombre completo</th><th>Carrera</th><th>Fecha de inscripcion</th><th>Estado</th></tr></thead><tbody>
            <?php foreach ($students as $student): ?><tr><td><?= e($student['registro_universitario'] ?: 'Sin registro') ?></td><td><?= e($student['estudiante']) ?></td><td><?= e($student['nombre_carrera']) ?></td><td><?= e($student['fecha_inscripcion']) ?></td><td><span class="status status-<?= e($student['estado']) ?>"><?= e($student['estado']) ?></span></td></tr><?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="5">No hay estudiantes inscritos en esta tutoría.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
