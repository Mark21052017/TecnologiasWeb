<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('tutor');
header('Location: ' . app_url('mi-perfil/'), true, 302);
exit;
