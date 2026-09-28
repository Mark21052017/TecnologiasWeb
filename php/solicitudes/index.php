<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('solicitudes_apertura');
requerirRol(['administrador', 'estudiante']);

$user = Auth::user();
$role = (string) $user['nombre_rol'];
$controller = new SolicitudesAperturaController();
$errors = [];
$message = match ((string) ($_GET['message'] ?? '')) {
    'created' => 'Solicitud registrada. Puedes consultar aquí su estado.',
    'approved' => 'Solicitud aprobada y oferta pendiente generada o vinculada.',
    'rejected' => 'Solicitud rechazada.',
    default => null,
};
$error = (string) ($_GET['error'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    if ($role === 'administrador') {
        $reviewError = $controller->review($_POST, (int) $user['id_usuario']);
        if ($reviewError !== null) {
            $errors[] = $reviewError;
        } else {
            $state = (string) ($_POST['estado'] ?? '');
            header('Location: ' . app_url('solicitudes/?message=' . ($state === 'aprobada' ? 'approved' : 'rejected')), true, 303);
            exit;
        }
    } else {
        $studentId = (new Tutoria())->studentIdByUserId((int) $user['id_usuario']);
        if ($studentId === null) {
            $errors[] = 'Tu cuenta todavía no tiene un perfil académico de estudiante.';
        } else {
            $requestError = $controller->submit($studentId, $_POST);
            if ($requestError !== null) {
                $errors[] = $requestError;
            } else {
                header('Location: ' . app_url('solicitudes/?message=created'), true, 303);
                exit;
            }
        }
    }
}

$periods = [];
$turns = [];
$subjects = [];
$requests = [];
$studentId = null;
$selectedPeriodId = filter_var($_POST['id_periodo'] ?? $_GET['id_periodo'] ?? null, FILTER_VALIDATE_INT);
$selectedTurnId = filter_var($_POST['id_turno'] ?? $_GET['id_turno'] ?? null, FILTER_VALIDATE_INT);
$selectedPeriodId = $selectedPeriodId !== false && $selectedPeriodId !== null ? (int) $selectedPeriodId : 0;
$selectedTurnId = $selectedTurnId !== false && $selectedTurnId !== null ? (int) $selectedTurnId : 0;

if ($role === 'administrador') {
    $requests = $controller->allForReview();
} else {
    $studentId = (new Tutoria())->studentIdByUserId((int) $user['id_usuario']);
    $periods = $controller->futurePeriods();
    $turns = $controller->activeTurns();
    if ($studentId !== null) {
        $requests = $controller->ownRequests($studentId);
        $periodIsAvailable = (bool) array_filter($periods, static fn (array $period): bool => (int) $period['id_periodo'] === $selectedPeriodId);
        $turnIsAvailable = (bool) array_filter($turns, static fn (array $turn): bool => (int) $turn['id_turno'] === $selectedTurnId);
        if ($periodIsAvailable && $turnIsAvailable) {
            $subjects = $controller->availableSubjects($studentId, $selectedPeriodId, $selectedTurnId);
        }
    }
}

$title = $role === 'administrador' ? 'Solicitudes de apertura' : 'Solicitudes de materias';
$activePage = 'solicitudes';
require dirname(__DIR__, 2) . '/views/solicitudes/index.php';
