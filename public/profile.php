<?php
require_once '../config/config.php';
require_once '../includes/auth_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $bio = sanitize($_POST['bio']);
    
    if (empty($nome) || empty($email)) {
        $error = 'Nome e email são obrigatórios';
    } else {
        // Atualizar no banco de dados
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE usuarios SET nome = ?, email = ?, bio = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$nome, $email, $bio, $_SESSION['usuario']['id']])) {
            // Atualizar sessão
            $_SESSION['usuario']['nome'] = $nome;
            $_SESSION['usuario']['email'] = $email;
            
            $success = 'Perfil atualizado com sucesso!';
        } else {
            $error = 'Erro ao atualizar perfil';
        }
    }
}

// Buscar dados atualizados
$database = new Database();
$db = $database->getConnection();
$query = "SELECT nome, email, bio, data_criacao FROM usuarios WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['usuario']['id']]);
$usuario = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <div class="form-page">
            <div class="form-header">
                <h1>Meu Perfil</h1>
                <a href="home.php" class="btn">← Voltar</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" required 
                           value="<?= $usuario['nome'] ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= $usuario['email'] ?>">
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4"
                              placeholder="Fale um pouco sobre você..."><?= $usuario['bio'] ?? '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Membro desde</label>
                    <p class="form-static"><?= date('d/m/Y', strtotime($usuario['data_criacao'])) ?></p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                    <a href="home.php" class="btn">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .form-static {
            padding: 12px;
            background: #f5f5f5;
            border-radius: 5px;
            margin: 0;
            color: #666;
        }
    </style>
</body>
</html>