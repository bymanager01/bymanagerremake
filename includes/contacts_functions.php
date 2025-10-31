<?php
require_once 'database.php';

class ContactsManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Pesquisar usuários
    public function searchUsers($usuario_id, $query) {
        try {
            $search_term = "%$query%";
            $sql = "SELECT id, nome, email, foto, bio 
                    FROM usuarios 
                    WHERE (nome LIKE ? OR email LIKE ?) 
                    AND id != ? 
                    AND status = 'ativo' 
                    LIMIT 20";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$search_term, $search_term, $usuario_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erro ao pesquisar usuários: " . $e->getMessage());
            return [];
        }
    }

    // Enviar solicitação de contato
    public function sendContactRequest($usuario_id, $contato_id) {
        try {
            // Verificar se já existe solicitação
            $sql = "SELECT id, status FROM contatos 
                    WHERE (usuario_id = ? AND contato_id = ?) 
                    OR (usuario_id = ? AND contato_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $contato_id, $contato_id, $usuario_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($existing['status'] == 'bloqueado') {
                    return ['success' => false, 'message' => 'Não é possível adicionar este contato'];
                }
                return ['success' => false, 'message' => 'Solicitação já enviada ou contato já adicionado'];
            }

            // Criar solicitação
            $sql = "INSERT INTO contatos (usuario_id, contato_id, status) VALUES (?, ?, 'pendente')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $contato_id]);

            // Log da atividade
            $this->logAtividade($usuario_id, 'solicitar_contato', "Solicitação enviada para usuário ID: $contato_id");

            return ['success' => true, 'message' => 'Solicitação de contato enviada'];
        } catch(PDOException $e) {
            error_log("Erro ao enviar solicitação: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao enviar solicitação'];
        }
    }

    // Aceitar solicitação de contato
    public function acceptContactRequest($usuario_id, $solicitante_id) {
        try {
            $sql = "UPDATE contatos SET status = 'aceito', data_aceitacao = NOW() 
                    WHERE usuario_id = ? AND contato_id = ? AND status = 'pendente'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $solicitante_id]);

            if ($stmt->rowCount() > 0) {
                $this->logAtividade($usuario_id, 'aceitar_contato', "Solicitação aceita de usuário ID: $solicitante_id");
                return ['success' => true, 'message' => 'Solicitação aceita'];
            }
            
            return ['success' => false, 'message' => 'Solicitação não encontrada'];
        } catch(PDOException $e) {
            error_log("Erro ao aceitar solicitação: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao aceitar solicitação'];
        }
    }

    // Recusar/bloquear contato
    public function blockContact($usuario_id, $contato_id) {
        try {
            $sql = "UPDATE contatos SET status = 'bloqueado' 
                    WHERE (usuario_id = ? AND contato_id = ?) 
                    OR (usuario_id = ? AND contato_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $contato_id, $contato_id, $usuario_id]);

            $this->logAtividade($usuario_id, 'bloquear_contato', "Usuário bloqueado ID: $contato_id");
            
            return ['success' => true, 'message' => 'Contato bloqueado'];
        } catch(PDOException $e) {
            error_log("Erro ao bloquear contato: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao bloquear contato'];
        }
    }

    // Listar contatos aceitos
    public function getContacts($usuario_id) {
        try {
            $sql = "SELECT u.id, u.nome, u.email, u.foto, u.bio, c.data_aceitacao 
                    FROM contatos c 
                    INNER JOIN usuarios u ON (
                        (c.usuario_id = ? AND c.contato_id = u.id) OR 
                        (c.contato_id = ? AND c.usuario_id = u.id)
                    )
                    WHERE c.status = 'aceito' 
                    ORDER BY u.nome";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $usuario_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erro ao listar contatos: " . $e->getMessage());
            return [];
        }
    }

    // Listar solicitações pendentes
    public function getPendingRequests($usuario_id) {
        try {
            $sql = "SELECT u.id, u.nome, u.email, u.foto, u.bio, c.data_solicitacao 
                    FROM contatos c 
                    INNER JOIN usuarios u ON c.usuario_id = u.id 
                    WHERE c.contato_id = ? AND c.status = 'pendente' 
                    ORDER BY c.data_solicitacao DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Erro ao listar solicitações: " . $e->getMessage());
            return [];
        }
    }

    // Verificar status do contato
    public function getContactStatus($usuario_id, $contato_id) {
        try {
            $sql = "SELECT status FROM contatos 
                    WHERE (usuario_id = ? AND contato_id = ?) 
                    OR (usuario_id = ? AND contato_id = ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$usuario_id, $contato_id, $contato_id, $usuario_id]);
            $result = $stmt->fetch();
            return $result ? $result['status'] : null;
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