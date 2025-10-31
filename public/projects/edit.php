<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';

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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $descricao = sanitize($_POST['descricao']);
    $cor = $_POST['cor'] ?? '#3498db';
    $data_limite = $_POST['data_limite'] ?: null;
    $status = $_POST['status'] ?? 'ativo';
    
    if (empty($nome)) {
        $error = 'Nome do projeto é obrigatório';
    } else {
        $result = $projectManager->update($project_id, $_SESSION['usuario']['id'], [
            'nome' => $nome,
            'descricao' => $descricao,
            'cor' => $cor,
            'data_limite' => $data_limite,
            'status' => $status
        ]);
        
        if ($result['success']) {
            $success = 'Projeto atualizado com sucesso!';
            $project = $projectManager->getProject($project_id, $_SESSION['usuario']['id']);
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
    <title>Editar Projeto - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="form-page">
            <div class="form-header">
                <h1>Editar Projeto</h1>
                <a href="view.php?id=<?= $project_id ?>" class="btn">← Voltar</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="nome">Nome do Projeto *</label>
                    <input type="text" id="nome" name="nome" required 
                           value="<?= htmlspecialchars($project['nome']) ?>"
                           placeholder="Digite o nome do projeto">
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4"
                              placeholder="Descreva o objetivo do projeto..."><?= htmlspecialchars($project['descricao'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cor">Cor do Projeto</label>
                        <input type="color" id="cor" name="cor" 
                               value="<?= $project['cor'] ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_limite">Data Limite</label>
                        <input type="date" id="data_limite" name="data_limite"
                               value="<?= $project['data_limite'] ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="ativo" <?= $project['status'] == 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="concluido" <?= $project['status'] == 'concluido' ? 'selected' : '' ?>>Concluído</option>
                        <option value="cancelado" <?= $project['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Atualizar Projeto</button>
                    <a href="view.php?id=<?= $project_id ?>" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>