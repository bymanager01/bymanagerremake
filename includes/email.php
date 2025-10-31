<?php
// Carrega o autoloader do Composer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'database.php';

class EmailManager {
    private $db;
    private $mail;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->setupMailer();
    }

    private function setupMailer() {
        $this->mail = new PHPMailer(true);
        
        try {
            // Configurações do servidor - AJUSTE COM SUAS CONFIGURAÇÕES
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com'; // Servidor SMTP
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'ByManager01@gmail.com'; // SEU EMAIL
            $this->mail->Password = 'uasypqatgdxepiyb'; // SUA SENHA DE APP
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 587;
            
            // Configurações do remetente
            $this->mail->setFrom('seu-email@gmail.com', SITE_NAME);
            $this->mail->isHTML(true);
            
            // Configurações de debug (remova em produção)
            // $this->mail->SMTPDebug = 2; // Descomente para debug
            
        } catch (Exception $e) {
            error_log("Erro ao configurar PHPMailer: " . $e->getMessage());
        }
    }

    // Enviar email de recuperação de senha
    public function sendPasswordReset($email, $token) {
        try {
            $user = $this->getUserByEmail($email);
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }

            // Salvar token no banco
            $this->saveResetToken($user['id'], $token);

            $reset_link = BASE_URL . "/auth/reset_password.php?token=" . $token;
            
            $subject = "Recuperação de Senha - " . SITE_NAME;
            $body = $this->getPasswordResetTemplate($user['nome'], $reset_link);
            
            $this->mail->addAddress($email);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = "Olá {$user['nome']}! Para redefinir sua senha, acesse: {$reset_link}";
            
            $this->mail->send();
            
            $this->logEmail($user['id'], 'password_reset', $subject);
            
            return ['success' => true, 'message' => 'Email enviado com sucesso! Verifique sua caixa de entrada.'];
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $this->mail->ErrorInfo);
            return [
                'success' => false, 
                'message' => 'Erro ao enviar email. Tente novamente.',
                'debug' => $this->mail->ErrorInfo // Remova isso em produção
            ];
        }
    }

    // Template para recuperação de senha
    private function getPasswordResetTemplate($name, $reset_link) {
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperação de Senha</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #3498db, #2980b9);
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content { 
                    padding: 30px; 
                }
                .button { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: #3498db; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 30px; 
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                    font-size: 12px; 
                    color: #666; 
                }
                .warning {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 15px 0;
                    color: #856404;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$name}!</h2>
                    <p>Você solicitou a recuperação de senha para sua conta.</p>
                    <p>Clique no botão abaixo para redefinir sua senha:</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Redefinir Senha</a>
                    </p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong>
                        <p>Se você não solicitou essa alteração, ignore este email.</p>
                        <p><strong>Este link expira em 1 hora.</strong></p>
                    </div>
                    
                    <p>Se o botão não funcionar, copie e cole este link no seu navegador:</p>
                    <p style='word-break: break-all; color: #666;'>{$reset_link}</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " " . SITE_NAME . ". Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    // Enviar notificação de nova tarefa
    public function sendTaskNotification($user_id, $task_title, $project_name, $deadline = null) {
        try {
            $user = $this->getUserById($user_id);
            if (!$user) return false;

            $subject = "📋 Nova Tarefa - {$project_name}";
            $body = $this->getTaskNotificationTemplate($user['nome'], $task_title, $project_name, $deadline);
            
            $this->mail->addAddress($user['email']);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            
            $this->mail->send();
            
            $this->logEmail($user_id, 'task_notification', $subject);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de tarefa: " . $e->getMessage());
            return false;
        }
    }

    // Template para notificação de tarefa
    private function getTaskNotificationTemplate($name, $task_title, $project_name, $deadline) {
        $deadline_text = $deadline ? 
            "<p><strong>📅 Data limite:</strong> " . date('d/m/Y', strtotime($deadline)) . "</p>" : 
            "<p><strong>📅 Data limite:</strong> Não definida</p>";
        
        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2ecc71; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .task-info { background: white; padding: 15px; border-left: 4px solid #2ecc71; margin: 15px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$name}!</h2>
                    <p>Uma nova tarefa foi atribuída a você:</p>
                    
                    <div class='task-info'>
                        <h3>📋 {$task_title}</h3>
                        <p><strong>🏗️ Projeto:</strong> {$project_name}</p>
                        {$deadline_text}
                    </div>
                    
                    <p>Acesse o sistema para ver mais detalhes e atualizar o andamento.</p>
                    
                    <p><a href='" . BASE_URL . "/home.php' style='color: #3498db;'>Acessar Sistema</a></p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " " . SITE_NAME . ". Todos os direitos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    // Métodos auxiliares
    private function getUserByEmail($email) {
        $query = "SELECT id, nome, email FROM usuarios WHERE email = ? AND status = 'ativo'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    private function getUserById($id) {
        $query = "SELECT id, nome, email FROM usuarios WHERE id = ? AND status = 'ativo'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function saveResetToken($user_id, $token) {
        // Primeiro, invalidar tokens anteriores
        $query = "UPDATE tokens_recuperacao SET utilizado = TRUE WHERE usuario_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id]);
        
        // Inserir novo token
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $query = "INSERT INTO tokens_recuperacao (usuario_id, token, expiracao) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id, $token, $expiration]);
    }

    private function logEmail($user_id, $type, $subject) {
        $query = "INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, 'email_enviado', ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id, "Email enviado: {$subject}"]);
    }

    // Verificar token de recuperação
    public function verifyResetToken($token) {
        $query = "SELECT tr.*, u.email 
                  FROM tokens_recuperacao tr 
                  INNER JOIN usuarios u ON tr.usuario_id = u.id 
                  WHERE tr.token = ? AND tr.utilizado = FALSE AND tr.expiracao > NOW()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    // Atualizar senha e invalidar token
    public function updatePasswordWithToken($token, $new_password) {
        try {
            $token_data = $this->verifyResetToken($token);
            if (!$token_data) {
                return ['success' => false, 'message' => 'Token inválido ou expirado'];
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Atualizar senha
            $query = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$hashed_password, $token_data['usuario_id']]);
            
            // Marcar token como utilizado
            $query = "UPDATE tokens_recuperacao SET utilizado = TRUE WHERE token = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$token]);
            
            $this->logEmail($token_data['usuario_id'], 'password_changed', 'Senha alterada com sucesso');
            
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar senha: ' . $e->getMessage()];
        }
    }
}
?>