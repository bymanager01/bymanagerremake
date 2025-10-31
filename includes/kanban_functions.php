<?php
require_once 'database.php';

class KanbanManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Atualizar posição da tarefa no Kanban
    public function updateTaskPosition($task_id, $usuario_id, $new_status, $new_order) {
        try {
            // Primeiro verificar se a tarefa pertence ao usuário
            $query = "SELECT id FROM tarefas WHERE id = ? AND usuario_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$task_id, $usuario_id]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Tarefa não encontrada'];
            }

            // Atualizar status e ordem
            $query = "UPDATE tarefas SET status = ?, ordem = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$new_status, $new_order, $task_id]);
            
            // Log da atividade
            $this->logAtividade($usuario_id, 'mover_tarefa', "Tarefa movida para {$new_status}", 'tarefas', $task_id);
            
            return ['success' => true];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar tarefa: ' . $e->getMessage()];
        }
    }

    // Buscar estatísticas do Kanban
    public function getKanbanStats($projeto_id, $usuario_id) {
        try {
            $query = "SELECT 
                         status,
                         COUNT(*) as quantidade,
                         SUM(CASE WHEN data_limite < CURDATE() AND status != 'done' THEN 1 ELSE 0 END) as atrasadas
                      FROM tarefas 
                      WHERE projeto_id = ? AND usuario_id = ? 
                      GROUP BY status";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$projeto_id, $usuario_id]);
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            return [];
        }
    }

    private function logAtividade($usuario_id, $acao, $descricao, $tabela = null, $registro_id = null) {
        try {
            $query = "INSERT INTO atividades (usuario_id, acao, descricao, tabela_afetada, registro_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $acao, $descricao, $tabela, $registro_id]);
        } catch(PDOException $e) {
            // Silencioso
        }
    }
}
?>