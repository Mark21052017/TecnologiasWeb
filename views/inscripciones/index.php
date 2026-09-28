<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container">
    <div class="page-heading">
        <div><h1>Inscripciones</h1><p>El estudiante se registra en una materia; Administración asigna al tutor confirmado después.</p></div>
    </div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <div class="table-wrapper card">
        <table>
            <thead><tr><th>Materia</th><th>Periodo</th><?php if ($role !== 'estudiante'): ?><th>Estudiante</th><?php endif; ?><th>Tutor asignado</th><th>Horario</th><th>Aula</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($inscripciones as $enrollment):
                    $tutorAssigned = !empty($enrollment['id_oferta_tutor']);
                    $tutorChoices = $tutorOptionsByOffer[(int) $enrollment['id_oferta']] ?? [];
                ?>
                    <tr>
                        <td><?= e($enrollment['nombre_materia']) ?><span class="table-profile-description">Grupo <?= e($enrollment['nombre_grupo']) ?></span></td>
                        <td><?= e($enrollment['nombre_periodo']) ?><span class="table-profile-description"><?= e($enrollment['fecha_inicio']) ?> a <?= e($enrollment['fecha_fin']) ?></span><span class="table-profile-description">Inscrita: <?= e(substr((string) $enrollment['fecha_inscripcion'], 0, 10)) ?></span></td>
                        <?php if ($role !== 'estudiante'): ?><td><?= e($enrollment['estudiante']) ?></td><?php endif; ?>
                        <td><?php if ($tutorAssigned): ?><?= e($enrollment['tutor'] ?: 'Tutor por asignar') ?><?php if ($enrollment['estado_tutor'] === 'baja_solicitada'): ?><span class="table-profile-description">Baja en revisión; Administración coordina reemplazo</span><?php endif; ?><?php else: ?><span class="table-muted">Por asignar</span><?php endif; ?></td>
                        <td><?= !empty($enrollment['id_oferta_horario']) && $enrollment['dia_semana'] ? e($enrollment['dia_semana'] . ' · ' . substr($enrollment['hora_inicio'], 0, 5) . '–' . substr($enrollment['hora_fin'], 0, 5)) : ($tutorAssigned ? 'Según horario publicado' : 'Se define al asignar tutor') ?></td>
                        <td><?= e($enrollment['nombre_aula'] ?: ($tutorAssigned ? 'Según horario publicado' : 'Se define al asignar tutor')) ?></td>
                        <td><span class="status status-<?= e($enrollment['estado']) ?>"><?= e($enrollment['estado']) ?></span></td>
                        <td class="actions">
                            <?php if ($role === 'administrador' && $enrollment['estado'] === 'inscrita' && !$tutorAssigned): ?>
                                <?php if ($tutorChoices): ?>
                                    <form class="enrollment-assign-tutor-form" method="post" action="<?= e(app_url('inscripciones/asignar-tutor.php')) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="id_inscripcion" value="<?= (int) $enrollment['id_inscripcion'] ?>">
                                        <select class="form-select form-select-sm" name="id_oferta_tutor" aria-label="Tutor para <?= e($enrollment['nombre_materia']) ?>" required>
                                            <option value="">Seleccione tutor</option>
                                            <?php foreach ($tutorChoices as $choice): ?><option value="<?= (int) $choice['id_oferta_tutor'] ?>"><?= e($choice['tutor'] . ($choice['especialidad'] ? ' · ' . $choice['especialidad'] : '')) ?></option><?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">Asignar tutor</button>
                                    </form>
                                <?php else: ?><span class="table-muted">Esperando tutor confirmado</span><?php endif; ?>
                            <?php elseif ($role === 'administrador'): ?>
                                <a class="button button-small" href="<?= e(app_url('tutorias/detalle.php?id_inscripcion=' . (int) $enrollment['id_inscripcion'])) ?>">Ver inscritos</a>
                            <?php elseif ($role === 'tutor' && $enrollment['estado'] === 'inscrita'): ?>
                                <a class="button button-small" href="<?= e(app_url('tutorias/programar.php?id_inscripcion=' . (int) $enrollment['id_inscripcion'])) ?>">Programar sesión</a>
                            <?php elseif ($role === 'estudiante' && $enrollment['estado'] === 'inscrita'): ?>
                                <form method="post" action="<?= e(app_url('inscripciones/cancel.php')) ?>" onsubmit="return confirm('¿Cancelar esta inscripción?');">
                                    <input type="hidden" name="id_inscripcion" value="<?= (int) $enrollment['id_inscripcion'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancelar</button>
                                </form>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$inscripciones): ?><tr><td colspan="<?= $role === 'estudiante' ? 7 : 8 ?>">No hay inscripciones para mostrar.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
