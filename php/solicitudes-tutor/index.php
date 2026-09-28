<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
header('Location: ' . app_url('solicitudes/'), true, 303);
exit;
