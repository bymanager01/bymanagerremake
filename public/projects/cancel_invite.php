<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/projects/index.php');
}

$invite_id = $_POST['invite_id'] ?? null;
$project_id = $_POST['project_id'] ?? null;

if (!$invite_id || !$project_id) {
    $_SESSION['error'] = 'Parâmetros inválidos.';
    redirect(BASE_URL . '/projects/index.php');
}

$db = new Database();
$pdo = $db->getConnection();

// Verificar permissões
$projectManager = new ProjectManager();
$project = $projectManager->getProject($project_id, $_SESSION['usuario']['id']);

if (!$project) {
    $_SESSION['error'] = 'Projeto não encontrado.';
    redirect(BASE_URL . '/projects/index.php');
}

$permissionsManager = new ProjectPermissionsManager();
if (!$permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'gerenciar_membros')) {
    $_SESSION['error'] = 'Você não tem permissão para cancelar convites deste projeto.';
    redirect(BASE_URL . '/projects/view.php?id=' . $project_id);
}

// Cancelar convite
$stmt = $pdo->prepare("UPDATE convites_projeto SET status = 'expirado' WHERE id = ?");
$stmt->execute([$invite_id]);

$_SESSION['success'] = 'Convite cancelado com sucesso.';
redirect(BASE_URL . '/projects/invite.php?project=' . $project_id);
?>