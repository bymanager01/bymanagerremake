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
    $email = sanitize($_POST['email']);
    $senha = $_POST['senha'];
    
    $result = $auth->login($email, $senha);
    
    if ($result['success']) {
        redirect(BASE_URL . '/home.php');
    } else {
        $error = $result['message'];
    }
}

// Após o login bem-sucedido, verificar se há um token de convite
if (isset($_SESSION['invite_token'])) {
    $token = $_SESSION['invite_token'];
    unset($_SESSION['invite_token']);
    redirect(BASE_URL . '/projects/accept_invite.php?token=' . $token);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><?= SITE_NAME ?></h1>
                <p>Entre em sua conta</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= $_POST['email'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </form>
            
            <div class="auth-links">
                <a href="forgot_password.php">Esqueci minha senha</a>
                <a href="register.php">Criar nova conta</a>
            </div>
        </div>
    </div>
</body>
</html>