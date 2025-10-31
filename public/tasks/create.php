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

$projects = $projectManager->getUserProjects($_SESSION['usuario']['id']);

$error = '';
$success = '';

// Se veio de um projeto específico
$projeto_id = $_GET['project'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo']);
    $descricao = sanitize($_POST['descricao']);
    $projeto_id = $_POST['projeto_id'];
    $prioridade = $_POST['prioridade'];
    $data_limite = $_POST['data_limite'] ?: null;
    
    if (empty($titulo) || empty($projeto_id)) {
        $error = 'Título e projeto são obrigatórios';
    } else {
        $result = $taskManager->create($titulo, $descricao, $projeto_id, $_SESSION['usuario']['id'], $prioridade, $data_limite);
        
        if ($result['success']) {
            $success = 'Tarefa criada com sucesso!';
            // Redirecionar após 2 segundos
            header("Refresh: 2; URL=../projects/view.php?id=" . $projeto_id);
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
    <title>Nova Tarefa - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="form-page">
            <div class="form-header">
                <h1>Nova Tarefa</h1>
                <a href="<?= $projeto_id ? "../projects/view.php?id={$projeto_id}" : "../home.php" ?>" class="btn">← Voltar</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="titulo">Título *</label>
                    <input type="text" id="titulo" name="titulo" required 
                           value="<?= $_POST['titulo'] ?? '' ?>" 
                           placeholder="Digite o título da tarefa">
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4" 
                              placeholder="Descreva os detalhes da tarefa..."><?= $_POST['descricao'] ?? '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="projeto_id">Projeto *</label>
                    <select id="projeto_id" name="projeto_id" required>
                        <option value="">Selecione um projeto</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" 
                                <?= (($_POST['projeto_id'] ?? $projeto_id) == $project['id']) ? 'selected' : '' ?>>
                                <?= $project['nome'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade">
                            <option value="baixa" <?= (($_POST['prioridade'] ?? 'media') == 'baixa') ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= (($_POST['prioridade'] ?? 'media') == 'media') ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= (($_POST['prioridade'] ?? 'media') == 'alta') ? 'selected' : '' ?>>Alta</option>
                            <option value="urgente" <?= (($_POST['prioridade'] ?? 'media') == 'urgente') ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_limite">Data Limite</label>
                        <input type="date" id="data_limite" name="data_limite" 
                               value="<?= $_POST['data_limite'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Criar Tarefa</button>
                    <a href="<?= $projeto_id ? "../projects/view.php?id={$projeto_id}" : "../home.php" ?>" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>