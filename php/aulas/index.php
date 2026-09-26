<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('ofertas');
$title = 'Aulas';
$activePage = 'aulas';
$aulas = (new AulasController())->index();
$messageKey = (string) ($_GET['message'] ?? '');
$message = $messageKey === 'created' ? 'Aula creada correctamente.' : ($messageKey === 'updated' ? 'Aula actualizada correctamente.' : null);
$error = (string) ($_GET['error'] ?? '');
require dirname(__DIR__, 2) . '/views/aulas/index.php';
