<?php
require_once 'database.php';

class ProjectPermissionsManager {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function checkPermission($project_id, $user_id, $permission) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se é o dono do projeto
            $stmt = $pdo->prepare("SELECT usuario_id FROM projetos WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            if ($project && $project['usuario_id'] == $user_id) {
                return true; // Dono tem todas as permissões
            }
            
            // Verificar permissões do membro
            $stmt = $pdo->prepare("
                SELECT nivel_permissao 
                FROM projeto_membros 
                WHERE projeto_id = ? AND usuario_id = ?
            ");
            $stmt->execute([$project_id, $user_id]);
            $membro = $stmt->fetch();
            
            if (!$membro) {
                return false;
            }
            
            $nivel_permissao = $membro['nivel_permissao'];
            
            // Mapeamento de permissões por nível
            $permissoes_por_nivel = [
                'leitura' => ['ver_projeto', 'ver_tarefas'],
                'edicao' => ['ver_projeto', 'ver_tarefas', 'criar_tarefas', 'editar_tarefas'],
                'administrador' => ['ver_projeto', 'ver_tarefas', 'criar_tarefas', 'editar_tarefas', 'gerenciar_membros']
            ];
            
            return in_array($permission, $permissoes_por_nivel[$nivel_permissao] ?? []);
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getProjectMembers($project_id, $user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário tem permissão para ver membros
            if (!$this->checkPermission($project_id, $user_id, 'gerenciar_membros')) {
                return [];
            }
            
            $stmt = $pdo->prepare("
                SELECT u.id, u.nome, u.email, u.foto, 
                       pm.nivel_permissao, 
                       pm.data_adicao,
                       CASE WHEN p.usuario_id = u.id THEN 1 ELSE 0 END as eh_dono
                FROM projeto_membros pm
                INNER JOIN usuarios u ON pm.usuario_id = u.id
                INNER JOIN projetos p ON pm.projeto_id = p.id
                WHERE pm.projeto_id = ?
                ORDER BY eh_dono DESC, pm.data_adicao ASC
            ");
            
            $stmt->execute([$project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function addMember($project_id, $user_id, $nivel_permissao) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se já é membro
            $stmt = $pdo->prepare("
                SELECT * FROM projeto_membros 
                WHERE projeto_id = ? AND usuario_id = ?
            ");
            $stmt->execute([$project_id, $user_id]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Este usuário já é membro do projeto.'
                ];
            }
            
            // Adicionar membro
            $stmt = $pdo->prepare("
                INSERT INTO projeto_membros (projeto_id, usuario_id, nivel_permissao)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$project_id, $user_id, $nivel_permissao]);
            
            return [
                'success' => true,
                'message' => 'Membro adicionado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao adicionar membro: ' . $e->getMessage()
            ];
        }
    }
    
    public function removeMember($project_id, $user_id, $current_user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário atual tem permissão para remover membros
            if (!$this->checkPermission($project_id, $current_user_id, 'gerenciar_membros')) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para remover membros.'
                ];
            }
            
            // Verificar se não está tentando remover o dono
            $stmt = $pdo->prepare("SELECT usuario_id FROM projetos WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            if ($project && $project['usuario_id'] == $user_id) {
                return [
                    'success' => false,
                    'message' => 'Não é possível remover o dono do projeto.'
                ];
            }
            
            // Remover membro
            $stmt = $pdo->prepare("
                DELETE FROM projeto_membros 
                WHERE projeto_id = ? AND usuario_id = ?
            ");
            $stmt->execute([$project_id, $user_id]);
            
            return [
                'success' => true,
                'message' => 'Membro removido com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao remover membro: ' . $e->getMessage()
            ];
        }
    }
    
    public function updateMemberPermission($project_id, $user_id, $novo_nivel, $current_user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário atual tem permissão para gerenciar membros
            if (!$this->checkPermission($project_id, $current_user_id, 'gerenciar_membros')) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para alterar permissões.'
                ];
            }
            
            // Verificar se não está tentando alterar o dono
            $stmt = $pdo->prepare("SELECT usuario_id FROM projetos WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch();
            
            if ($project && $project['usuario_id'] == $user_id) {
                return [
                    'success' => false,
                    'message' => 'Não é possível alterar as permissões do dono do projeto.'
                ];
            }
            
            // Atualizar permissão
            $stmt = $pdo->prepare("
                UPDATE projeto_membros 
                SET nivel_permissao = ? 
                WHERE projeto_id = ? AND usuario_id = ?
            ");
            $stmt->execute([$novo_nivel, $project_id, $user_id]);
            
            return [
                'success' => true,
                'message' => 'Permissão atualizada com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar permissão: ' . $e->getMessage()
            ];
        }
    }
}
?>