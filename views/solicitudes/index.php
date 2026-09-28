<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container request-page">
    <div class="page-heading">
        <div><span class="hero-kicker">Apertura académica</span><h1><?= e($title) ?></h1><p><?php if ($role === 'administrador'): ?>Revisa la demanda de materias y administra las ofertas pendientes de publicación.<?php else: ?>Solicita una materia para un periodo futuro; la aprobación genera una oferta pendiente que Administración completa antes de publicarla.<?php endif; ?></p></div>
    </div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $requestError): ?><li><?= e($requestError) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <?php if ($role === 'estudiante'): ?>
        <?php if ($studentId === null): ?>
            <section class="card request-panel"><h2>Perfil académico requerido</h2><p>Tu cuenta todavía no tiene un perfil de estudiante vinculado. Solicita a Administración que complete tu carrera antes de pedir una materia.</p></section>
        <?php else: ?>
            <section class="card request-panel">
                <h2>1. Elegir periodo y turno</h2>
                <p class="form-hint">Se muestran periodos publicados cuya fecha de inicio aún no llega. Puedes solicitar otro turno si la materia ya se ofrece en un turno diferente.</p>
                <?php if ($periods && $turns): ?>
                    <form class="request-filter-form" method="get" action="<?= e(app_url('solicitudes/')) ?>">
                        <label>Periodo<select class="form-select" name="id_periodo" required><option value="">Seleccione</option><?php foreach ($periods as $period): ?><option value="<?= (int) $period['id_periodo'] ?>" <?= $selectedPeriodId === (int) $period['id_periodo'] ? 'selected' : '' ?>><?= e($period['nombre_periodo'] . ' · ' . $period['fecha_inicio'] . ' a ' . $period['fecha_fin']) ?></option><?php endforeach; ?></select></label>
                        <label>Turno preferido<select class="form-select" name="id_turno" required><option value="">Seleccione</option><?php foreach ($turns as $turn): ?><option value="<?= (int) $turn['id_turno'] ?>" <?= $selectedTurnId === (int) $turn['id_turno'] ? 'selected' : '' ?>><?= e($turn['nombre_turno'] . ' (' . substr($turn['hora_inicio'], 0, 5) . '–' . substr($turn['hora_fin'], 0, 5) . ')') ?></option><?php endforeach; ?></select></label>
                        <button class="btn btn-outline-primary" type="submit">Ver materias</button>
                    </form>
                <?php else: ?><p class="empty-state">No hay periodos futuros publicados o turnos activos para solicitar materias.</p><?php endif; ?>
            </section>

            <?php if ($selectedPeriodId && $selectedTurnId): ?>
                <section class="card request-panel">
                    <h2>2. Solicitar apertura</h2>
                    <?php if ($subjects): ?>
                        <form class="request-submit-form" method="post" action="<?= e(app_url('solicitudes/')) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id_periodo" value="<?= $selectedPeriodId ?>">
                            <input type="hidden" name="id_turno" value="<?= $selectedTurnId ?>">
                            <label>Materia<select class="form-select" name="id_materia" required><option value="">Seleccione una materia de tu carrera</option><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id_materia'] ?>"><?= e($subject['nombre_materia'] . ($subject['nombre_carrera'] ? ' · ' . $subject['nombre_carrera'] : ' · General')) ?></option><?php endforeach; ?></select></label>
                            <label>Motivo o comentario (opcional)<textarea class="form-control" name="motivo" maxlength="500" rows="3" placeholder="Por ejemplo, necesitas cursar esta materia para avanzar en tu plan de estudios."></textarea></label>
                            <button class="btn btn-primary" type="submit">Enviar solicitud</button>
                        </form>
                    <?php else: ?><p class="empty-state">No hay materias de tu carrera pendientes de publicación para esa combinación de periodo y turno.</p><?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="card request-panel">
                <div class="section-heading"><div><h2>Mis solicitudes</h2><p>Una solicitud aprobada crea o vincula una oferta pendiente; no la publica automáticamente.</p></div></div>
                <?php if ($requests): ?><div class="table-wrapper"><table><thead><tr><th>Periodo</th><th>Materia</th><th>Turno solicitado</th><th>Fecha</th><th>Estado</th><th>Resultado</th></tr></thead><tbody>
                    <?php foreach ($requests as $request): ?><tr><td><?= e($request['nombre_periodo']) ?></td><td><?= e($request['nombre_materia']) ?></td><td><?= e($request['nombre_turno']) ?></td><td><?= e($request['fecha_solicitud']) ?></td><td><span class="status status-<?= e($request['estado']) ?>"><?= e(ucfirst($request['estado'])) ?></span><?php if ($request['observaciones_revision']): ?><span class="table-profile-description"><?= e($request['observaciones_revision']) ?></span><?php endif; ?></td><td><?php if ($request['id_oferta']): ?>Oferta #<?= (int) $request['id_oferta'] ?> (<?= e($request['estado_oferta']) ?>)<?php elseif ($request['estado'] === 'pendiente'): ?>En revisión<?php else: ?>—<?php endif; ?></td></tr><?php endforeach; ?>
                </tbody></table></div><?php else: ?><p class="empty-state">Aún no has enviado solicitudes de apertura.</p><?php endif; ?>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <section class="card request-panel">
            <div class="section-heading"><div><h2>Demanda de apertura</h2><p>Las solicitudes se agrupan por periodo, materia y turno para mostrar la cantidad de estudiantes interesados.</p></div><span class="table-meta"><?= count($requests) ?> solicitud<?= count($requests) === 1 ? '' : 'es' ?></span></div>
            <?php if ($requests): ?><div class="table-wrapper"><table><thead><tr><th>Estudiante</th><th>Periodo</th><th>Materia / carrera</th><th>Turno</th><th>Demanda</th><th>Estado</th><th>Oferta</th><th>Revisión</th></tr></thead><tbody>
                <?php foreach ($requests as $request): ?><tr>
                    <td><strong><?= e($request['estudiante']) ?></strong><span class="table-profile-description"><?= e($request['correo']) ?></span></td>
                    <td><?= e($request['nombre_periodo']) ?><span class="table-profile-description">Inicio <?= e($request['fecha_inicio']) ?></span></td>
                    <td><?= e($request['nombre_materia']) ?><span class="table-profile-description"><?= e($request['nombre_carrera'] ?: 'Formación general') ?></span><?php if ($request['motivo']): ?><details class="request-reason"><summary>Ver comentario</summary><?= e($request['motivo']) ?></details><?php endif; ?></td>
                    <td><?= e($request['nombre_turno']) ?></td>
                    <td><?= (int) $request['demanda'] ?> estudiante<?= (int) $request['demanda'] === 1 ? '' : 's' ?></td>
                    <td><span class="status status-<?= e($request['estado']) ?>"><?= e(ucfirst($request['estado'])) ?></span></td>
                    <td><?php if ($request['id_oferta']): ?><a href="<?= e(app_url('ofertas/edit.php?id=' . (int) $request['id_oferta'])) ?>">Oferta #<?= (int) $request['id_oferta'] ?> (<?= e($request['estado_oferta']) ?>)</a><?php else: ?>—<?php endif; ?></td>
                    <td>
                        <?php if ($request['estado'] === 'pendiente'): ?>
                            <form class="request-review-form" method="post" action="<?= e(app_url('solicitudes/')) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="revisar"><input type="hidden" name="id_solicitud" value="<?= (int) $request['id_solicitud'] ?>">
                                <input class="form-control form-control-sm" name="observaciones_revision" maxlength="500" placeholder="Observación opcional">
                                <button class="btn btn-sm btn-primary" name="estado" value="aprobada" type="submit">Aprobar / crear oferta pendiente</button>
                                <button class="btn btn-sm btn-outline-danger" name="estado" value="rechazada" type="submit">Rechazar</button>
                            </form>
                        <?php elseif ($request['revisor']): ?><span class="table-profile-description"><?= e($request['revisor']) ?></span><?php else: ?>—<?php endif; ?>
                    </td>
                </tr><?php endforeach; ?>
            </tbody></table></div><?php else: ?><p class="empty-state">No hay solicitudes de apertura registradas.</p><?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
