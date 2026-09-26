<?php
$pendingActionLabel = $role === 'tutor' ? 'Cancelar sesion' : 'Cancelar tutoria';
$pageTitle = $role === 'estudiante' ? 'Mis tutorias' : ($role === 'tutor' ? 'Tutorias' : 'Gestion de tutorias');
$pageDescription = $role === 'estudiante'
    ? 'Consulta tus inscripciones, sesiones, horarios y estados.'
    : ($role === 'tutor' ? 'Organiza y atiende las sesiones de tus estudiantes.' : 'Consulta inscripciones y sesiones de apoyo academico.');
require __DIR__ . '/../layouts/header.php';
?>

<main class="container">
    <div class="page-heading">
        <div><h1><?= e($pageTitle) ?></h1><p><?= e($pageDescription) ?></p></div>
        <?php if ($role === 'tutor'): ?><a class="button" href="<?= e(app_url('tutorias/programar.php')) ?>">Programar sesion</a><?php elseif ($role === 'estudiante'): ?><a class="button" href="<?= e(app_url('materias-disponibles/')) ?>">Ver ofertas</a><?php endif; ?>
    </div>
    <?php if (!empty($message)): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>

    <section class="card filter-card">
        <div class="section-heading"><h2>Filtrar sesiones</h2><span class="eyebrow">Historial y agenda</span></div>
        <form method="get" action="<?= e(app_url('tutorias/')) ?>">
            <div class="form-grid">
                <div><label for="filter_materia">Materia</label><select id="filter_materia" name="id_materia"><option value="">Todas</option><?php foreach ($filterOptions as $option): ?><option value="<?= (int) $option['id_materia'] ?>" <?= (string) $filters['id_materia'] === (string) $option['id_materia'] ? 'selected' : '' ?>><?= e($option['nombre_materia']) ?></option><?php endforeach; ?></select></div>
                <div><label for="filter_estado">Estado</label><select id="filter_estado" name="estado"><option value="">Todos</option><?php foreach (['pendiente', 'confirmada', 'realizada', 'cancelada'] as $status): ?><option value="<?= e($status) ?>" <?= $filters['estado'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
                <div><label for="fecha_desde">Desde</label><input id="fecha_desde" name="fecha_desde" type="date" value="<?= e($filters['fecha_desde']) ?>"></div>
                <div><label for="fecha_hasta">Hasta</label><input id="fecha_hasta" name="fecha_hasta" type="date" value="<?= e($filters['fecha_hasta']) ?>"></div>
            </div>
            <button type="submit">Filtrar</button> <a class="button secondary" href="<?= e(app_url('tutorias/')) ?>">Limpiar</a>
        </form>
    </section>

    <section class="card">
        <div class="section-heading"><h2><?= $role === 'tutor' ? 'Estudiantes inscritos' : ($role === 'estudiante' ? 'Mis inscripciones' : 'Inscripciones activas') ?></h2><span class="eyebrow">Asignaciones academicas</span></div>
        <div class="table-wrapper"><table><thead><tr><th>Materia</th><th>Periodo</th><?php if ($role !== 'estudiante'): ?><th>Estudiante</th><?php endif; ?><th>Tutor</th><th>Horario</th><th>Aula</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach ($inscripciones as $enrollment): ?><tr>
                <td><?= e($enrollment['nombre_materia']) ?><span class="table-profile-description">Grupo <?= e($enrollment['nombre_grupo']) ?></span></td>
                <td><?= e($enrollment['nombre_periodo']) ?><span class="table-profile-description"><?= e($enrollment['fecha_inicio']) ?> a <?= e($enrollment['fecha_fin']) ?></span><span class="table-profile-description">Inscrita: <?= e(substr((string) $enrollment['fecha_inscripcion'], 0, 10)) ?></span></td>
                <?php if ($role !== 'estudiante'): ?><td><?= e($enrollment['estudiante']) ?></td><?php endif; ?>
                <td><?= e($enrollment['tutor']) ?></td>
                <td><?= e($enrollment['dia_semana'] . ' ' . substr($enrollment['hora_inicio'], 0, 5) . ' - ' . substr($enrollment['hora_fin'], 0, 5)) ?></td>
                <td><?= e($enrollment['nombre_aula'] ?: 'Sin aula') ?></td>
                <td><span class="status status-<?= e($enrollment['estado']) ?>"><?= e($enrollment['estado']) ?></span></td>
                <td class="actions">
                    <?php if ($role === 'administrador'): ?><a class="button button-small" href="<?= e(app_url('tutorias/detalle.php?id_inscripcion=' . (int) $enrollment['id_inscripcion'])) ?>">Ver inscritos</a><?php elseif ($role === 'tutor' && $enrollment['estado'] === 'inscrita'): ?><a class="button button-small" href="<?= e(app_url('tutorias/programar.php?id_inscripcion=' . (int) $enrollment['id_inscripcion'])) ?>">Programar</a><?php elseif ($role === 'estudiante' && $enrollment['estado'] === 'inscrita'): ?><form method="post" action="<?= e(app_url('inscripciones/cancel.php')) ?>" onsubmit="return confirm('Cancelar esta inscripcion?');"><input type="hidden" name="id_inscripcion" value="<?= (int) $enrollment['id_inscripcion'] ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Cancelar</button></form><?php else: ?>-<?php endif; ?>
                </td>
            </tr><?php endforeach; ?>
            <?php if (!$inscripciones): ?><tr><td colspan="8">No hay inscripciones para mostrar.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="card">
        <div class="section-heading"><h2><?= $role === 'tutor' ? 'Mis sesiones' : 'Sesiones programadas' ?></h2><span class="eyebrow">Agenda de atencion</span></div>
        <div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Materia</th><th>Estudiante</th><th>Tutor</th><th>Horario</th><th>Modalidad y lugar</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            <?php foreach ($tutorias as $tutoria): ?><tr>
                <td><?= e($tutoria['fecha']) ?></td>
                <td><?= e($tutoria['nombre_materia']) ?></td>
                <td><?= e($tutoria['estudiante']) ?></td>
                <td><?= e($tutoria['tutor']) ?></td>
                <td><?= e(($tutoria['dia_semana'] ? $tutoria['dia_semana'] . ' | ' : '') . ($tutoria['nombre_turno'] ?: substr($tutoria['hora_inicio'], 0, 5) . ' - ' . substr($tutoria['hora_fin'], 0, 5))) ?></td>
                <td><?= e($tutoria['modalidad']) ?><span class="table-profile-description"><?= e($tutoria['lugar_o_enlace'] ?: 'Sin lugar o enlace') ?></span></td>
                <td><span class="status status-<?= e($tutoria['estado']) ?>"><?= e($tutoria['estado']) ?></span></td>
                <td class="actions">
                    <?php if ($role === 'administrador' && !empty($tutoria['id_inscripcion'])): ?><a class="button button-small" href="<?= e(app_url('tutorias/detalle.php?id_inscripcion=' . (int) $tutoria['id_inscripcion'])) ?>">Ver inscritos</a><?php endif; ?>
                    <?php if ($role === 'estudiante' && $tutoria['estado'] === 'pendiente'): ?><form method="post" action="<?= e(app_url('tutorias/status.php')) ?>"><input type="hidden" name="id" value="<?= (int) $tutoria['id_tutoria'] ?>"><input type="hidden" name="estado" value="cancelada"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-danger" type="submit" title="Cancelar tutoria" aria-label="Cancelar tutoria"><i class="bi bi-x-circle" aria-hidden="true"></i><span class="visually-hidden">Cancelar tutoria</span></button></form><?php endif; ?>
                    <?php if (in_array($role, ['administrador', 'tutor'], true) && $tutoria['estado'] === 'pendiente'): ?><form method="post" action="<?= e(app_url('tutorias/status.php')) ?>"><input type="hidden" name="id" value="<?= (int) $tutoria['id_tutoria'] ?>"><input type="hidden" name="estado" value="confirmada"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-success" type="submit" title="Confirmar sesion" aria-label="Confirmar sesion"><i class="bi bi-check-circle" aria-hidden="true"></i><span class="visually-hidden">Confirmar sesion</span></button></form><form method="post" action="<?= e(app_url('tutorias/status.php')) ?>"><input type="hidden" name="id" value="<?= (int) $tutoria['id_tutoria'] ?>"><input type="hidden" name="estado" value="cancelada"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-danger" type="submit" title="<?= e($pendingActionLabel) ?>" aria-label="<?= e($pendingActionLabel) ?>"><i class="bi bi-x-circle" aria-hidden="true"></i><span class="visually-hidden"><?= e($pendingActionLabel) ?></span></button></form><?php elseif (in_array($role, ['administrador', 'tutor'], true) && $tutoria['estado'] === 'confirmada'): ?><form method="post" action="<?= e(app_url('tutorias/status.php')) ?>"><input type="hidden" name="id" value="<?= (int) $tutoria['id_tutoria'] ?>"><input type="hidden" name="estado" value="realizada"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-success" type="submit" title="Marcar sesion como realizada" aria-label="Marcar sesion como realizada"><i class="bi bi-check2-circle" aria-hidden="true"></i><span class="visually-hidden">Marcar sesion como realizada</span></button></form><form method="post" action="<?= e(app_url('tutorias/status.php')) ?>"><input type="hidden" name="id" value="<?= (int) $tutoria['id_tutoria'] ?>"><input type="hidden" name="estado" value="cancelada"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="link-button icon-action icon-action-danger" type="submit" title="Cancelar sesion" aria-label="Cancelar sesion"><i class="bi bi-x-circle" aria-hidden="true"></i><span class="visually-hidden">Cancelar sesion</span></button></form><?php endif; ?>
                </td>
            </tr><?php endforeach; ?>
            <?php if (!$tutorias): ?><tr><td colspan="8">No hay sesiones para mostrar.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
