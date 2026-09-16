<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="container">
    <div class="page-heading"><div><h1>Registro de accesos</h1><p>Auditoria de los intentos de inicio de sesion.</p></div></div>
    <div class="table-wrapper card"><table><thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Nombre</th><th>IP de origen</th><th>Resultado</th></tr></thead><tbody>
        <?php foreach ($accesses as $access): ?><tr><td><?= e($access['fecha_hora']) ?></td><td><?= e($access['usuario']) ?></td><td><?= e($access['nombre'] . ' ' . $access['apellido']) ?></td><td><?= e($access['ip_origen'] ?: 'No disponible') ?></td><td><span class="status status-<?= $access['resultado'] === 'exitoso' ? 'activo' : 'inactivo' ?>"><?= e($access['resultado']) ?></span></td></tr><?php endforeach; ?>
        <?php if (!$accesses): ?><tr><td colspan="5">No hay accesos registrados.</td></tr><?php endif; ?>
    </tbody></table></div>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
