<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';

$auth = new Auth();

// Redirecionar se já estiver logado
if ($auth->isLoggedIn()) {
    redirect(BASE_URL . '/home.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } else {
        $result = $auth->registrar($nome, $email, $senha);
        
        if ($result['success']) {
            $success = $result['message'] . ' Você já pode fazer login.';
            // Limpar formulário
            $_POST = [];
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
    <title>Registrar - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Criar Conta</h1>
                <p>Preencha os dados para se registrar</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required 
                           value="<?= $_POST['nome'] ?? '' ?>"
                           placeholder="Digite seu nome completo">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= $_POST['email'] ?? '' ?>"
                           placeholder="seu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required minlength="6"
                           placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                           placeholder="Digite a senha novamente">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Criar Conta</button>
            </form>
            
            <div class="auth-links">
                <a href="login.php">Já tem uma conta? Faça login</a>
            </div>
        </div>
    </div>
</body>
</html>