<?php
$title = $title ?? 'Sistema de Tutorias';
$isAuthenticated = Auth::check();
$user = $user ?? Auth::user();
$activePage = $activePage ?? '';
$role = $user['nombre_rol'] ?? '';
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(app_url('css/app.css')) ?>">
</head>
<body class="<?= $isAuthenticated ? 'app-body' : 'auth-body' ?>">
<?php if ($isAuthenticated): ?>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar" data-sidebar>
            <div class="sidebar-brand">
                <span class="brand-mark">TA</span>
                <div>
                    <strong>Tutoria</strong>
                    <span>Apoyo academico</span>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Navegacion principal">
                <span class="nav-label">Workspace</span>
                <a class="nav-link <?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(app_url('dashboard.php')) ?>">
                    <span class="nav-icon">01</span>
                    <span>Resumen</span>
                </a>

                <?php if (($user['nombre_rol'] ?? '') === 'administrador'): ?>
                    <span class="nav-label">Administracion</span>
                    <a class="nav-link <?= $activePage === 'usuarios' ? 'is-active' : '' ?>" href="<?= e(app_url('usuarios/')) ?>">
                        <span class="nav-icon">US</span>
                        <span>Cuentas de acceso</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'roles' ? 'is-active' : '' ?>" href="<?= e(app_url('roles/')) ?>">
                        <span class="nav-icon">RO</span>
                        <span>Roles</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'permisos' ? 'is-active' : '' ?>" href="<?= e(app_url('permisos/')) ?>">
                        <span class="nav-icon">PE</span>
                        <span>Permisos</span>
                    </a>
                    <span class="nav-label">Catalogo academico</span>
                    <a class="nav-link <?= $activePage === 'carreras' ? 'is-active' : '' ?>" href="<?= e(app_url('carreras/')) ?>">
                        <span class="nav-icon">CA</span>
                        <span>Carreras</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'materias' ? 'is-active' : '' ?>" href="<?= e(app_url('materias/')) ?>">
                        <span class="nav-icon">MA</span>
                        <span>Materias</span>
                    </a>
                    <span class="nav-label">Perfiles academicos</span>
                    <a class="nav-link <?= $activePage === 'estudiantes' ? 'is-active' : '' ?>" href="<?= e(app_url('estudiantes/')) ?>">
                        <span class="nav-icon">ES</span>
                        <span>Estudiantes</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'tutores' ? 'is-active' : '' ?>" href="<?= e(app_url('tutores/')) ?>">
                        <span class="nav-icon">TU</span>
                        <span>Tutores</span>
                    </a>
                    <span class="nav-label">Operacion</span>
                    <a class="nav-link <?= $activePage === 'asignaciones' ? 'is-active' : '' ?>" href="<?= e(app_url('asignaciones/')) ?>">
                        <span class="nav-icon">AS</span>
                        <span>Asignaciones</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'disponibilidad' ? 'is-active' : '' ?>" href="<?= e(app_url('disponibilidad/')) ?>">
                        <span class="nav-icon">DI</span>
                        <span>Disponibilidad</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'tutorias' ? 'is-active' : '' ?>" href="<?= e(app_url('tutorias/')) ?>">
                        <span class="nav-icon">TI</span>
                        <span>Tutorias</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'evaluaciones' ? 'is-active' : '' ?>" href="<?= e(app_url('evaluaciones/')) ?>">
                        <span class="nav-icon">EV</span>
                        <span>Evaluaciones</span>
                    </a>
                    <span class="nav-label">Auditoria</span>
                    <a class="nav-link <?= $activePage === 'accesos' ? 'is-active' : '' ?>" href="<?= e(app_url('accesos/')) ?>">
                        <span class="nav-icon">LG</span>
                        <span>Registro de accesos</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'tutor'): ?>
                    <span class="nav-label">Mi espacio</span>
                    <?php if (Auth::can('tutores')): ?><a class="nav-link <?= $activePage === 'mi-perfil-tutor' ? 'is-active' : '' ?>" href="<?= e(app_url('mi-perfil-tutor/')) ?>">
                        <span class="nav-icon">PE</span>
                        <span>Mi perfil</span>
                    </a><?php endif; ?>
                    <?php if (Auth::can('asignaciones')): ?><a class="nav-link <?= $activePage === 'mis-materias' ? 'is-active' : '' ?>" href="<?= e(app_url('mis-materias/')) ?>">
                        <span class="nav-icon">MA</span>
                        <span>Mis materias</span>
                    </a><?php endif; ?>
                    <span class="nav-label">Operacion</span>
                <?php endif; ?>
                <?php if ($role === 'tutor' && Auth::can('disponibilidad')): ?>
                    <a class="nav-link <?= $activePage === 'disponibilidad' ? 'is-active' : '' ?>" href="<?= e(app_url('disponibilidad/')) ?>">
                        <span class="nav-icon">DI</span>
                        <span>Disponibilidad</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'estudiante'): ?>
                    <span class="nav-label">Mi espacio</span>
                    <?php if (Auth::can('materias')): ?><a class="nav-link <?= $activePage === 'materias-disponibles' ? 'is-active' : '' ?>" href="<?= e(app_url('materias-disponibles/')) ?>">
                        <span class="nav-icon">MA</span>
                        <span>Materias disponibles</span>
                    </a><?php endif; ?>
                    <?php if (Auth::can('tutores')): ?><a class="nav-link <?= $activePage === 'tutores-disponibles' ? 'is-active' : '' ?>" href="<?= e(app_url('tutores-disponibles/')) ?>">
                        <span class="nav-icon">TU</span>
                        <span>Tutores disponibles</span>
                    </a><?php endif; ?>
                    <?php if (Auth::can('disponibilidad')): ?><a class="nav-link <?= $activePage === 'horarios-disponibles' ? 'is-active' : '' ?>" href="<?= e(app_url('horarios-disponibles/')) ?>">
                        <span class="nav-icon">HO</span>
                        <span>Horarios disponibles</span>
                    </a><?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($role, ['tutor', 'estudiante'], true) && Auth::can('tutorias')): ?>
                    <a class="nav-link <?= $activePage === 'tutorias' ? 'is-active' : '' ?>" href="<?= e(app_url('tutorias/')) ?>">
                        <span class="nav-icon">TI</span>
                        <span>Tutorias</span>
                    </a>
                    <?php if (Auth::can('evaluaciones')): ?><a class="nav-link <?= $activePage === 'evaluaciones' ? 'is-active' : '' ?>" href="<?= e(app_url('evaluaciones/')) ?>">
                        <span class="nav-icon">EV</span>
                        <span>Evaluaciones</span>
                    </a><?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <span class="avatar avatar-small"><?= e(strtoupper(substr($user['nombre'] ?? 'U', 0, 1))) ?></span>
                    <div>
                        <strong><?= e($user['nombre'] ?? 'Usuario') ?></strong>
                        <span><?= e($user['nombre_rol'] ?? '') ?></span>
                    </div>
                </div>
                <a class="nav-link logout-link" href="<?= e(app_url('logout.php')) ?>">
                    <span class="nav-icon">-&gt;</span>
                    <span>Cerrar sesion</span>
                </a>
            </div>
        </aside>

        <div class="sidebar-backdrop" data-sidebar-close></div>
        <div class="app-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" aria-label="Abrir navegacion" aria-controls="sidebar" aria-expanded="false" data-sidebar-toggle>
                    <span></span><span></span><span></span>
                </button>
                <div class="topbar-context">
                    <span class="eyebrow">Sistema de apoyo academico</span>
                    <strong><?= e($title) ?></strong>
                </div>
                <div class="topbar-user">
                    <span class="avatar"><?= e(strtoupper(substr($user['nombre'] ?? 'U', 0, 1))) ?></span>
                    <div>
                        <strong><?= e(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></strong>
                        <span><?= e($user['nombre_rol'] ?? '') ?></span>
                    </div>
                </div>
            </header>
<?php endif; ?>
