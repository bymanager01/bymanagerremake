<?php
require_once 'config/config.php';
require_once 'includes/email.php';

echo "<h2>Testando PHPMailer</h2>";

try {
    $emailManager = new EmailManager();
    
    // Teste de configuração
    echo "<p>✅ PHPMailer carregado com sucesso</p>";
    
    // Teste de envio (substitua pelo seu email)
    $result = $emailManager->sendPasswordReset('seu-email@gmail.com', 'test-token-123');
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ Email enviado com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro: " . $result['message'] . "</p>";
        if (isset($result['debug'])) {
            echo "<p><strong>Debug:</strong> " . $result['debug'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>