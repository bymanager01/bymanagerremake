<?php
require_once 'database.php';

class StatisticsManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Estatísticas gerais do usuário
    public function getUserStatistics($usuario_id) {
        try {
            $stats = [];
            
            // Projetos
            $query = "SELECT 
                         COUNT(*) as total_projetos,
                         SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as projetos_concluidos,
                         SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as projetos_ativos
                      FROM projetos 
                      WHERE usuario_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            $stats['projetos'] = $stmt->fetch();
            
            // Tarefas
            $query = "SELECT 
                         COUNT(*) as total_tarefas,
                         SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as tarefas_concluidas,
                         SUM(CASE WHEN status != 'done' THEN 1 ELSE 0 END) as tarefas_pendentes,
                         SUM(CASE WHEN data_limite < CURDATE() AND status != 'done' THEN 1 ELSE 0 END) as tarefas_atrasadas
                      FROM tarefas 
                      WHERE usuario_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            $stats['tarefas'] = $stmt->fetch();
            
            // Tarefas por prioridade
            $query = "SELECT 
                         prioridade,
                         COUNT(*) as quantidade
                      FROM tarefas 
                      WHERE usuario_id = ? 
                      GROUP BY prioridade";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            $stats['prioridades'] = $stmt->fetchAll();
            
            // Tarefas por status
            $query = "SELECT 
                         status,
                         COUNT(*) as quantidade
                      FROM tarefas 
                      WHERE usuario_id = ? 
                      GROUP BY status";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            $stats['status_tarefas'] = $stmt->fetchAll();
            
            // Produtividade semanal
            $query = "SELECT 
                         DATE(data_criacao) as data,
                         COUNT(*) as tarefas_criadas,
                         SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as tarefas_concluidas
                      FROM tarefas 
                      WHERE usuario_id = ? AND data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      GROUP BY DATE(data_criacao)
                      ORDER BY data DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id]);
            $stats['produtividade_semanal'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch(PDOException $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [];
        }
    }

    // Projetos com melhor performance
    public function getTopPerformingProjects($usuario_id, $limit = 5) {
        try {
            $query = "SELECT 
                         p.*,
                         COUNT(t.id) as total_tarefas,
                         SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as tarefas_concluidas,
                         ROUND((SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 2) as percentual_concluido
                      FROM projetos p 
                      LEFT JOIN tarefas t ON p.id = t.projeto_id 
                      WHERE p.usuario_id = ? 
                      GROUP BY p.id 
                      HAVING total_tarefas > 0
                      ORDER BY percentual_concluido DESC 
                      LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $limit]);
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            return [];
        }
    }

    // Tarefas próximas do vencimento
    public function getUpcomingDeadlines($usuario_id, $days = 7) {
        try {
            $query = "SELECT 
                         t.*,
                         p.nome as projeto_nome,
                         p.cor as projeto_cor,
                         DATEDIFF(t.data_limite, CURDATE()) as dias_restantes
                      FROM tarefas t 
                      INNER JOIN projetos p ON t.projeto_id = p.id 
                      WHERE t.usuario_id = ? 
                         AND t.data_limite IS NOT NULL 
                         AND t.status != 'done'
                         AND t.data_limite >= CURDATE() 
                         AND t.data_limite <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                      ORDER BY t.data_limite ASC 
                      LIMIT 10";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $days]);
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            return [];
        }
    }

    // Atividade recente
    public function getRecentActivity($usuario_id, $limit = 10) {
        try {
            $query = "SELECT 
                         a.*,
                         DATE_FORMAT(a.data_criacao, '%d/%m/%Y %H:%i') as data_formatada
                      FROM atividades a 
                      WHERE a.usuario_id = ? 
                      ORDER BY a.data_criacao DESC 
                      LIMIT ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $limit]);
            
            return $stmt->fetchAll();
            
        } catch(PDOException $e) {
            return [];
        }
    }
}
?>