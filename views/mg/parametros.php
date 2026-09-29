<?php
$labels = [
    'max_estudiantes_grupo' => 'Estudiantes por grupo',
    'tutor_max_estudiantes' => 'Estudiantes activos por tutor',
    'asistencia_minima_pct' => 'Asistencia mínima',
    'avance_requerido_defensa' => 'Avance mínimo para defensa',
    'miembros_tribunal_predeterminado' => 'Miembros del tribunal',
    'max_defensas' => 'Intentos de defensa',
];
$parameterGroups = [];
foreach ($parameters as $parameter) {
    $parameterGroups[$parameter['categoria']][] = $parameter;
}
$configuration = new MgConfiguracion();
require __DIR__ . '/../layouts/header.php';
?>
<main class="container mg-page">
    <div class="page-heading">
        <div><span class="hero-kicker">Configuración MG</span><h1>Reglas generales</h1><p>Define los límites que comparten las modalidades. Si una modalidad necesita un valor diferente, configúralo en <a href="<?= e(app_url('modalidades-grado/modalidades.php')) ?>">Modalidades</a>.</p></div>
        <a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/configuracion.php')) ?>">Volver a Configuración</a>
    </div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <?php foreach ($parameterGroups as $category => $items): ?>
        <section class="card mg-panel mg-simple-parameters">
            <h2><?= e($category) ?></h2>
            <?php foreach ($items as $parameter): ?>
                <form class="mg-simple-parameter" method="post" action="<?= e(app_url('modalidades-grado/parametros.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="clave" value="<?= e($parameter['clave']) ?>">
                    <div>
                        <label for="param-<?= e($parameter['clave']) ?>"><strong><?= e($labels[$parameter['clave']]) ?><?= $parameter['tipo_dato'] === 'decimal' ? ' (%)' : '' ?></strong></label>
                        <p><?= e($parameter['descripcion']) ?></p>
                        <small>Valor inicial: <?= e($parameter['valor_defecto']) ?><?= $parameter['tipo_dato'] === 'decimal' ? '%' : '' ?>. Se aplica donde la modalidad no define un valor propio.</small>
                    </div>
                    <input class="form-control" id="param-<?= e($parameter['clave']) ?>" type="number" name="valor" step="<?= $parameter['tipo_dato'] === 'entero' ? '1' : '0.01' ?>" <?= $parameter['minimo'] !== null ? 'min="' . e($parameter['minimo']) . '"' : '' ?> <?= $parameter['maximo'] !== null ? 'max="' . e($parameter['maximo']) . '"' : '' ?> value="<?= e((string) $configuration->effectiveValue($parameter['clave'])) ?>" required>
                    <button class="btn btn-sm btn-primary" type="submit">Guardar</button>
                </form>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <details class="card mg-panel mg-parameter-group">
        <summary><span>Historial de cambios</span><small><?= count($parameterHistory) ?> cambio<?= count($parameterHistory) === 1 ? '' : 's' ?> recientes</small></summary>
        <?php if ($parameterHistory): ?><div class="table-wrapper"><table><thead><tr><th>Fecha</th><th>Regla</th><th>Anterior</th><th>Nuevo</th><th>Responsable</th></tr></thead><tbody>
            <?php foreach ($parameterHistory as $event): ?><tr><td><?= e($event['ocurrido_en']) ?></td><td><?= e($labels[$event['clave']] ?? $event['clave']) ?></td><td><?= e($event['valor_anterior'] ?? '—') ?></td><td><?= e($event['valor_nuevo'] ?? '—') ?></td><td><?= e($event['actor']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><p class="form-hint">Aún no hay cambios registrados.</p><?php endif; ?>
    </details>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
