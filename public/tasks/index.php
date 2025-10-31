<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/task_functions.php';
require_once '../../includes/project_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$taskManager = new TaskManager();
$projectManager = new ProjectManager();

// Obter filtros
$filters = [];
if (isset($_GET['projeto_id']) && !empty($_GET['projeto_id'])) {
    $filters['projeto_id'] = $_GET['projeto_id'];
}
if (isset($_GET['prioridade']) && !empty($_GET['prioridade'])) {
    $filters['prioridade'] = $_GET['prioridade'];
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

// Obter tarefas do usuário com filtros
$tasks = $taskManager->getUserTasksWithFilters($_SESSION['usuario']['id'], $filters);

// Obter projetos do usuário para o filtro
$myProjects = $projectManager->getUserProjects($_SESSION['usuario']['id']);
$invitedProjects = $projectManager->getInvitedProjects($_SESSION['usuario']['id']);
$allProjects = array_merge($myProjects, $invitedProjects);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Tarefas - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="section-header">
            <h1>Minhas Tarefas</h1>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <h2>Filtros</h2>
            <form method="GET" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="projeto_id">Projeto</label>
                        <select id="projeto_id" name="projeto_id">
                            <option value="">Todos os projetos</option>
                            <?php foreach ($allProjects as $project): ?>
                                <option value="<?= $project['id'] ?>" 
                                    <?= isset($_GET['projeto_id']) && $_GET['projeto_id'] == $project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade">
                            <option value="">Todas</option>
                            <option value="baixa" <?= isset($_GET['prioridade']) && $_GET['prioridade'] == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= isset($_GET['prioridade']) && $_GET['prioridade'] == 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= isset($_GET['prioridade']) && $_GET['prioridade'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                            <option value="urgente" <?= isset($_GET['prioridade']) && $_GET['prioridade'] == 'urgente' ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos</option>
                            <option value="backlog" <?= isset($_GET['status']) && $_GET['status'] == 'backlog' ? 'selected' : '' ?>>Backlog</option>
                            <option value="todo" <?= isset($_GET['status']) && $_GET['status'] == 'todo' ? 'selected' : '' ?>>A Fazer</option>
                            <option value="doing" <?= isset($_GET['status']) && $_GET['status'] == 'doing' ? 'selected' : '' ?>>Em Progresso</option>
                            <option value="done" <?= isset($_GET['status']) && $_GET['status'] == 'done' ? 'selected' : '' ?>>Concluído</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="index.php" class="btn">Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista de Tarefas -->
        <div class="tasks-section">
            <h2>Tarefas (<?= count($tasks) ?>)</h2>
            
            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <h3>Nenhuma tarefa encontrada</h3>
                    <p>Não há tarefas correspondentes aos filtros selecionados.</p>
                </div>
            <?php else: ?>
                <div class="tasks-list">
                    <?php foreach ($tasks as $task): ?>
              <div class="task-item">
    <div class="task-main">
        <div class="task-header">
            <h4 class="task-title">
                <a href="view.php?id=<?= $task['id'] ?>" class="task-title-link">
                    <?= htmlspecialchars($task['titulo']) ?>
                </a>
            </h4>
            <div class="task-badges">
                <span class="task-priority priority-<?= $task['prioridade'] ?>">
                    <?= ucfirst($task['prioridade']) ?>
                </span>
                <span class="project-badge" style="background: <?= $task['projeto_cor'] ?>; color: white;">
                    <?= htmlspecialchars($task['projeto_nome']) ?>
                </span>
            </div>
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
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-form .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .tasks-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tasks-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .task-item {
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e1e1e1;
            transition: all 0.3s ease;
        }
        .task-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            flex: 1;
        }
        .task-badges {
            display: flex;
            gap: 8px;
        }
        .task-priority {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .priority-baixa { background: #e8f5e8; color: #2e7d32; }
        .priority-media { background: #fff3e0; color: #ef6c00; }
        .priority-alta { background: #ffebee; color: #c62828; }
        .priority-urgente { background: #fce4ec; color: #ad1457; }
        .project-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
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
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
    </style>
</body>
</html>