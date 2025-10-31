<?php
require_once 'database.php';

class ProjectManager {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($nome, $descricao, $usuario_id, $data_limite = null, $cor = '#3498db') {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO projetos (nome, descricao, cor, usuario_id, data_limite) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$nome, $descricao, $cor, $usuario_id, $data_limite]);
            $project_id = $pdo->lastInsertId();
            
            // Adicionar o criador como membro com permissão de administrador
            $stmt = $pdo->prepare("
                INSERT INTO projeto_membros (projeto_id, usuario_id, nivel_permissao) 
                VALUES (?, ?, 'administrador')
            ");
            $stmt->execute([$project_id, $usuario_id]);
            
            // Registrar atividade
            $this->registrarAtividade($usuario_id, 'criar_projeto', "Projeto criado: $nome", 'projetos', $project_id);
            
            return [
                'success' => true,
                'id' => $project_id,
                'message' => 'Projeto criado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar projeto: ' . $e->getMessage()
            ];
        }
    }
    
    public function update($project_id, $user_id, $data) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário é o dono do projeto
            $stmt = $pdo->prepare("SELECT usuario_id FROM projetos WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            if (!$project || $project['usuario_id'] != $user_id) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este projeto.'
                ];
            }
            
            $stmt = $pdo->prepare("
                UPDATE projetos 
                SET nome = ?, descricao = ?, cor = ?, data_limite = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['descricao'],
                $data['cor'],
                $data['data_limite'],
                $data['status'],
                $project_id
            ]);
            
            return [
                'success' => true,
                'message' => 'Projeto atualizado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar projeto: ' . $e->getMessage()
            ];
        }
    }
    
    public function getProject($project_id, $user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id) as total_tarefas,
                       (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id AND t.status = 'done') as tarefas_concluidas
                FROM projetos p
                WHERE p.id = ? AND (
                    p.usuario_id = ? 
                    OR EXISTS (
                        SELECT 1 FROM projeto_membros pm 
                        WHERE pm.projeto_id = p.id AND pm.usuario_id = ?
                    )
                )
            ");
            
            $stmt->execute([$project_id, $user_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getUserProjects($user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id) as total_tarefas,
                       (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id AND t.status = 'done') as tarefas_concluidas
                FROM projetos p
                WHERE p.usuario_id = ?
                ORDER BY p.data_criacao DESC
            ");
            
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getInvitedProjects($user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       pm.nivel_permissao,
                       (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id) as total_tarefas,
                       (SELECT COUNT(*) FROM tarefas t WHERE t.projeto_id = p.id AND t.status = 'done') as tarefas_concluidas
                FROM projetos p
                INNER JOIN projeto_membros pm ON p.id = pm.projeto_id
                WHERE pm.usuario_id = ? AND p.usuario_id != ?
                ORDER BY p.data_criacao DESC
            ");
            
            $stmt->execute([$user_id, $user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function delete($project_id, $user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário é o dono do projeto
            $stmt = $pdo->prepare("SELECT usuario_id FROM projetos WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            if (!$project || $project['usuario_id'] != $user_id) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir este projeto.'
                ];
            }
            
            $stmt = $pdo->prepare("DELETE FROM projetos WHERE id = ?");
            $stmt->execute([$project_id]);
            
            return [
                'success' => true,
                'message' => 'Projeto excluído com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir projeto: ' . $e->getMessage()
            ];
        }
    }
    
    private function registrarAtividade($usuario_id, $acao, $descricao, $tabela_afetada = null, $registro_id = null) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO atividades (usuario_id, acao, descricao, tabela_afetada, registro_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$usuario_id, $acao, $descricao, $tabela_afetada, $registro_id]);
            
        } catch (PDOException $e) {
            // Silencioso - não quebrar o fluxo principal se falhar o registro de atividade
        }
    }
}
?>