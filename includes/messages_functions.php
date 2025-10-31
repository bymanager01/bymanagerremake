<?php
require_once 'database.php';

class MessagesManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Enviar mensagem
    public function sendMessage($remetente_id, $destinatario_id, $mensagem, $tipo = 'texto') {
        try {
            // Verificar se são contatos
            $contactsManager = new ContactsManager();
            $status = $contactsManager->getContactStatus($remetente_id, $destinatario_id);
            
            if ($status !== 'aceito') {
                return ['success' => false, 'message' => 'Você precisa ser contato para enviar mensagens'];
            }

            $sql = "INSERT INTO mensagens (remetente_id, destinatario_id, mensagem, tipo) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remetente_id, $destinatario_id, $mensagem, $tipo]);

            $message_id = $this->db->lastInsertId();

            $this->logAtividade($remetente_id, 'enviar_mensagem', "Mensagem enviada para usuário ID: $destinatario_id");

            return ['success' => true, 'message_id' => $message_id];
        } catch(PDOException $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao enviar mensagem'];
        }
    }

    // Buscar conversas
    public function getConversations($usuario_id) {
        try {
            $sql = "SELECT 
                u.id as contact_id,
                u.nome,
                u.foto,
                u.email,
                MAX(m.data_envio) as ultima_mensagem,
                (SELECT mensagem FROM mensagens 
                 WHERE ((remetente_id = ? AND destinatario_id = u.id) OR 
                        (remetente_id = u.id AND destinatario_id = ?))
                 ORDER BY data_envio DESC LIMIT 1) as ultimo_texto,
                (SELECT COUNT(*) FROM mensagens 
                 WHERE destinatario_id = ? AND remetente_id = u.id AND lida = FALSE) as nao_lidas
            FROM usuarios u
            INNER JOIN contatos c ON (
                (c.usuario_id = ? AND c.contato_id = u.id) OR 
                (c.contato_id = ? AND c.usuario_id = u.id)
            )
            LEFT JOIN mensagens m ON (
                (m.remetente_id = ? AND m.destinatario_id = u.id) OR 
                (m.remetente_id = u.id AND m.destinatario_id = ?)
            )
            WHERE c.status = 'aceito'
            GROUP BY u.id
            ORDER BY ultima_mensagem DESC NULLS LAST, u.nome ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id, $usuario_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erro ao buscar conversas: " . $e->getMessage());
            return [];
        }
    }

    // Buscar mensagens entre dois usuários
    public function getMessages($usuario1_id, $usuario2_id, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT m.*, 
                           u.nome as remetente_nome, 
                           u.foto as remetente_foto,
                           CASE WHEN m.remetente_id = ? THEN 'sent' ELSE 'received' END as direcao
                    FROM mensagens m
                    INNER JOIN usuarios u ON m.remetente_id = u.id
                    WHERE (m.remetente_id = ? AND m.destinatario_id = ?) 
                       OR (m.remetente_id = ? AND m.destinatario_id = ?)
                    ORDER BY m.data_envio DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario1_id, $usuario1_id, $usuario2_id, $usuario2_id, $usuario1_id, $limit, $offset]);
            $messages = $stmt->fetchAll();
            
            // Inverter para ordem cronológica
            return array_reverse($messages);
        } catch(PDOException $e) {
            error_log("Erro ao buscar mensagens: " . $e->getMessage());
            return [];
        }
    }

    // Marcar mensagens como lidas
    public function markMessagesAsRead($remetente_id, $destinatario_id) {
        try {
            $sql = "UPDATE mensagens SET lida = TRUE, data_leitura = NOW() 
                    WHERE remetente_id = ? AND destinatario_id = ? AND lida = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$remetente_id, $destinatario_id]);
            return true;
        } catch(PDOException $e) {
            error_log("Erro ao marcar mensagens como lidas: " . $e->getMessage());
            return false;
        }
    }

    // Contar mensagens não lidas
    public function countUnreadMessages($usuario_id) {
        try {
            $sql = "SELECT COUNT(*) as total FROM mensagens 
                    WHERE destinatario_id = ? AND lida = FALSE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id]);
            $result = $stmt->fetch();
            return $result['total'];
        } catch(PDOException $e) {
            error_log("Erro ao contar mensagens não lidas: " . $e->getMessage());
            return 0;
        }
    }

    // Buscar última mensagem com um contato
    public function getLastMessage($usuario_id, $contato_id) {
        try {
            $sql = "SELECT * FROM mensagens 
                    WHERE ((remetente_id = ? AND destinatario_id = ?) 
                       OR (remetente_id = ? AND destinatario_id = ?))
                    ORDER BY data_envio DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $contato_id, $contato_id, $usuario_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            return null;
        }
    }

    private function logAtividade($usuario_id, $acao, $descricao) {
        try {
            $query = "INSERT INTO atividades (usuario_id, acao, descricao) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $acao, $descricao]);
        } catch(PDOException $e) {
            // Silencioso
        }
    }
}
?>