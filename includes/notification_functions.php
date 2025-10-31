<?php
require_once 'database.php';

class NotificationManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Criar notificação
    public function create($usuario_id, $tipo, $titulo, $mensagem, $link = null) {
        try {
            $query = "INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $tipo, $titulo, $mensagem, $link]);
            
            return true;
            
        } catch(PDOException $e) {
            error_log("Erro ao criar notificação: " . $e->getMessage());
            return false;
        }
    }

    // Buscar notificações do usuário
    public function getUserNotifications($usuario_id, $limit = 10) {
        try {
            $query = "SELECT * FROM notificacoes 
                      WHERE usuario_id = ? 
                      ORDER BY data_criacao DESC 
                      LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $limit]);
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            return [];
        }
    }

    // Contar notificações não lidas
    public function countUnread($usuario_id) {
        try {
            $query = "SELECT COUNT(*) as total FROM notificacoes 
                      WHERE usuario_id = ? AND lida = FALSE";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            
            $result = $stmt->fetch();
            return $result['total'];
            
        } catch(PDOException $e) {
            return 0;
        }
    }

    // Marcar como lida
    public function markAsRead($notification_id, $usuario_id) {
        try {
            $query = "UPDATE notificacoes SET lida = TRUE 
                      WHERE id = ? AND usuario_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$notification_id, $usuario_id]);
            
            return true;
            
        } catch(PDOException $e) {
            return false;
        }
    }

    // Marcar todas como lidas
    public function markAllAsRead($usuario_id) {
        try {
            $query = "UPDATE notificacoes SET lida = TRUE 
                      WHERE usuario_id = ? AND lida = FALSE";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            
            return true;
            
        } catch(PDOException $e) {
            return false;
        }
    }

    // Notificação para tarefa próxima do prazo
    public function notifyUpcomingDeadline($tarefa_id) {
        try {
            $query = "SELECT t.*, u.id as usuario_id, u.nome as usuario_nome 
                      FROM tarefas t 
                      INNER JOIN usuarios u ON t.usuario_id = u.id 
                      WHERE t.id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$tarefa_id]);
            $tarefa = $stmt->fetch();
            
            if ($tarefa) {
                $dias_restantes = date_diff(
                    date_create(date('Y-m-d')),
                    date_create($tarefa['data_limite'])
                )->days;
                
                $this->create(
                    $tarefa['usuario_id'],
                    'deadline',
                    'Prazo se aproximando',
                    "A tarefa '{$tarefa['titulo']}' vence em {$dias_restantes} dias",
                    BASE_URL . "/tasks/view.php?id={$tarefa_id}"
                );
            }
            
        } catch(PDOException $e) {
            error_log("Erro na notificação de prazo: " . $e->getMessage());
        }
    }
}
?>