<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="container profile-page">
    <div class="page-heading profile-heading">
        <div>
            <span class="profile-title-icon"><i class="bi bi-person-badge-fill" aria-hidden="true"></i></span>
            <div><h1>Perfil del tutor</h1><p>Información profesional del tutor asignado a una materia ofertada.</p></div>
        </div>
        <a class="button secondary profile-back" href="<?= e(app_url('materias-disponibles/')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a materias</a>
    </div>

    <section class="card profile-summary public-tutor-profile">
        <div class="profile-photo-frame"><span><?= e($initials) ?></span></div>
        <h2><?= e($profile['nombre'] . ' ' . $profile['apellido']) ?></h2>
        <span class="profile-role">Docente tutor</span>
        <div class="profile-section-divider"></div>
        <div class="profile-section-heading"><i class="bi bi-mortarboard" aria-hidden="true"></i><div><h2>Información profesional</h2></div></div>
        <p><strong>Especialidad:</strong> <?= e($profile['especialidad'] ?: 'No especificada') ?></p>
        <?php if (!empty($profile['biografia'])): ?><p><?= nl2br(e($profile['biografia'])) ?></p><?php else: ?><p class="table-muted">El tutor aún no ha agregado una biografía.</p><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
