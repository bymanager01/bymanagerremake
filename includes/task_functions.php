<?php
require_once 'database.php';

class TaskManager {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function create($titulo, $descricao, $projeto_id, $usuario_id, $prioridade = 'media', $data_limite = null) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO tarefas (titulo, descricao, projeto_id, usuario_id, prioridade, data_limite) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$titulo, $descricao, $projeto_id, $usuario_id, $prioridade, $data_limite]);
            $task_id = $pdo->lastInsertId();
            
            // Registrar atividade
            $this->registrarAtividade($usuario_id, 'criar_tarefa', "Tarefa criada: $titulo", 'tarefas', $task_id);
            
            return [
                'success' => true,
                'id' => $task_id,
                'message' => 'Tarefa criada com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar tarefa: ' . $e->getMessage()
            ];
        }
    }
    
    public function update($task_id, $user_id, $data) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário tem permissão para editar a tarefa
            $stmt = $pdo->prepare("
                SELECT t.usuario_id as criador_id, p.usuario_id as dono_projeto, pm.nivel_permissao
                FROM tarefas t
                INNER JOIN projetos p ON t.projeto_id = p.id
                LEFT JOIN projeto_membros pm ON p.id = pm.projeto_id AND pm.usuario_id = ?
                WHERE t.id = ?
            ");
            $stmt->execute([$user_id, $task_id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada.'
                ];
            }
            
            $permitido = false;
            if ($result['criador_id'] == $user_id) {
                $permitido = true;
            } elseif ($result['dono_projeto'] == $user_id) {
                $permitido = true;
            } elseif ($result['nivel_permissao'] == 'edicao' || $result['nivel_permissao'] == 'administrador') {
                $permitido = true;
            }
            
            if (!$permitido) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para editar esta tarefa.'
                ];
            }
            
            $stmt = $pdo->prepare("
                UPDATE tarefas 
                SET titulo = ?, descricao = ?, prioridade = ?, status = ?, data_limite = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['titulo'],
                $data['descricao'],
                $data['prioridade'],
                $data['status'],
                $data['data_limite'],
                $task_id
            ]);
            
            // Registrar atividade
            $this->registrarAtividade($user_id, 'atualizar_tarefa', "Tarefa atualizada ID: $task_id", 'tarefas', $task_id);
            
            return [
                'success' => true,
                'message' => 'Tarefa atualizada com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar tarefa: ' . $e->getMessage()
            ];
        }
    }
    
    public function getProjectTasks($project_id, $user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Primeiro, verificar se o usuário tem acesso ao projeto
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM projetos p 
                LEFT JOIN projeto_membros pm ON p.id = pm.projeto_id 
                WHERE p.id = ? AND (p.usuario_id = ? OR pm.usuario_id = ?)
            ");
            $stmt->execute([$project_id, $user_id, $user_id]);
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                return []; // Não tem acesso
            }
            
            // Se tem acesso, buscar todas as tarefas do projeto
            $stmt = $pdo->prepare("
                SELECT t.* 
                FROM tarefas t
                WHERE t.projeto_id = ?
                ORDER BY t.ordem ASC, t.data_criacao DESC
            ");
            $stmt->execute([$project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getTask($task_id, $user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar se o usuário tem acesso à tarefa (via projeto)
            $stmt = $pdo->prepare("
                SELECT t.*, p.nome as projeto_nome, p.cor as projeto_cor, u.nome as criador_nome
                FROM tarefas t
                INNER JOIN projetos p ON t.projeto_id = p.id
                INNER JOIN usuarios u ON t.usuario_id = u.id
                LEFT JOIN projeto_membros pm ON p.id = pm.projeto_id AND pm.usuario_id = ?
                WHERE t.id = ? AND (p.usuario_id = ? OR pm.usuario_id IS NOT NULL)
            ");
            $stmt->execute([$user_id, $task_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function delete($task_id, $user_id) {
        try {
            $pdo = $this->db->getConnection();
            
            // Verificar permissões (similar ao update)
            $stmt = $pdo->prepare("
                SELECT t.usuario_id as criador_id, p.usuario_id as dono_projeto, pm.nivel_permissao
                FROM tarefas t
                INNER JOIN projetos p ON t.projeto_id = p.id
                LEFT JOIN projeto_membros pm ON p.id = pm.projeto_id AND pm.usuario_id = ?
                WHERE t.id = ?
            ");
            $stmt->execute([$user_id, $task_id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Tarefa não encontrada.'
                ];
            }
            
            $permitido = false;
            if ($result['criador_id'] == $user_id) {
                $permitido = true;
            } elseif ($result['dono_projeto'] == $user_id) {
                $permitido = true;
            } elseif ($result['nivel_permissao'] == 'administrador') {
                $permitido = true;
            }
            
            if (!$permitido) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir esta tarefa.'
                ];
            }
            
            $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = ?");
            $stmt->execute([$task_id]);
            
            return [
                'success' => true,
                'message' => 'Tarefa excluída com sucesso!'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir tarefa: ' . $e->getMessage()
            ];
        }
    }

    public function getUserTasksWithFilters($user_id, $filters = []) {
        try {
            $pdo = $this->db->getConnection();
            
            $sql = "
                SELECT t.*, p.nome as projeto_nome, p.cor as projeto_cor
                FROM tarefas t
                INNER JOIN projetos p ON t.projeto_id = p.id
                WHERE (p.usuario_id = ? OR EXISTS (
                    SELECT 1 FROM projeto_membros pm 
                    WHERE pm.projeto_id = p.id AND pm.usuario_id = ?
                ))
            ";
            
            $params = [$user_id, $user_id];
            
            // Aplicar filtros
            if (!empty($filters['projeto_id'])) {
                $sql .= " AND t.projeto_id = ?";
                $params[] = $filters['projeto_id'];
            }
            
            if (!empty($filters['prioridade'])) {
                $sql .= " AND t.prioridade = ?";
                $params[] = $filters['prioridade'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['data_limite_inicio'])) {
                $sql .= " AND t.data_limite >= ?";
                $params[] = $filters['data_limite_inicio'];
            }
            
            if (!empty($filters['data_limite_fim'])) {
                $sql .= " AND t.data_limite <= ?";
                $params[] = $filters['data_limite_fim'];
            }
            
            $sql .= " ORDER BY t.data_limite ASC, t.prioridade DESC, t.data_criacao DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    // MÉTODO ALTERNATIVO - para compatibilidade
    public function getUserTasks($user_id, $filters = []) {
        return $this->getUserTasksWithFilters($user_id, $filters);
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
            // Silencioso
        }
    }
}
?>