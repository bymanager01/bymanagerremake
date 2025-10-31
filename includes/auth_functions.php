<?php
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Registrar usuário
    public function registrar($nome, $email, $senha) {
        try {
            // Verificar se email já existe
            $query = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email já cadastrado'];
            }

            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir usuário
            $query = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$nome, $email, $senha_hash]);

            if ($stmt->rowCount() > 0) {
                $this->logAtividade($this->db->lastInsertId(), 'registro', 'Novo usuário registrado: ' . $nome);
                return ['success' => true, 'message' => 'Usuário criado com sucesso'];
            }
            
            return ['success' => false, 'message' => 'Erro ao criar usuário'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    // Login
    public function login($email, $senha) {
        try {
            $query = "SELECT id, nome, email, senha, foto FROM usuarios WHERE email = ? AND status = 'ativo'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 1) {
                $usuario = $stmt->fetch();
                
                if (password_verify($senha, $usuario['senha'])) {
                    $_SESSION['usuario'] = [
                        'id' => $usuario['id'],
                        'nome' => $usuario['nome'],
                        'email' => $usuario['email'],
                        'foto' => $usuario['foto']
                    ];
                    
                    $this->logAtividade($usuario['id'], 'login', 'Usuário fez login no sistema');
                    return ['success' => true, 'message' => 'Login realizado com sucesso'];
                }
            }
            
            return ['success' => false, 'message' => 'Email ou senha incorretos'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    // Verificar se usuário está logado
    public function isLoggedIn() {
        return isset($_SESSION['usuario']);
    }

    // Logout
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logAtividade($_SESSION['usuario']['id'], 'logout', 'Usuário fez logout do sistema');
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    // Registrar atividade
    private function logAtividade($usuario_id, $acao, $descricao, $tabela = null, $registro_id = null) {
        try {
            $query = "INSERT INTO atividades (usuario_id, acao, descricao, tabela_afetada, registro_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$usuario_id, $acao, $descricao, $tabela, $registro_id]);
        } catch(PDOException $e) {
            // Não quebrar o fluxo principal se o log falhar
        }
    }
}

// Funções auxiliares
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>