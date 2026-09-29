<?php require __DIR__ . '/../layouts/header.php';
$academicComplete = $academicVerification
    && (int) $academicVerification[0]['materias_requeridas'] > 0
    && ((int)$academicVerification[0]['materias_requeridas'] - (int)$academicVerification[0]['materias_aprobadas']) <= ($configuration->effectiveValue('exigir_plan_completo', true) ? 0 : (int)$configuration->effectiveValue('materias_pendientes_permitidas', 0));
$formRequest = $editing ? $selectedRequest : null;
$selectedModality = null;
foreach ($modalities as $modalityOption) {
    if ((int)($formRequest['id_modalidad'] ?? $selectedRequest['id_modalidad'] ?? 0) === (int)$modalityOption['id_modalidad']) {
        $selectedModality = $modalityOption;
        break;
    }
}
if ($academicComplete && $selectedModality && $selectedModality['codigo'] === 'GRADUACION_EXCELENCIA') {
    $average = $academicVerification[0]['promedio_aprobadas'] ?? null;
    $threshold = (float)($selectedModality['promedio_minimo'] ?? 90);
    $academicComplete = $average !== null && (float)$average > 90.0 && (float)$average >= $threshold;
}
if (!$configuration->effectiveValue('verificar_materias_aprobadas', true)
    && !$configuration->effectiveValue('exigir_plan_completo', true)) {
    $academicComplete = true;
}
?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Mi espacio</span><h1>Mi Modalidad de Grado</h1><p>Crea un borrador, verifica tus requisitos académicos y sigue las decisiones de Administración.</p></div></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <section class="card mg-panel">
        <h2>Verificación académica previa</h2>
        <?php if ($academicVerification): $academic = $academicVerification[0]; ?>
            <p>Plan asignado: <strong><?= e($academic['codigo_plan']) ?> / <?= e($academic['version_plan']) ?></strong> — <?= e($academic['nombre_carrera']) ?></p>
            <div class="stat-grid"><article class="stat-card"><span class="stat-label">Materias obligatorias</span><strong class="stat-value"><?= (int) $academic['materias_requeridas'] ?></strong></article><article class="stat-card"><span class="stat-label">Aprobadas</span><strong class="stat-value"><?= (int) $academic['materias_aprobadas'] ?></strong></article><article class="stat-card"><span class="stat-label">Pendientes/sin aprobar</span><strong class="stat-value"><?= (int) $academic['materias_requeridas'] - (int) $academic['materias_aprobadas'] ?></strong></article><article class="stat-card"><span class="stat-label">Promedio informativo</span><strong class="stat-value"><?= $academic['promedio_aprobadas'] === null ? '—' : e(number_format((float) $academic['promedio_aprobadas'], 2)) ?></strong></article></div>
        <?php elseif ($selectedRequest && $selectedRequest['evidencia_academica']): ?><p>No hay historial oficial completo todavía. Tu documento <?= e($selectedRequest['evidencia_academica']['estado']) ?> será contrastado por Administración con el plan asignado antes de decidir la solicitud.</p>
        <?php else: ?><p>No hay un historial académico aprobado en el sistema. Puedes guardar tu solicitud y adjuntar un certificado PDF; Administración comprobará la malla y todas tus materias antes de decidir.</p><?php endif; ?>
        <?php if ($pendingSubjects): ?><h3>Materias obligatorias pendientes</h3><ul><?php foreach ($pendingSubjects as $subject): ?><li><?= e($subject['nombre_materia']) ?> — <?= $subject['registrada'] ? 'sin aprobación registrada' : 'sin registro en el historial aprobado' ?></li><?php endforeach; ?></ul><?php endif; ?>
        <p class="form-hint"><?= $academicComplete ? 'El historial disponible cumple los requisitos; la solicitud aún requiere aprobación administrativa.' : ($academicEvidenceReady ? 'Tu documento permite enviar la solicitud para revisión; aún no certifica materias aprobadas ni habilita la modalidad.' : 'Si falta historial académico oficial, guarda un borrador y adjunta el PDF de calificaciones antes de enviar.') ?></p>
    </section>

    <?php if ($requests): ?><section class="card mg-panel"><h2>Mis solicitudes</h2><div class="table-wrapper"><table><thead><tr><th>Solicitud</th><th>Modalidad</th><th>Creada</th><th>Estado</th><th>Calificaciones</th><th>Habilitación</th><th>Detalle</th></tr></thead><tbody>
        <?php foreach ($requests as $request): ?><tr><td>#<?= (int) $request['id_solicitud'] ?></td><td><?= e($request['modalidad']) ?></td><td><?= e($request['creado_en']) ?></td><td><span class="status <?= in_array($request['estado'], ['aprobada'], true) ? 'status-activo' : (in_array($request['estado'], ['rechazada', 'cancelada'], true) ? 'status-inactivo' : '') ?>"><?= e($request['estado']) ?></span></td><td><?= e($request['evidencia_academica']['estado'] ?? 'Historial oficial / sin archivo') ?></td><td><?= $request['habilitado_en'] ? e($request['habilitado_en']) : 'Pendiente' ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/mi-solicitud.php?id=' . (int) $request['id_solicitud'])) ?>">Ver</a></td></tr><?php endforeach; ?>
    </tbody></table></div></section><?php endif; ?>

    <?php if ($selectedRequest): ?>
        <section class="card mg-panel"><h2>Solicitud #<?= (int) $selectedRequest['id_solicitud'] ?> — <?= e($selectedRequest['modalidad']) ?></h2>
            <p>Estado: <strong><?= e($selectedRequest['estado']) ?></strong> · Tipo: <?= e($selectedRequest['tipo_trabajo']) ?></p>
            <?php if ($selectedRequest['tema_preliminar']): ?><p><strong>Tema:</strong> <?= e($selectedRequest['tema_preliminar']) ?></p><?php endif; ?>
            <?php if ($selectedRequest['descripcion']): ?><p><strong>Descripción:</strong><br><?= nl2br(e($selectedRequest['descripcion'])) ?></p><?php endif; ?>
            <?php if ($selectedRequest['observaciones_estudiante']): ?><p><strong>Nota enviada:</strong><br><?= nl2br(e($selectedRequest['observaciones_estudiante'])) ?></p><?php endif; ?>
            <?php if ($selectedRequest['habilitado_en']): ?><p class="success">Habilitada por Administración el <?= e($selectedRequest['habilitado_en']) ?>. La inscripción formal es una etapa posterior.</p><?php endif; ?>
            <section class="mg-panel">
                <h3>Documento temporal de calificaciones</h3>
                <p>Si tu historial oficial aún no está cargado, puedes adjuntar un PDF. Administración comparará cada materia obligatoria con tu plan asignado o con la malla oficial de tu carrera si el plan todavía no está cargado. El archivo no actualiza por sí solo el historial oficial ni aprueba la solicitud. Para Graduación por Excelencia, el promedio de materias obligatorias aprobadas debe ser mayor que 90.</p>
                <?php if ($selectedRequest['evidencia_academica']): $evidence = $selectedRequest['evidencia_academica']; ?>
                    <p>Versión <?= (int)$evidence['numero_version'] ?> · Estado: <strong><?= e($evidence['estado']) ?></strong> · Plan <?= e($evidence['codigo_plan'] ? $evidence['codigo_plan'] . ' / ' . $evidence['version_plan'] : ($evidence['plan_referencia'] ?: 'Por verificar')) ?> · <?= e($evidence['subido_en']) ?></p>
                    <p><a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('modalidades-grado/mi-solicitud.php?id=' . (int)$selectedRequest['id_solicitud'] . '&descargar_evidencia=' . (int)$evidence['id_evidencia'])) ?>">Ver PDF de calificaciones</a></p>
                    <?php if ($evidence['observacion_revision']): ?><p class="alert">Observación de Administración: <?= nl2br(e($evidence['observacion_revision'])) ?></p><?php endif; ?>
                    <?php if ($evidence['estado'] === 'verificada' && $evidence['materias_requeridas'] !== null): ?><p>Materias aprobadas verificadas: <?= (int)$evidence['materias_aprobadas'] ?> / <?= (int)$evidence['materias_requeridas'] ?> · Promedio verificado: <?= e(number_format((float)$evidence['promedio_verificado'],2)) ?></p><?php endif; ?>
                <?php else: ?><p>Aún no adjuntaste evidencia académica.</p><?php endif; ?>
                <?php if (!$academicComplete && in_array($selectedRequest['estado'], ['borrador','observada'], true)): ?>
                    <form class="mg-form-grid" method="post" enctype="multipart/form-data" action="<?= e(app_url('modalidades-grado/mi-solicitud.php?id=' . (int)$selectedRequest['id_solicitud'])) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_solicitud" value="<?= (int)$selectedRequest['id_solicitud'] ?>"><input type="hidden" name="accion" value="subir_evidencia_academica">
                        <label>Certificado o historial (PDF, máximo 5 MB)<input class="form-control" type="file" name="evidencia_academica" accept="application/pdf,.pdf" required></label>
                        <label>Comentario opcional<textarea class="form-control" name="comentario_evidencia" maxlength="2000" rows="2"></textarea></label>
                        <button class="btn btn-outline-primary" type="submit">Adjuntar calificaciones</button>
                    </form>
                <?php endif; ?>
            </section>
            <?php if ($selectedRequest['id_inscripcion']): ?><section class="mg-panel"><h3>Inscripción formal #<?= (int) $selectedRequest['id_inscripcion'] ?></h3><p>Estado: <?= e($selectedRequest['estado_inscripcion']) ?> · Inscrito el <?= e($selectedRequest['inscrito_en']) ?></p><p><strong>Cohorte:</strong> <?= e($selectedRequest['codigo_cohorte'] . ' · ' . $selectedRequest['cohorte']) ?></p><p><strong>Trabajo:</strong> <?= e($selectedRequest['codigo_trabajo']) ?> · <?= e($selectedRequest['tema_trabajo'] ?? 'Sin tema') ?> · <?= e($selectedRequest['tipo_trabajo_formal']) ?> · <?= (int) $selectedRequest['integrantes'] ?> integrante(s)</p></section>
                <h3>Hitos de mi trabajo</h3>
                <?php if ($milestoneTasks): ?><div class="table-wrapper"><table><thead><tr><th>Orden / etapa</th><th>Hito</th><th>Fecha límite</th><th>Estado</th><th>Entrega / avance</th></tr></thead><tbody>
                    <?php foreach ($milestoneTasks as $task): ?><tr><td><?= (int) $task['orden'] ?> · <?= e(strtoupper($task['etapa'])) ?></td><td><?= e($task['nombre']) ?><br><small><?= e($task['tipo_nombre'] ?? $task['tipo']) ?></small></td><td><?= e($task['fecha_limite'] ?? '—') ?><?= (int) $task['vencido'] === 1 ? ' · Vencido' : '' ?></td><td><?= e($task['estado']) ?></td><td>
                        <?php if ($task['tipo'] === 'informe' && (int) $task['requiere_informes'] === 1): ?>
                            <?php if ($task['versiones']): ?><ul class="list-unstyled"><?php foreach ($task['versiones'] as $version): ?><li>Versión <?= (int) $version['numero_version'] ?> · <?= e($version['estado']) ?> · <a href="<?= e(app_url('modalidades-grado/descargar-informe.php?id=' . (int) $version['id_version'])) ?>">Descargar PDF</a><?php if ($version['observacion_tutor']): ?><br><small>Observación: <?= nl2br(e($version['observacion_tutor'])) ?></small><?php endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
                            <?php if (($task['estado'] === 'pendiente' || ($task['estado'] === 'observado' && !empty($task['correccion_permitida']))) && !empty($task['entrega_informe_permitida']) && $selectedRequest['estado_trabajo'] === 'activo'): ?><form method="post" enctype="multipart/form-data" action="<?= e(app_url('modalidades-grado/mi-solicitud.php?id=' . (int) $selectedRequest['id_solicitud'])) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="entregar_informe"><input type="hidden" name="id_solicitud" value="<?= (int) $selectedRequest['id_solicitud'] ?>"><input type="hidden" name="id_seguimiento" value="<?= (int) $task['id_seguimiento'] ?>"><label>Informe PDF<input class="form-control" type="file" name="archivo" accept="application/pdf,.pdf" required></label><label>Avance real (%)<input class="form-control" type="number" min="0" max="100" step="0.01" name="avance_real_pct" value="<?= e($task['avance_real_pct'] ?? '') ?>"></label><label>Comentario<textarea class="form-control" name="observacion_estudiante" rows="2" maxlength="5000"></textarea></label><button class="btn btn-sm btn-primary" type="submit"><?= $task['estado'] === 'observado' ? 'Subir corrección' : 'Entregar informe' ?></button></form><?php elseif ($task['estado'] === 'observado' && empty($task['correccion_permitida'])): ?><span class="table-muted">Se alcanzó el máximo de correcciones configurado.</span><?php elseif ($task['estado'] === 'pendiente' && empty($task['entrega_informe_permitida'])): ?><span class="table-muted">El plazo de entrega del informe ya venció.</span><?php endif; ?>
                        <?php elseif ($task['estado'] === 'pendiente' && !empty($task['entrega_hito_permitida']) && $selectedRequest['estado_trabajo'] === 'activo'): ?><form method="post" action="<?= e(app_url('modalidades-grado/mi-solicitud.php?id=' . (int) $selectedRequest['id_solicitud'])) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="entregar_hito"><input type="hidden" name="id_solicitud" value="<?= (int) $selectedRequest['id_solicitud'] ?>"><input type="hidden" name="id_seguimiento" value="<?= (int) $task['id_seguimiento'] ?>"><label>Avance (%)<input class="form-control" type="number" min="0" max="100" step="0.01" name="avance_real_pct" value="<?= e($task['avance_real_pct'] ?? '') ?>"></label><label>Comentario<textarea class="form-control" name="observacion_estudiante" rows="2" maxlength="5000" required></textarea></label><button class="btn btn-sm btn-primary" type="submit">Registrar entrega</button></form>
                        <?php else: ?><?= $task['fecha_entrega'] ? e($task['fecha_entrega']) : '—' ?><?= $task['avance_real_pct'] === null ? '' : ' · ' . e($task['avance_real_pct']) . '%' ?><?php if ($task['observacion_estudiante']): ?><br><small><?= nl2br(e($task['observacion_estudiante'])) ?></small><?php endif; ?><?php endif; ?>
                    </td></tr><?php endforeach; ?>
                </tbody></table></div><?php else: ?><p class="form-hint">No hay hitos activos configurados para este trabajo. Administración puede aplicar una plantilla desde Calendarios.</p><?php endif; ?>
                <h3>Sesiones de seguimiento y asistencia</h3><?php if ($studentSessions): ?><div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Sesión</th><th>Tutor</th><th>Asistencia</th><th>Observación</th></tr></thead><tbody><?php foreach ($studentSessions as $session): ?><tr><td><?= e($session['inicio']) ?></td><td><?= e($session['titulo']) ?> · <?= e($session['tipo']) ?><br><small><?= e($session['modalidad']) ?> · <?= e($session['ubicacion'] ?? 'Sin ubicación') ?></small></td><td><?= e($session['tutor'] ?? '—') ?></td><td><?= e($session['asistencia']) ?></td><td><?= nl2br(e($session['observacion_asistencia'] ?? '—')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="form-hint">Aún no hay sesiones programadas.</p><?php endif; ?>
                <h3>Resultados de etapa</h3><?php if ($studentStageResults): ?><div class="table-wrapper"><table><thead><tr><th>Etapa</th><th>Estado</th><th>Nota</th><th>Actualizado</th><th>Observaciones</th></tr></thead><tbody><?php foreach ($studentStageResults as $result): ?><tr><td><?= e(strtoupper($result['etapa'])) ?></td><td><?= e($result['estado']) ?></td><td><?= $result['nota'] === null ? '—' : e($result['nota']) ?></td><td><?= e($result['actualizado_en']) ?></td><td><?= nl2br(e($result['observaciones'] ?? '—')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="form-hint">Todavía no hay resultados MDG I/II registrados.</p><?php endif; ?>
                <h3>Tribunal</h3><?php if ($studentDefenseInfo['tribunal']): ?><ul><?php foreach ($studentDefenseInfo['tribunal'] as $member): ?><li><?= e(ucfirst($member['rol']) . ': ' . $member['nombre'] . ' ' . $member['apellido']) ?></li><?php endforeach; ?></ul><?php else: ?><p>Tribunal no asignado.</p><?php endif; ?>
                <h3>Defensas</h3><?php if ($studentDefenseInfo['defensas']): ?><div class="table-wrapper"><table><thead><tr><th>Intento</th><th>Fecha/hora</th><th>Ubicación</th><th>Tribunal del intento</th><th>Estado</th><th>Resultado</th><th>Nota</th><th>Observaciones</th></tr></thead><tbody><?php foreach ($studentDefenseInfo['defensas'] as $defense): ?><tr><td>#<?= (int) $defense['numero_defensa'] ?></td><td><?= e($defense['fecha_hora']) ?></td><td><?= e($defense['ubicacion'] ?? '—') ?></td><td><?= e($defense['tribunal'] ?? '—') ?></td><td><?= e($defense['estado']) ?></td><td><?= e($defense['resultado'] ?? '—') ?></td><td><?= e($defense['nota'] ?? '—') ?></td><td><?= nl2br(e($defense['observaciones'] ?? '—')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Sin defensas registradas.</p><?php endif; ?>
                <?php if ($studentDefenseInfo['cierre']): ?><section class="mg-panel"><h3>Cierre final</h3><p>Resultado: <strong><?= e($studentDefenseInfo['cierre']['resultado']) ?></strong> · <?= e($studentDefenseInfo['cierre']['cerrado_en']) ?></p><?php if ($studentDefenseInfo['cierre']['observaciones']): ?><p><?= nl2br(e($studentDefenseInfo['cierre']['observaciones'])) ?></p><?php endif; ?></section><?php endif; ?>
            <?php endif; ?>
            <h3>Historial de revisión</h3>
            <?php if ($selectedRequest['historial']): ?><div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Acción</th><th>Estado</th><th>Responsable</th><th>Observación</th></tr></thead><tbody><?php foreach ($selectedRequest['historial'] as $event): ?><tr><td><?= e($event['ocurrido_en']) ?></td><td><?= e($event['accion']) ?></td><td><?= e(($event['estado_anterior'] ?? '—') . ' → ' . $event['estado_nuevo']) ?></td><td><?= e($event['actor']) ?></td><td><?= nl2br(e($event['comentario'] ?? '—')) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p>Aún no hay actividad registrada.</p><?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($formRequest || $canCreate): ?>
        <section class="card mg-panel">
            <h2><?= $formRequest ? ($formRequest['estado'] === 'observada' ? 'Corregir solicitud observada' : (in_array($formRequest['estado'], ['enviada','en_revision'], true) ? 'Editar solicitud enviada' : 'Editar borrador')) : 'Crear solicitud' ?></h2>
            <?php if (!$modalities): ?><p>No hay modalidades habilitadas para tu carrera. Contacta a Administración.</p>
            <?php else: ?><form class="mg-form-grid" method="post" action="<?= e(app_url('modalidades-grado/mi-solicitud.php')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($formRequest): ?><input type="hidden" name="id_solicitud" value="<?= (int) $formRequest['id_solicitud'] ?>"><?php endif; ?>
                <label>Modalidad<select class="form-select" name="id_modalidad" required><option value="">Seleccione</option><?php foreach ($modalities as $modality): ?><option value="<?= (int) $modality['id_modalidad'] ?>" <?= (int) ($formRequest['id_modalidad'] ?? 0) === (int) $modality['id_modalidad'] ? 'selected' : '' ?>><?= e($modality['nombre']) ?><?= $modality['descripcion'] ? ' — ' . e($modality['descripcion']) : '' ?></option><?php endforeach; ?></select></label>
                <label>Tipo de trabajo<select class="form-select" name="tipo_trabajo"><option value="individual" <?= ($formRequest['tipo_trabajo'] ?? 'individual') === 'individual' ? 'selected' : '' ?>>Individual</option><option value="grupal" <?= ($formRequest['tipo_trabajo'] ?? '') === 'grupal' ? 'selected' : '' ?>>Grupal (sujeto a modalidad)</option></select></label>
                <label>Tema preliminar<input class="form-control" name="tema_preliminar" maxlength="250" value="<?= e($formRequest['tema_preliminar'] ?? '') ?>"></label>
                <label>Descripción<textarea class="form-control" name="descripcion" rows="4" maxlength="10000"><?= e($formRequest['descripcion'] ?? '') ?></textarea></label>
                <label>Observaciones para Administración<textarea class="form-control" name="observaciones_estudiante" rows="2" maxlength="5000"><?= e($formRequest['observaciones_estudiante'] ?? '') ?></textarea></label>
                <div class="mg-actions"><button class="btn btn-outline-primary" type="submit" name="accion" value="guardar"><?= $formRequest && in_array($formRequest['estado'], ['enviada','en_revision'], true) ? 'Guardar cambios' : 'Guardar borrador' ?></button><?php if ($canSubmitRequest): ?><button class="btn btn-primary" type="submit" name="accion" value="enviar" <?= !($academicComplete || $academicEvidenceReady) ? 'disabled' : '' ?>><?= $formRequest && $formRequest['estado'] === 'observada' ? 'Reenviar solicitud' : 'Enviar solicitud' ?></button><?php endif; ?></div>
            </form><?php endif; ?>
        </section>
    <?php endif; ?>
    <?php if ($canCancel): ?>
        <section class="card mg-panel"><form method="post" action="<?= e(app_url('modalidades-grado/mi-solicitud.php')) ?>"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id_solicitud" value="<?= (int) $selectedRequest['id_solicitud'] ?>"><label>Motivo de cancelación (opcional)<input class="form-control" name="observaciones_estudiante" maxlength="5000"></label><button class="btn btn-outline-danger" type="submit" name="accion" value="cancelar">Cancelar solicitud</button></form></section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
