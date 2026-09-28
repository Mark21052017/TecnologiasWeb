<?php

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
Auth::requireModule('modalidades-grado');
requerirRol('administrador');
requerirPermiso('mg.academico.importar');

$controller = new MgAcademicoController();
if (isset($_GET['plantilla'])) {
    $controller->template((string) $_GET['plantilla']);
}

$errors = [];
$message = flash_get('mg_academic_message');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_validar();
    $userId = (int) Auth::user()['id_usuario'];
    try {
        $action = (string) ($_POST['accion'] ?? 'importar');
        if ($action === 'revisar') {
            $controller->review((int) ($_POST['id_importacion'] ?? 0), $userId, (string) ($_POST['decision'] ?? ''), (string) ($_POST['observacion'] ?? ''));
            flash_set('mg_academic_message', 'La importación fue revisada y su decisión quedó registrada.');
        } else {
            $id = $controller->import($_FILES['archivo'] ?? [], $userId, (string) ($_POST['tipo'] ?? ''));
            flash_set('mg_academic_message', 'Archivo cargado. Revise las filas antes de aprobarlo. Importación #' . $id . '.');
        }
        header('Location: ' . app_url('modalidades-grado/academico.php'), true, 303);
        exit;
    } catch (Throwable $exception) {
        $errors[] = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo procesar la importación.';
        if (!$exception instanceof RuntimeException) {
            error_log($exception->getMessage());
        }
    }
}

$imports = $controller->imports();
$selectedId = (int) ($_GET['id'] ?? 0);
$selectedRows = $selectedId > 0 ? $controller->rows($selectedId) : [];
$title = 'Importación académica MG';
$activePage = 'mg-configuracion';
require dirname(__DIR__, 2) . '/views/mg/academico.php';
