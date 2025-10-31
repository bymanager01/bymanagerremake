<?php
require_once 'database.php';

class CallsManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Iniciar chamada
    public function startCall($chamador_id, $receptor_id, $tipo = 'video') {
        try {
            // Verificar se são contatos
            $contactsManager = new ContactsManager();
            $status = $contactsManager->getContactStatus($chamador_id, $receptor_id);
            
            if ($status !== 'aceito') {
                return ['success' => false, 'message' => 'Você precisa ser contato para fazer chamadas'];
            }

            // Gerar ID único para a sala
            $sala_id = uniqid('call_');

            $sql = "INSERT INTO chamadas (chamador_id, receptor_id, tipo, sala_id) VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$chamador_id, $receptor_id, $tipo, $sala_id]);

            $call_id = $this->db->lastInsertId();

            // Criar notificação
            $this->createCallNotification($receptor_id, $call_id, 'nova_chamada');

            $this->logAtividade($chamador_id, 'iniciar_chamada', "Chamada $tipo iniciada para usuário ID: $receptor_id");

            return ['success' => true, 'call_id' => $call_id, 'sala_id' => $sala_id];
        } catch(PDOException $e) {
            error_log("Erro ao iniciar chamada: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao iniciar chamada'];
        }
    }

    // Atualizar status da chamada
    public function updateCallStatus($call_id, $status, $duracao = null) {
        try {
            if ($status == 'finalizada' || $status == 'recusada' || $status == 'cancelada') {
                $sql = "UPDATE chamadas SET status = ?, data_fim = NOW(), duracao = ? WHERE id = ?";
            } else {
                $sql = "UPDATE chamadas SET status = ?, duracao = ? WHERE id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $duracao, $call_id]);
            
            return ['success' => true];
        } catch(PDOException $e) {
            error_log("Erro ao atualizar chamada: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar chamada'];
        }
    }

    // Buscar chamada por ID
    public function getCall($call_id) {
        try {
            $sql = "SELECT c.*, 
                           u1.nome as chamador_nome, u1.foto as chamador_foto,
                           u2.nome as receptor_nome, u2.foto as receptor_foto
                    FROM chamadas c
                    INNER JOIN usuarios u1 ON c.chamador_id = u1.id
                    INNER JOIN usuarios u2 ON c.receptor_id = u2.id
                    WHERE c.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$call_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erro ao buscar chamada: " . $e->getMessage());
            return null;
        }
    }

    // Buscar chamadas do usuário
    public function getUserCalls($usuario_id, $limit = 20) {
        try {
            $sql = "SELECT c.*, 
                           u1.nome as chamador_nome, 
                           u2.nome as receptor_nome,
                           CASE 
                               WHEN c.chamador_id = ? THEN 'outgoing' 
                               ELSE 'incoming' 
                           END as direcao
                    FROM chamadas c
                    INNER JOIN usuarios u1 ON c.chamador_id = u1.id
                    INNER JOIN usuarios u2 ON c.receptor_id = u2.id
                    WHERE c.chamador_id = ? OR c.receptor_id = ?
                    ORDER BY c.data_inicio DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $usuario_id, $usuario_id, $limit]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erro ao buscar chamadas: " . $e->getMessage());
            return [];
        }
    }

    // Verificar se usuário tem chamada ativa
    public function getActiveCall($usuario_id) {
        try {
            $sql = "SELECT c.*, 
                           u1.nome as chamador_nome, u1.foto as chamador_foto,
                           u2.nome as receptor_nome, u2.foto as receptor_foto
                    FROM chamadas c
                    INNER JOIN usuarios u1 ON c.chamador_id = u1.id
                    INNER JOIN usuarios u2 ON c.receptor_id = u2.id
                    WHERE (c.chamador_id = ? OR c.receptor_id = ?) 
                    AND c.status IN ('chamando', 'em_andamento')
                    ORDER BY c.data_inicio DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $usuario_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Erro ao buscar chamada ativa: " . $e->getMessage());
            return null;
        }
    }

    // Criar notificação de chamada
    private function createCallNotification($usuario_id, $chamada_id, $tipo) {
        try {
            $sql = "INSERT INTO notificacoes_chamada (usuario_id, chamada_id, tipo) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $chamada_id, $tipo]);
        } catch(PDOException $e) {
            error_log("Erro ao criar notificação: " . $e->getMessage());
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