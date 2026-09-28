<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.defensas.gestionar');

$userId = (int) Auth::user()['id_usuario'];
$controller = new MgDefensasController();
$works = $controller->works();
$workId = (int) ($_GET['id_trabajo'] ?? $_POST['id_trabajo'] ?? 0);
if ($workId < 1 && $works) {
    $workId = (int) $works[0]['id_trabajo'];
}
$workIds = array_map(static fn(array $work): int => (int) $work['id_trabajo'], $works);
if ($workId > 0 && !in_array($workId, $workIds, true)) {
    http_response_code(404);
    exit('El trabajo no está disponible.');
}

$errors = [];
$message = flash_get('mg_defense_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    try {
        $action = (string) ($_POST['accion'] ?? '');
        if ($action === 'tribunal') {
            $controller->assignTribunal($workId, $userId, $_POST);
            flash_set('mg_defense_message', 'El tribunal quedó designado; los anteriores se conservan en su historial.');
        } elseif ($action === 'programar_defensa') {
            $number = $controller->schedule($workId, $userId, $_POST);
            flash_set('mg_defense_message', 'Defensa programada y auditada.');
        } elseif (in_array($action, ['reprogramar', 'resultado_defensa', 'cancelar_defensa'], true)) {
            $controller->updateDefense((int) ($_POST['id_defensa'] ?? 0), $userId, $_POST);
            flash_set('mg_defense_message', 'El cambio de defensa quedó auditado.');
        } elseif ($action === 'cerrar') {
            requerirPermiso('mg.cierre.gestionar');
            $controller->close($workId, $userId, $_POST);
            flash_set('mg_defense_message', 'El proceso quedó cerrado con su resultado final.');
        } else {
            throw new RuntimeException('Seleccione una acción válida.');
        }
        header('Location: ' . app_url('modalidades-grado/defensas.php?id_trabajo=' . $workId), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar la defensa o cierre.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$detail = $workId > 0 ? $controller->overview($workId) : null;
$candidates = $controller->candidates();
$allReady = $detail !== null && count(array_filter($detail['checklist'], static fn(array $item): bool => !$item['ok'])) === 0;
$title = 'Defensas y cierre MG';
$activePage = 'mg-defensas';
require dirname(__DIR__, 2) . '/views/mg/defensas.php';
