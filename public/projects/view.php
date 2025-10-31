<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';
require_once '../../includes/task_functions.php';
require_once '../../includes/project_permissions_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$project_id = $_GET['id'] ?? null;
if (!$project_id) {
    redirect(BASE_URL . '/projects/index.php');
}

$projectManager = new ProjectManager();
$project = $projectManager->getProject($project_id, $_SESSION['usuario']['id']);

if (!$project) {
    redirect(BASE_URL . '/projects/index.php');
}

$permissionsManager = new ProjectPermissionsManager();

// Verificar se usuário tem acesso ao projeto
if (!$permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'ver_projeto')) {
    $_SESSION['error'] = 'Você não tem permissão para acessar este projeto.';
    redirect(BASE_URL . '/projects/index.php');
}

$taskManager = new TaskManager();
$tasks = $taskManager->getProjectTasks($project_id, $_SESSION['usuario']['id']);

// Verificar se o usuário é dono ou membro convidado
$is_owner = ($project['usuario_id'] == $_SESSION['usuario']['id']);
$user_permissions = [
    'gerenciar_membros' => $permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'gerenciar_membros'),
    'criar_tarefas' => $permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'criar_tarefas'),
    'editar_tarefas' => $permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'editar_tarefas')
];

// Estatísticas
$total_tasks = count($tasks);
$completed_tasks = array_filter($tasks, function($task) {
    return $task['status'] === 'done';
});
$pending_tasks = array_filter($tasks, function($task) {
    return $task['status'] !== 'done';
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $project['nome'] ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="project-header">
            <div>
                <h1><?= htmlspecialchars($project['nome']) ?></h1>
                <p style="color: #666; margin-top: 5px;"><?= htmlspecialchars($project['descricao'] ?? 'Sem descrição') ?></p>
                <?php if (!$is_owner): ?>
                    <div class="project-role-badge">
                        <span class="role-indicator">👤 Projeto Convidado</span>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <a href="../kanban/board.php?project=<?= $project_id ?>" class="btn btn-primary">📊 Kanban</a>
                
                <?php if ($user_permissions['gerenciar_membros']): ?>
                    <a href="members.php?project=<?= $project_id ?>" class="btn">👥 Gerenciar Membros</a>
                <?php endif; ?>
                
                <?php if ($is_owner): ?>
                    <a href="edit.php?id=<?= $project_id ?>" class="btn">✏️ Editar</a>
                <?php endif; ?>
                
                <?php if ($user_permissions['criar_tarefas']): ?>
                    <a href="../tasks/create.php?project=<?= $project_id ?>" class="btn">➕ Nova Tarefa</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total de Tarefas</h3>
                <span class="stat-number"><?= $total_tasks ?></span>
            </div>
            <div class="stat-card">
                <h3>Concluídas</h3>
                <span class="stat-number"><?= count($completed_tasks) ?></span>
            </div>
            <div class="stat-card">
                <h3>Pendentes</h3>
                <span class="stat-number"><?= count($pending_tasks) ?></span>
            </div>
            <div class="stat-card">
                <h3>Progresso</h3>
                <span class="stat-number">
                    <?= $total_tasks > 0 ? round((count($completed_tasks) / $total_tasks) * 100) : 0 ?>%
                </span>
            </div>
        </div>
        
        <div class="tasks-section">
            <div class="section-header">
                <h2>Tarefas do Projeto</h2>
                <?php if ($user_permissions['criar_tarefas']): ?>
                    <a href="../tasks/create.php?project=<?= $project_id ?>" class="btn btn-primary">+ Nova Tarefa</a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <h3>Nenhuma tarefa encontrada</h3>
                    <p>Este projeto ainda não possui tarefas.</p>
                    <?php if ($user_permissions['criar_tarefas']): ?>
                        <a href="../tasks/create.php?project=<?= $project_id ?>" class="btn btn-primary">Criar Primeira Tarefa</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="tasks-list">
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item">
                            <div class="task-main">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['titulo']) ?></h4>
                                    <span class="task-priority priority-<?= $task['prioridade'] ?>">
                                        <?= ucfirst($task['prioridade']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($task['descricao'])): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['descricao']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="task-meta">
                                <span class="task-status status-<?= $task['status'] ?>">
                                    <?= ucfirst($task['status']) ?>
                                </span>
                                <?php if ($task['data_limite']): ?>
                                    <span class="task-deadline">
                                        📅 <?= date('d/m/Y', strtotime($task['data_limite'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }
        .project-role-badge {
            margin-top: 10px;
        }
        .role-indicator {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .tasks-section {
            margin-top: 40px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }
        .tasks-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .task-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .task-title {
            margin: 0;
            font-size: 16px;
        }
        .task-priority {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .priority-alta { background: #ffebee; color: #c62828; }
        .priority-media { background: #fff3e0; color: #ef6c00; }
        .priority-baixa { background: #e8f5e8; color: #2e7d32; }
        .priority-urgente { background: #fce4ec; color: #ad1457; }
        .task-description {
            color: #666;
            margin: 8px 0;
            font-size: 14px;
        }
        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        .task-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-backlog { background: #f5f5f5; color: #666; }
        .status-todo { background: #e3f2fd; color: #1565c0; }
        .status-doing { background: #fff3e0; color: #ef6c00; }
        .status-done { background: #e8f5e8; color: #2e7d32; }
        .task-deadline {
            font-size: 12px;
            color: #666;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>