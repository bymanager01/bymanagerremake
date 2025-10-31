<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/task_functions.php';
require_once '../../includes/project_functions.php';
require_once '../../includes/project_permissions_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$task_id = $_GET['id'] ?? null;
if (!$task_id) {
    redirect(BASE_URL . '/tasks/index.php');
}

$taskManager = new TaskManager();
$task = $taskManager->getTask($task_id, $_SESSION['usuario']['id']);

if (!$task) {
    $_SESSION['error'] = 'Tarefa não encontrada ou você não tem permissão para acessá-la.';
    redirect(BASE_URL . '/tasks/index.php');
}

$permissionsManager = new ProjectPermissionsManager();
$can_edit = $permissionsManager->checkPermission($task['projeto_id'], $_SESSION['usuario']['id'], 'editar_tarefas') 
            || $task['usuario_id'] == $_SESSION['usuario']['id'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($task['titulo']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($task['titulo']) ?></h1>
                <p>Projeto: <a href="../projects/view.php?id=<?= $task['projeto_id'] ?>"><?= htmlspecialchars($task['projeto_nome']) ?></a></p>
            </div>
            <div>
                <a href="index.php" class="btn">← Voltar para Tarefas</a>
                <?php if ($can_edit): ?>
                    <a href="edit.php?id=<?= $task_id ?>" class="btn btn-primary">✏️ Editar Tarefa</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="task-details">
            <div class="task-main-info">
                <div class="task-header">
                    <div class="task-title-section">
                        <h2><?= htmlspecialchars($task['titulo']) ?></h2>
                        <div class="task-meta-badges">
                            <span class="task-priority priority-<?= $task['prioridade'] ?>">
                                <?= ucfirst($task['prioridade']) ?>
                            </span>
                            <span class="task-status status-<?= $task['status'] ?>">
                                <?= ucfirst($task['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($task['descricao'])): ?>
                    <div class="task-description-section">
                        <h3>Descrição</h3>
                        <div class="description-content">
                            <?= nl2br(htmlspecialchars($task['descricao'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="task-sidebar">
                <div class="task-info-card">
                    <h3>Informações da Tarefa</h3>
                    
                    <div class="info-item">
                        <strong>Projeto:</strong>
                        <span>
                            <a href="../projects/view.php?id=<?= $task['projeto_id'] ?>">
                                <?= htmlspecialchars($task['projeto_nome']) ?>
                            </a>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Criado por:</strong>
                        <span><?= htmlspecialchars($task['criador_nome']) ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Data de Criação:</strong>
                        <span><?= date('d/m/Y H:i', strtotime($task['data_criacao'])) ?></span>
                    </div>
                    
                    <?php if ($task['data_limite']): ?>
                    <div class="info-item">
                        <strong>Data Limite:</strong>
                        <span class="deadline-date"><?= date('d/m/Y', strtotime($task['data_limite'])) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <strong>Prioridade:</strong>
                        <span class="priority-badge priority-<?= $task['prioridade'] ?>">
                            <?= ucfirst($task['prioridade']) ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Status:</strong>
                        <span class="status-badge status-<?= $task['status'] ?>">
                            <?= ucfirst($task['status']) ?>
                        </span>
                    </div>
                </div>

                <?php if ($can_edit): ?>
                <div class="task-actions">
                    <a href="edit.php?id=<?= $task_id ?>" class="btn btn-primary btn-block">✏️ Editar Tarefa</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }
        .task-details {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
        }
        .task-main-info {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .task-header {
            margin-bottom: 25px;
        }
        .task-title-section h2 {
            margin: 0 0 15px 0;
            color: var(--dark);
            font-size: 24px;
        }
        .task-meta-badges {
            display: flex;
            gap: 10px;
        }
        .task-priority, .task-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .priority-baixa { background: #e8f5e8; color: #2e7d32; }
        .priority-media { background: #fff3e0; color: #ef6c00; }
        .priority-alta { background: #ffebee; color: #c62828; }
        .priority-urgente { background: #fce4ec; color: #ad1457; }
        .status-backlog { background: #f5f5f5; color: #666; }
        .status-todo { background: #e3f2fd; color: #1565c0; }
        .status-doing { background: #fff3e0; color: #ef6c00; }
        .status-done { background: #e8f5e8; color: #2e7d32; }
        .task-description-section h3 {
            margin: 0 0 15px 0;
            color: var(--dark);
            font-size: 18px;
        }
        .description-content {
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        .task-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .task-info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .task-info-card h3 {
            margin: 0 0 20px 0;
            color: var(--dark);
            font-size: 18px;
            border-bottom: 1px solid #e1e1e1;
            padding-bottom: 10px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-item strong {
            color: #666;
        }
        .priority-badge, .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .deadline-date {
            font-weight: bold;
            color: #e74c3c;
        }
        .task-actions {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
        }
        @media (max-width: 768px) {
            .task-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>