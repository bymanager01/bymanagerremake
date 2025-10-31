<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/task_functions.php';
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

if (!$can_edit) {
    $_SESSION['error'] = 'Você não tem permissão para editar esta tarefa.';
    redirect(BASE_URL . '/tasks/view.php?id=' . $task_id);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $descricao = sanitize($_POST['descricao']);
    $prioridade = $_POST['prioridade'];
    $status = $_POST['status'];
    $data_limite = $_POST['data_limite'] ?: null;

    if (empty($titulo)) {
        $error = 'Título da tarefa é obrigatório';
    } else {
        $result = $taskManager->update($task_id, $_SESSION['usuario']['id'], [
            'titulo' => $titulo,
            'descricao' => $descricao,
            'prioridade' => $prioridade,
            'status' => $status,
            'data_limite' => $data_limite
        ]);

        if ($result['success']) {
            $success = 'Tarefa atualizada com sucesso!';
            // Atualizar os dados da tarefa
            $task = $taskManager->getTask($task_id, $_SESSION['usuario']['id']);
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarefa - <?= htmlspecialchars($task['titulo']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="page-header">
            <div>
                <h1>Editar Tarefa</h1>
                <p>Projeto: <a href="../projects/view.php?id=<?= $task['projeto_id'] ?>"><?= htmlspecialchars($task['projeto_nome']) ?></a></p>
            </div>
            <div>
                <a href="view.php?id=<?= $task_id ?>" class="btn">← Voltar para Tarefa</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="edit-task-form">
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="titulo">Título da Tarefa *</label>
                    <input type="text" id="titulo" name="titulo" required 
                           value="<?= htmlspecialchars($task['titulo']) ?>"
                           placeholder="Digite o título da tarefa">
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="6"
                              placeholder="Descreva os detalhes da tarefa..."><?= htmlspecialchars($task['descricao']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade" required>
                            <option value="baixa" <?= $task['prioridade'] == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= $task['prioridade'] == 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= $task['prioridade'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                            <option value="urgente" <?= $task['prioridade'] == 'urgente' ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="backlog" <?= $task['status'] == 'backlog' ? 'selected' : '' ?>>Backlog</option>
                            <option value="todo" <?= $task['status'] == 'todo' ? 'selected' : '' ?>>A Fazer</option>
                            <option value="doing" <?= $task['status'] == 'doing' ? 'selected' : '' ?>>Em Progresso</option>
                            <option value="done" <?= $task['status'] == 'done' ? 'selected' : '' ?>>Concluído</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_limite">Data Limite</label>
                        <input type="date" id="data_limite" name="data_limite"
                               value="<?= $task['data_limite'] ?>">
                    </div>
                </div>

                <div class="current-info">
                    <h3>Informações Atuais</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Projeto:</strong>
                            <span><?= htmlspecialchars($task['projeto_nome']) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Criado por:</strong>
                            <span><?= htmlspecialchars($task['criador_nome']) ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Data de Criação:</strong>
                            <span><?= date('d/m/Y H:i', strtotime($task['data_criacao'])) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="view.php?id=<?= $task_id ?>" class="btn">Cancelar</a>
                </div>
            </form>
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
        .edit-task-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .current-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid var(--primary);
        }
        .current-info h3 {
            margin: 0 0 15px 0;
            color: var(--dark);
            font-size: 16px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .info-item strong {
            color: #666;
        }
    </style>
</body>
</html>