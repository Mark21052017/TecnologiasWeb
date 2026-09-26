<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireRole('administrador');
Auth::requireModule('tutorias');

$id = filter_var($_GET['id_inscripcion'] ?? null, FILTER_VALIDATE_INT);
$model = new InscripcionTutoria();
$detail = $id ? $model->detailForAdmin($id) : null;
if (!$detail) {
    header('Location: ' . app_url('tutorias/?error=' . rawurlencode('Inscripcion no valida.')), true, 303);
    exit;
}

$title = 'Estudiantes inscritos';
$activePage = 'tutorias';
$students = $model->studentsForDetail($id);
$availableSeats = max(0, (int) $detail['cupo'] - (int) $detail['inscritos']);

require dirname(__DIR__, 2) . '/views/tutorias/detalle.php';
