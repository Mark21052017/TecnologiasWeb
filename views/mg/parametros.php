<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container mg-page">
    <div class="page-heading"><div><span class="hero-kicker">Configuración MG</span><h1>Parámetros</h1><p>Los valores no confirmados se mantienen como propuestas o pendientes y no bloquean operaciones.</p></div><a class="btn btn-outline-secondary" href="<?= e(app_url('modalidades-grado/')) ?>">Volver a MG</a></div>
    <?php if ($message): ?><p class="success" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="card mg-panel">
        <div class="section-heading"><div><h2>Valores configurables</h2><p>La clave es estable para el sistema; se puede actualizar valor, fuente y evidencia.</p></div></div>
        <div class="mg-parameter-list">
            <?php foreach ($parameters as $parameter): ?>
                <form class="mg-parameter-row" method="post" action="<?= e(app_url('modalidades-grado/parametros.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="clave" value="<?= e($parameter['clave']) ?>">
                    <div class="mg-parameter-description"><code><?= e($parameter['clave']) ?></code><span><?= e($parameter['descripcion']) ?></span></div>
                    <label>Valor<input class="form-control" name="valor" type="<?= $parameter['tipo_dato'] === 'texto' ? 'text' : 'number' ?>" <?= $parameter['tipo_dato'] === 'entero' ? 'step="1"' : ($parameter['tipo_dato'] === 'decimal' ? 'step="0.01"' : '') ?> value="<?= e($parameter['valor'] ?? '') ?>" placeholder="Pendiente"></label>
                    <label>Fuente<input class="form-control" name="fuente" maxlength="255" value="<?= e($parameter['fuente'] ?? '') ?>"></label>
                    <label>Evidencia<select class="form-select" name="estado_evidencia" required><?php foreach (['confirmado' => 'Confirmado', 'pendiente' => 'Pendiente', 'propuesta' => 'Propuesta'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $parameter['estado_evidencia'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
                    <button class="btn btn-sm btn-primary" type="submit">Guardar</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
