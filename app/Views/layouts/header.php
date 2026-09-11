<?php
$title = $title ?? 'Sistema de Tutorias';
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="site-header-content">
        <a class="brand" href="<?= e(app_url()) ?>">Sistema de Tutorias</a>
        <?php if (Auth::check()): ?>
            <a class="button secondary" href="<?= e(app_url('logout.php')) ?>">Cerrar sesion</a>
        <?php endif; ?>
    </div>
</header>
