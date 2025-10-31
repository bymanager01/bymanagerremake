<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/email.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    redirect(BASE_URL . '/home.php');
}

$error = '';
$success = '';

// Verificar se o token foi passado via GET
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Token de recuperação não fornecido.';
    $token_invalido = true;
} else {
    $emailManager = new EmailManager();
    $token_data = $emailManager->verifyResetToken($token);
    
    if (!$token_data) {
        $error = 'Token inválido ou expirado. Solicite uma nova recuperação de senha.';
        $token_invalido = true;
    } else {
        $token_invalido = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$token_invalido) {
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    if (empty($senha) || empty($confirmar_senha)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } else {
        $result = $emailManager->updatePasswordWithToken($token, $senha);
        
        if ($result['success']) {
            $success = 'Senha alterada com sucesso! Você já pode fazer login.';
            $token = ''; // Limpar token após uso
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
    <title>Nova Senha - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Criar Nova Senha</h1>
                <p>Digite sua nova senha</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (!$token_invalido || $success): ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="senha">Nova Senha</label>
                    <input type="password" id="senha" name="senha" required minlength="6"
                           placeholder="Mínimo 6 caracteres">
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                           placeholder="Digite a senha novamente">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Redefinir Senha</button>
            </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <a href="login.php">Fazer login</a>
                <a href="register.php">Criar nova conta</a>
                <?php if ($token_invalido && !$success): ?>
                    <a href="forgot_password.php">Solicitar novo link</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>