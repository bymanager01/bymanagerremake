<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';
require_once '../../includes/task_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$project_id = $_GET['project'] ?? null;
if (!$project_id) {
    redirect(BASE_URL . '/home.php');
}

$projectManager = new ProjectManager();
$project = $projectManager->getProject($project_id, $_SESSION['usuario']['id']);

if (!$project) {
    redirect(BASE_URL . '/home.php');
}

$taskManager = new TaskManager();
$tasks = $taskManager->getTasksByStatus($project_id, $_SESSION['usuario']['id']);

// Se for uma requisição AJAX para atualizar o status da tarefa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_task_status') {
        $task_id = $_POST['task_id'] ?? null;
        $new_status = $_POST['new_status'] ?? null;
        $new_order = $_POST['new_order'] ?? 0;
        
        if ($task_id && $new_status) {
            $result = $taskManager->updateStatusAndOrder($task_id, $_SESSION['usuario']['id'], $new_status, $new_order);
            echo json_encode($result);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban - <?= $project['nome'] ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .kanban-container {
            padding: 20px;
        }
        .kanban-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }
        .kanban-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .kanban-column {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            min-height: 600px;
        }
        .kanban-column h3 {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e1e1e1;
            color: var(--dark);
        }
        .kanban-task {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: move;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        .kanban-task:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .kanban-task.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .task-title {
            font-weight: 600;
            font-size: 14px;
            margin: 0;
            flex: 1;
        }
        .task-priority {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }
        .task-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #888;
        }
        .task-deadline {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }
        .priority-alta { background: #ffeaea; color: var(--danger); border-left-color: var(--danger); }
        .priority-media { background: #fff4e6; color: var(--warning); border-left-color: var(--warning); }
        .priority-baixa { background: #e8f5e9; color: var(--secondary); border-left-color: var(--secondary); }
        .priority-urgente { background: #fce4ec; color: #e91e63; border-left-color: #e91e63; }
        
        .column-backlog { border-top: 4px solid #95a5a6; }
        .column-todo { border-top: 4px solid #3498db; }
        .column-doing { border-top: 4px solid #f39c12; }
        .column-done { border-top: 4px solid #2ecc71; }
        
        .add-task-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            width: 100%;
        }
        .add-task-btn:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="kanban-container">
            <div class="kanban-header">
                <div>
                    <h1>Kanban - <?= $project['nome'] ?></h1>
                    <p style="color: #666; margin-top: 5px;"><?= $project['descricao'] ?></p>
                </div>
                <div>
                    <a href="../projects/view.php?id=<?= $project_id ?>" class="btn">← Voltar ao Projeto</a>
                    <a href="../tasks/create.php?project=<?= $project_id ?>" class="btn btn-primary">+ Nova Tarefa</a>
                </div>
            </div>
            
            <div class="kanban-board" id="kanbanBoard">
                <!-- Coluna Backlog -->
                <div class="kanban-column column-backlog">
                    <h3>📥 Backlog</h3>
                    <div class="kanban-column-content" data-status="backlog" id="backlog-column">
                        <?php foreach ($tasks['backlog'] as $task): ?>
                            <div class="kanban-task" data-task-id="<?= $task['id'] ?>" draggable="true">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['titulo']) ?></h4>
                                    <span class="task-priority priority-<?= $task['prioridade'] ?>">
                                        <?= ucfirst($task['prioridade']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($task['descricao'])): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['descricao']) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <?php if ($task['data_limite']): ?>
                                        <span class="task-deadline">
                                            📅 <?= date('d/m/Y', strtotime($task['data_limite'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>#<?= $task['id'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="add-task-btn" onclick="location.href='../tasks/create.php?project=<?= $project_id ?>'">
                        + Adicionar Tarefa
                    </button>
                </div>
                
                <!-- Coluna To Do -->
                <div class="kanban-column column-todo">
                    <h3>📋 To Do</h3>
                    <div class="kanban-column-content" data-status="todo" id="todo-column">
                        <?php foreach ($tasks['todo'] as $task): ?>
                            <div class="kanban-task" data-task-id="<?= $task['id'] ?>" draggable="true">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['titulo']) ?></h4>
                                    <span class="task-priority priority-<?= $task['prioridade'] ?>">
                                        <?= ucfirst($task['prioridade']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($task['descricao'])): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['descricao']) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <?php if ($task['data_limite']): ?>
                                        <span class="task-deadline">
                                            📅 <?= date('d/m/Y', strtotime($task['data_limite'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>#<?= $task['id'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Coluna Doing -->
                <div class="kanban-column column-doing">
                    <h3>⚡ Doing</h3>
                    <div class="kanban-column-content" data-status="doing" id="doing-column">
                        <?php foreach ($tasks['doing'] as $task): ?>
                            <div class="kanban-task" data-task-id="<?= $task['id'] ?>" draggable="true">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['titulo']) ?></h4>
                                    <span class="task-priority priority-<?= $task['prioridade'] ?>">
                                        <?= ucfirst($task['prioridade']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($task['descricao'])): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['descricao']) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <?php if ($task['data_limite']): ?>
                                        <span class="task-deadline">
                                            📅 <?= date('d/m/Y', strtotime($task['data_limite'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>#<?= $task['id'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Coluna Done -->
                <div class="kanban-column column-done">
                    <h3>✅ Done</h3>
                    <div class="kanban-column-content" data-status="done" id="done-column">
                        <?php foreach ($tasks['done'] as $task): ?>
                            <div class="kanban-task" data-task-id="<?= $task['id'] ?>" draggable="true">
                                <div class="task-header">
                                    <h4 class="task-title"><?= htmlspecialchars($task['titulo']) ?></h4>
                                    <span class="task-priority priority-<?= $task['prioridade'] ?>">
                                        <?= ucfirst($task['prioridade']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($task['descricao'])): ?>
                                    <p class="task-description"><?= htmlspecialchars($task['descricao']) ?></p>
                                <?php endif; ?>
                                <div class="task-meta">
                                    <?php if ($task['data_limite']): ?>
                                        <span class="task-deadline">
                                            📅 <?= date('d/m/Y', strtotime($task['data_limite'])) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>#<?= $task['id'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Sistema de Drag & Drop
        document.addEventListener('DOMContentLoaded', function() {
            const tasks = document.querySelectorAll('.kanban-task');
            const columns = document.querySelectorAll('.kanban-column-content');
            
            let draggedTask = null;
            
            // Configurar eventos de drag para cada tarefa
            tasks.forEach(task => {
                task.addEventListener('dragstart', function(e) {
                    draggedTask = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.innerHTML);
                });
                
                task.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                    draggedTask = null;
                });
            });
            
            // Configurar eventos para as colunas
            columns.forEach(column => {
                column.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    
                    const afterElement = getDragAfterElement(column, e.clientY);
                    const draggable = document.querySelector('.kanban-task.dragging');
                    
                    if (afterElement == null) {
                        column.appendChild(draggable);
                    } else {
                        column.insertBefore(draggable, afterElement);
                    }
                });
                
                column.addEventListener('drop', function(e) {
                    e.preventDefault();
                    
                    if (draggedTask) {
                        const newStatus = this.getAttribute('data-status');
                        const taskId = draggedTask.getAttribute('data-task-id');
                        
                        // Calcular nova ordem
                        const tasksInColumn = this.querySelectorAll('.kanban-task');
                        let newOrder = Array.from(tasksInColumn).indexOf(draggedTask);
                        
                        // Atualizar no servidor via AJAX
                        updateTaskStatus(taskId, newStatus, newOrder);
                    }
                });
            });
            
            function getDragAfterElement(container, y) {
                const draggableElements = [...container.querySelectorAll('.kanban-task:not(.dragging)')];
                
                return draggableElements.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    
                    if (offset < 0 && offset > closest.offset) {
                        return { offset: offset, element: child };
                    } else {
                        return closest;
                    }
                }, { offset: Number.NEGATIVE_INFINITY }).element;
            }
            
            function updateTaskStatus(taskId, newStatus, newOrder) {
                const formData = new FormData();
                formData.append('action', 'update_task_status');
                formData.append('task_id', taskId);
                formData.append('new_status', newStatus);
                formData.append('new_order', newOrder);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Erro ao atualizar tarefa: ' + data.message);
                        // Recarregar a página para restaurar o estado
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao atualizar tarefa. Recarregando...');
                    location.reload();
                });
            }
        });
    </script>
</body>
</html>