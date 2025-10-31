<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$projectManager = new ProjectManager();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $descricao = sanitize($_POST['descricao']);
    $cor = $_POST['cor'] ?? '#3498db';
    $data_limite = $_POST['data_limite'] ?: null;
    
    if (empty($nome)) {
        $error = 'Nome do projeto é obrigatório';
    } else {
        $result = $projectManager->create($nome, $descricao, $_SESSION['usuario']['id'], $data_limite, $cor);
        
        if ($result['success']) {
            $success = 'Projeto criado com sucesso!';
            header("Refresh: 2; URL=view.php?id=" . $result['id']);
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
    <title>Novo Projeto - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="form-page">
            <div class="form-header">
                <h1>Novo Projeto</h1>
                <a href="index.php" class="btn">← Voltar</a>
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
                           value="<?= $_POST['nome'] ?? '' ?>"
                           placeholder="Digite o nome do projeto">
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4"
                              placeholder="Descreva o objetivo do projeto..."><?= $_POST['descricao'] ?? '' ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cor">Cor do Projeto</label>
                        <input type="color" id="cor" name="cor" 
                               value="<?= $_POST['cor'] ?? '#3498db' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_limite">Data Limite</label>
                        <input type="date" id="data_limite" name="data_limite"
                               value="<?= $_POST['data_limite'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Criar Projeto</button>
                    <a href="index.php" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>