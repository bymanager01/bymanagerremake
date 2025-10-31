<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/email.php'; // Agora com PHPMailer

$auth = new Auth();
if ($auth->isLoggedIn()) {
    redirect(BASE_URL . '/home.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Digite seu email';
    } else {
        $emailManager = new EmailManager();
        $token = bin2hex(random_bytes(32));
        
        $result = $emailManager->sendPasswordReset($email, $token);
        
        if ($result['success']) {
            $success = 'Enviamos um link de recuperação para seu email. Verifique sua caixa de entrada.';
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
    <title>Recuperar Senha - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Recuperar Senha</h1>
                <p>Digite seu email para receber o link de recuperação</p>
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
                           value="<?= $_POST['email'] ?? '' ?>"
                           placeholder="seu@email.com">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Enviar Link de Recuperação</button>
            </form>
            
            <div class="auth-links">
                <a href="login.php">Lembrou sua senha? Faça login</a>
                <a href="register.php">Criar nova conta</a>
            </div>
        </div>
    </div>
</body>
</html>