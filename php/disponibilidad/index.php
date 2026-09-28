<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireAnyRole(['administrador', 'tutor']);
if ((Auth::user()['nombre_rol'] ?? '') === 'administrador') {
    header('Location: ' . app_url('turnos/'), true, 303);
    exit;
}
header('Location: ' . app_url('materias-ofertadas/'), true, 303);
exit;
