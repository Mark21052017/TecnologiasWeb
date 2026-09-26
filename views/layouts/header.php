<?php
$title = $title ?? 'Sistema de Tutorias';
$isAuthenticated = Auth::check();
$authUser = Auth::user();
$activePage = $activePage ?? '';
$role = $authUser['nombre_rol'] ?? '';
$profileIcons = [
    'estudiante' => 'bi-mortarboard-fill',
    'tutor' => 'bi-person-badge-fill',
    'administrador' => 'bi-person-gear',
];
$profileIcon = $profileIcons[$role] ?? 'bi-person-fill';
$profilePhotoUrl = !empty($authUser['foto_perfil']) && in_array($role, ['tutor', 'estudiante'], true)
    ? app_url('mi-perfil/foto.php?v=' . rawurlencode((string) $authUser['foto_perfil']))
    : null;
$appCssVersion = (string) (@filemtime(dirname(__DIR__, 2) . '/css/app.css') ?: '1');
?><!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('tecnologiasweb-theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme === 'dark' || savedTheme === 'light' ? savedTheme : systemTheme);
        })();
    </script>
    <link rel="stylesheet" href="<?= e(app_url('css/vendor/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(app_url('css/vendor/bootstrap-icons.min.css')) ?>">
    <link rel="icon" href="<?= e(app_url('img/logo2.png')) ?>" type="image/png">
    <link rel="stylesheet" href="<?= e(app_url('css/app.css?v=' . $appCssVersion)) ?>">
</head>
<body class="<?= $isAuthenticated ? 'app-body' : 'auth-body' ?>" data-request-method="<?= e($_SERVER['REQUEST_METHOD'] ?? 'GET') ?>">
<?php if ($isAuthenticated): ?>
    <div class="app-shell" data-app-shell>
        <aside class="sidebar" id="sidebar" data-sidebar>
            <div class="sidebar-brand">
                <img class="brand-logo" src="<?= e(app_url('img/logo2.png')) ?>" alt="Universidad Privada Domingo Savio">
                <div class="sidebar-product">
                    <strong>Tutoria</strong>
                    <span>Apoyo academico</span>
                </div>
                <button class="sidebar-collapse-toggle" type="button" data-sidebar-collapse aria-expanded="true" aria-label="Minimizar navegacion" title="Minimizar navegacion">
                    <i class="bi bi-layout-sidebar-inset" data-sidebar-collapse-icon aria-hidden="true"></i>
                </button>
            </div>

            <nav class="sidebar-nav" aria-label="Navegacion principal">
                <span class="nav-label">Workspace</span>
                <?php if (Auth::can('dashboard')): ?>
                    <a class="nav-link <?= $activePage === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(app_url('dashboard.php')) ?>">
                        <i class="bi bi-grid-1x2-fill nav-icon" aria-hidden="true"></i>
                        <span>Resumen</span>
                    </a>
                <?php endif; ?>

                <?php if (($authUser['nombre_rol'] ?? '') === 'administrador'): ?>
                    <span class="nav-label">Administracion</span>
                    <a class="nav-link <?= $activePage === 'usuarios' ? 'is-active' : '' ?>" href="<?= e(app_url('usuarios/')) ?>">
                        <i class="bi bi-person-vcard-fill nav-icon" aria-hidden="true"></i>
                        <span>Cuentas de acceso</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'roles' ? 'is-active' : '' ?>" href="<?= e(app_url('roles/')) ?>">
                        <i class="bi bi-shield-check nav-icon" aria-hidden="true"></i>
                        <span>Roles</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'permisos' ? 'is-active' : '' ?>" href="<?= e(app_url('permisos/')) ?>">
                        <i class="bi bi-key-fill nav-icon" aria-hidden="true"></i>
                        <span>Permisos</span>
                    </a>
                    <span class="nav-label">Catalogo academico</span>
                    <a class="nav-link <?= $activePage === 'carreras' ? 'is-active' : '' ?>" href="<?= e(app_url('carreras/')) ?>">
                        <i class="bi bi-diagram-3-fill nav-icon" aria-hidden="true"></i>
                        <span>Carreras</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'materias' ? 'is-active' : '' ?>" href="<?= e(app_url('materias/')) ?>">
                        <i class="bi bi-journal-bookmark-fill nav-icon" aria-hidden="true"></i>
                        <span>Materias</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'tipos-tutoria' ? 'is-active' : '' ?>" href="<?= e(app_url('tipos-tutoria/')) ?>">
                        <i class="bi bi-tags-fill nav-icon" aria-hidden="true"></i>
                        <span>Tipos de Tutoría</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'periodos' ? 'is-active' : '' ?>" href="<?= e(app_url('periodos/')) ?>">
                        <i class="bi bi-calendar3 nav-icon" aria-hidden="true"></i>
                        <span>Periodos</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'turnos' ? 'is-active' : '' ?>" href="<?= e(app_url('turnos/')) ?>">
                        <i class="bi bi-clock-history nav-icon" aria-hidden="true"></i>
                        <span>Turnos</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'aulas' ? 'is-active' : '' ?>" href="<?= e(app_url('aulas/')) ?>">
                        <i class="bi bi-building nav-icon" aria-hidden="true"></i>
                        <span>Aulas</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'ofertas' ? 'is-active' : '' ?>" href="<?= e(app_url('ofertas/')) ?>">
                        <i class="bi bi-megaphone-fill nav-icon" aria-hidden="true"></i>
                        <span>Ofertas academicas</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'solicitudes-tutor' ? 'is-active' : '' ?>" href="<?= e(app_url('solicitudes-tutor/')) ?>">
                        <i class="bi bi-person-check-fill nav-icon" aria-hidden="true"></i>
                        <span>Solicitudes</span>
                    </a>
                    <span class="nav-label">Perfiles academicos</span>
                    <a class="nav-link <?= $activePage === 'estudiantes' ? 'is-active' : '' ?>" href="<?= e(app_url('estudiantes/')) ?>">
                        <i class="bi bi-mortarboard-fill nav-icon" aria-hidden="true"></i>
                        <span>Estudiantes</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'tutores' ? 'is-active' : '' ?>" href="<?= e(app_url('tutores/')) ?>">
                        <i class="bi bi-person-badge-fill nav-icon" aria-hidden="true"></i>
                        <span>Tutores</span>
                    </a>
                    <span class="nav-label">Operacion</span>
                    <a class="nav-link <?= $activePage === 'tutorias' ? 'is-active' : '' ?>" href="<?= e(app_url('tutorias/')) ?>">
                        <i class="bi bi-calendar2-check-fill nav-icon" aria-hidden="true"></i>
                        <span>Tutorias</span>
                    </a>
                    <a class="nav-link <?= $activePage === 'evaluaciones' ? 'is-active' : '' ?>" href="<?= e(app_url('evaluaciones/')) ?>">
                        <i class="bi bi-star-fill nav-icon" aria-hidden="true"></i>
                        <span>Reportes</span>
                    </a>
                    <span class="nav-label">Auditoria</span>
                    <a class="nav-link <?= $activePage === 'accesos' ? 'is-active' : '' ?>" href="<?= e(app_url('accesos/')) ?>">
                        <i class="bi bi-clock-history nav-icon" aria-hidden="true"></i>
                        <span>Registro de accesos</span>
                    </a>
                <?php endif; ?>
                <?php if (Auth::can('modalidades-grado')): ?>
                    <span class="nav-label">Modalidades de Grado</span>
                    <a class="nav-link <?= $activePage === 'modalidades-grado' ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/')) ?>">
                        <i class="bi bi-mortarboard nav-icon" aria-hidden="true"></i>
                        <span>Modalidades de Grado</span>
                    </a>
                <?php endif; ?>
                <?php if (in_array($role, ['tutor', 'estudiante'], true)): ?>
                    <span class="nav-label">Mi espacio</span>
                    <a class="nav-link <?= $activePage === 'mi-perfil' ? 'is-active' : '' ?>" href="<?= e(app_url('mi-perfil/')) ?>">
                        <i class="bi bi-person-circle nav-icon" aria-hidden="true"></i>
                        <span>Mi perfil</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'tutor'): ?>
                    <?php if (Auth::can('ofertas')): ?><a class="nav-link <?= $activePage === 'materias-ofertadas' ? 'is-active' : '' ?>" href="<?= e(app_url('materias-ofertadas/')) ?>">
                        <i class="bi bi-journal-bookmark-fill nav-icon" aria-hidden="true"></i>
                        <span>Materias ofertadas</span>
                    </a><?php endif; ?>
                    <span class="nav-label">Operacion</span>
                <?php endif; ?>
                <?php if ($role === 'tutor' && Auth::can('ofertas')): ?>
                    <a class="nav-link <?= $activePage === 'disponibilidad' ? 'is-active' : '' ?>" href="<?= e(app_url('disponibilidad/')) ?>">
                        <i class="bi bi-calendar-week-fill nav-icon" aria-hidden="true"></i>
                        <span>Disponibilidad</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'estudiante'): ?>
                    <?php if (Auth::can('ofertas')): ?><a class="nav-link <?= $activePage === 'materias-disponibles' ? 'is-active' : '' ?>" href="<?= e(app_url('materias-disponibles/')) ?>">
                        <i class="bi bi-journal-bookmark-fill nav-icon" aria-hidden="true"></i>
                        <span>Materias disponibles</span>
                    </a><?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($role, ['tutor', 'estudiante'], true) && Auth::can('tutorias')): ?>
                    <a class="nav-link <?= $activePage === 'tutorias' ? 'is-active' : '' ?>" href="<?= e(app_url('tutorias/')) ?>">
                        <i class="bi bi-calendar2-check-fill nav-icon" aria-hidden="true"></i>
                        <span><?= $role === 'estudiante' ? 'Mis tutorias' : 'Tutorias' ?></span>
                    </a>
                    <?php if (Auth::can('evaluaciones')): ?><a class="nav-link <?= $activePage === 'evaluaciones' ? 'is-active' : '' ?>" href="<?= e(app_url('evaluaciones/')) ?>">
                        <i class="bi bi-star-fill nav-icon" aria-hidden="true"></i>
                        <span>Evaluaciones</span>
                    </a><?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <span class="avatar avatar-small"><?php if ($profilePhotoUrl !== null): ?><img src="<?= e($profilePhotoUrl) ?>" alt="" width="34" height="34"><?php else: ?><i class="bi <?= e($profileIcon) ?>" aria-hidden="true"></i><?php endif; ?></span>
                    <div>
                        <strong><?= e($authUser['nombre'] ?? 'Usuario') ?></strong>
                        <span><?= e($authUser['nombre_rol'] ?? '') ?></span>
                    </div>
                </div>
                <form class="logout-form" method="post" action="<?= e(app_url('logout.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button class="nav-link logout-link" type="submit">
                        <i class="bi bi-box-arrow-right nav-icon" aria-hidden="true"></i>
                        <span>Cerrar sesion</span>
                    </button>
                </form>
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
                <div class="topbar-actions">
                    <button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Activar modo oscuro" title="Activar modo oscuro">
                        <i class="bi bi-moon-stars-fill theme-toggle-icon" data-theme-icon aria-hidden="true"></i>
                    </button>
                <div class="topbar-user">
                    <span class="avatar"><?php if ($profilePhotoUrl !== null): ?><img src="<?= e($profilePhotoUrl) ?>" alt="" width="38" height="38"><?php else: ?><i class="bi <?= e($profileIcon) ?>" aria-hidden="true"></i><?php endif; ?></span>
                    <div>
                        <strong><?= e(($authUser['nombre'] ?? '') . ' ' . ($authUser['apellido'] ?? '')) ?></strong>
                        <span><?= e($authUser['nombre_rol'] ?? '') ?></span>
                    </div>
                </div>
                </div>
            </header>
<?php else: ?>
    <button class="theme-toggle theme-toggle-floating" type="button" data-theme-toggle aria-pressed="false" aria-label="Activar modo oscuro" title="Activar modo oscuro">
        <i class="bi bi-moon-stars-fill theme-toggle-icon" data-theme-icon aria-hidden="true"></i>
    </button>
<?php endif; ?>
