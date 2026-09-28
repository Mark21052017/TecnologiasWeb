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
$unreadNotifications = $isAuthenticated ? (new NotificacionesController())->unreadCount((int) $authUser['id_usuario']) : 0;
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
                    <a class="nav-link <?= $activePage === 'solicitudes' ? 'is-active' : '' ?>" href="<?= e(app_url('solicitudes/')) ?>">
                        <i class="bi bi-person-check-fill nav-icon" aria-hidden="true"></i>
                        <span>Solicitudes de materias</span>
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
                <?php if (Auth::can('modalidades-grado') && in_array($role, ['administrador', 'coordinador_mg', 'auxiliar_mg'], true)): ?>
                    <span class="nav-label">Modalidades de Grado</span>
                    <a class="nav-link <?= $activePage === 'mg-resumen' ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/')) ?>">
                        <i class="bi bi-grid-1x2-fill nav-icon" aria-hidden="true"></i>
                        <span>Resumen</span>
                    </a>
                    <?php if ($role === 'administrador'): ?>
                        <a class="nav-link <?= $activePage === 'mg-solicitudes' ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/solicitudes.php')) ?>"><i class="bi bi-inbox-fill nav-icon" aria-hidden="true"></i><span>Solicitudes</span></a>
                    <?php endif; ?>
                    <a class="nav-link <?= in_array($activePage, ['mg-cohortes', 'mg-calendario'], true) ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/cohortes.php')) ?>"><i class="bi bi-people-fill nav-icon" aria-hidden="true"></i><span>Cohortes</span></a>
                    <?php if ($role === 'administrador'): ?>
                        <a class="nav-link <?= $activePage === 'mg-seguimiento' ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/seguimiento.php')) ?>"><i class="bi bi-activity nav-icon" aria-hidden="true"></i><span>Seguimiento</span></a>
                        <a class="nav-link <?= $activePage === 'mg-defensas' ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/defensas.php')) ?>"><i class="bi bi-mortarboard-fill nav-icon" aria-hidden="true"></i><span>Defensas</span></a>
                    <?php endif; ?>
                    <?php if (in_array($role, ['administrador', 'coordinador_mg'], true)): ?><a class="nav-link <?= in_array($activePage, ['mg-configuracion','mg-parametros','mg-modalidades','mg-academico','mg-planes-estudiante','mg-oferta-carreras'], true) ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/configuracion.php')) ?>"><i class="bi bi-sliders nav-icon" aria-hidden="true"></i><span>Configuración</span></a><?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($role, ['tutor', 'estudiante'], true)): ?>
                    <span class="nav-label">Mi espacio</span>
                    <a class="nav-link <?= $activePage === 'mi-perfil' ? 'is-active' : '' ?>" href="<?= e(app_url('mi-perfil/')) ?>">
                        <i class="bi bi-person-circle nav-icon" aria-hidden="true"></i>
                        <span>Mi perfil</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'tutor'): ?>
                    <span class="nav-label">Operacion</span>
                    <?php if (Auth::can('ofertas')): ?><a class="nav-link <?= $activePage === 'materias-ofertadas' ? 'is-active' : '' ?>" href="<?= e(app_url('materias-ofertadas/')) ?>">
                        <i class="bi bi-journal-bookmark-fill nav-icon" aria-hidden="true"></i>
                        <span>Materias ofertadas</span>
                    </a><?php endif; ?>
                <?php endif; ?>
                <?php if ($role === 'estudiante'): ?>
                    <?php if (Auth::can('ofertas')): ?><a class="nav-link <?= $activePage === 'materias-disponibles' ? 'is-active' : '' ?>" href="<?= e(app_url('materias-disponibles/')) ?>">
                        <i class="bi bi-journal-bookmark-fill nav-icon" aria-hidden="true"></i>
                        <span>Materias disponibles</span>
                    </a><?php endif; ?>
                    <?php if (Auth::can('solicitudes_apertura')): ?><a class="nav-link <?= $activePage === 'solicitudes' ? 'is-active' : '' ?>" href="<?= e(app_url('solicitudes/')) ?>">
                        <i class="bi bi-inbox nav-icon" aria-hidden="true"></i>
                        <span>Solicitudes de materias</span>
                    </a><?php endif; ?>
                <?php endif; ?>
                <?php if ($role === 'tutor' && Auth::can('tutorias')): ?>
                    <a class="nav-link <?= $activePage === 'tutorias' ? 'is-active' : '' ?>" href="<?= e(app_url('tutorias/')) ?>">
                        <i class="bi bi-calendar2-check-fill nav-icon" aria-hidden="true"></i>
                        <span>Tutorias</span>
                    </a>
                <?php endif; ?>
                <?php if ($role === 'tutor' && Auth::can('modalidades-grado') && (new MgPermiso())->userHasPermission((int) $authUser['id_usuario'], 'mg.trabajos.propios')): ?>
                    <a class="nav-link <?= $activePage === 'mg-mis-trabajos' ? 'is-active' : '' ?>" href="<?= e(app_url('modalidades-grado/mis-trabajos.php')) ?>"><i class="bi bi-journal-text nav-icon" aria-hidden="true"></i><span>Mis trabajos de grado</span></a>
                <?php endif; ?>
                <?php if (in_array($role, ['tutor', 'estudiante'], true) && Auth::can('evaluaciones')): ?>
                    <a class="nav-link <?= $activePage === 'evaluaciones' ? 'is-active' : '' ?>" href="<?= e(app_url('evaluaciones/')) ?>">
                        <i class="bi bi-star-fill nav-icon" aria-hidden="true"></i>
                        <span><?= $role === 'estudiante' ? 'Calificaciones' : 'Evaluaciones' ?></span>
                    </a>
                <?php endif; ?>
                <?php if (in_array($role, ['tutor', 'estudiante'], true) && Auth::can('modalidades-grado')): ?>
                    <span class="nav-label">Modalidades de Grado</span>
                    <a class="nav-link <?= $activePage === ($role === 'estudiante' ? 'mg-solicitud' : 'mg-resumen') ? 'is-active' : '' ?>" href="<?= e(app_url($role === 'estudiante' ? 'modalidades-grado/mi-solicitud.php' : 'modalidades-grado/')) ?>">
                        <i class="bi bi-mortarboard nav-icon" aria-hidden="true"></i>
                        <span><?= $role === 'estudiante' ? 'Mi Modalidad de Grado' : 'Resumen' ?></span>
                    </a>
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
                    <a class="notification-toggle" href="<?= e(app_url('notificaciones/')) ?>" aria-label="Notificaciones<?= $unreadNotifications > 0 ? ', ' . $unreadNotifications . ' sin leer' : '' ?>" title="Notificaciones">
                        <i class="bi bi-bell-fill" aria-hidden="true"></i>
                        <?php if ($unreadNotifications > 0): ?><span class="notification-count" aria-hidden="true"><?= $unreadNotifications > 99 ? '99+' : (int) $unreadNotifications ?></span><?php endif; ?>
                    </a>
                    <button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="Activar modo oscuro" title="Activar modo oscuro">
                        <i class="bi bi-moon-stars-fill theme-toggle-icon" data-theme-icon aria-hidden="true"></i>
                    </button>
                <?php if (in_array($role, ['tutor', 'estudiante'], true)): ?>
                    <a class="topbar-user topbar-profile-link" href="<?= e(app_url('mi-perfil/')) ?>" aria-label="Abrir mi perfil" title="Mi perfil">
                        <span class="avatar"><?php if ($profilePhotoUrl !== null): ?><img src="<?= e($profilePhotoUrl) ?>" alt="" width="38" height="38"><?php else: ?><i class="bi <?= e($profileIcon) ?>" aria-hidden="true"></i><?php endif; ?></span>
                        <span class="topbar-profile-copy">
                            <strong><?= e(($authUser['nombre'] ?? '') . ' ' . ($authUser['apellido'] ?? '')) ?></strong>
                            <span><?= e($authUser['nombre_rol'] ?? '') ?></span>
                        </span>
                        <i class="bi bi-chevron-right topbar-profile-chevron" aria-hidden="true"></i>
                    </a>
                <?php else: ?>
                    <div class="topbar-user">
                        <span class="avatar"><i class="bi <?= e($profileIcon) ?>" aria-hidden="true"></i></span>
                        <div>
                            <strong><?= e(($authUser['nombre'] ?? '') . ' ' . ($authUser['apellido'] ?? '')) ?></strong>
                            <span><?= e($authUser['nombre_rol'] ?? '') ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
            </header>
<?php else: ?>
    <button class="theme-toggle theme-toggle-floating" type="button" data-theme-toggle aria-pressed="false" aria-label="Activar modo oscuro" title="Activar modo oscuro">
        <i class="bi bi-moon-stars-fill theme-toggle-icon" data-theme-icon aria-hidden="true"></i>
    </button>
<?php endif; ?>
