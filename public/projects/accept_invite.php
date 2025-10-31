<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';
require_once '../../includes/project_permissions_functions.php';

$auth = new Auth();

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['error'] = 'Token de convite inválido.';
    redirect(BASE_URL . '/auth/login.php');
}

// Se não estiver logado, redirecionar para login guardando o token
if (!$auth->isLoggedIn()) {
    $_SESSION['invite_token'] = $token;
    $_SESSION['error'] = 'Por favor, faça login para aceitar o convite.';
    redirect(BASE_URL . '/auth/login.php');
}

$db = new Database();
$pdo = $db->getConnection();

// Buscar o convite
$stmt = $pdo->prepare("
    SELECT c.*, p.nome as projeto_nome, p.descricao as projeto_descricao, u.nome as convidador_nome 
    FROM convites_projeto c 
    JOIN projetos p ON c.projeto_id = p.id 
    JOIN usuarios u ON c.usuario_convidador_id = u.id 
    WHERE c.token = ? AND c.status = 'pendente' AND c.data_expiracao > NOW()
");
$stmt->execute([$token]);
$convite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$convite) {
    $_SESSION['error'] = 'Convite inválido, expirado ou já utilizado.';
    redirect(BASE_URL . '/projects/index.php');
}

// Verificar se o usuário logado é o convidado
if ($convite['email_convidado'] !== $_SESSION['usuario']['email']) {
    $_SESSION['error'] = 'Este convite não é para o seu usuário.';
    redirect(BASE_URL . '/projects/index.php');
}

// Aceitar o convite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Verificar se já é membro (caso de duplo clique)
        $stmt = $pdo->prepare("SELECT * FROM projeto_membros WHERE projeto_id = ? AND usuario_id = ?");
        $stmt->execute([$convite['projeto_id'], $_SESSION['usuario']['id']]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Você já é membro deste projeto.';
            redirect(BASE_URL . '/projects/view.php?id=' . $convite['projeto_id']);
        }
        
        // Adicionar como membro
        $stmt = $pdo->prepare("
            INSERT INTO projeto_membros (projeto_id, usuario_id, nivel_permissao) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$convite['projeto_id'], $_SESSION['usuario']['id'], $convite['nivel_permissao']]);
        
        // Atualizar status do convite
        $stmt = $pdo->prepare("
            UPDATE convites_projeto 
            SET status = 'aceito', data_aceitacao = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$convite['id']]);
        
        $pdo->commit();
        
        $_SESSION['success'] = 'Convite aceito! Você agora é um membro do projeto "' . $convite['projeto_nome'] . '".';
        redirect(BASE_URL . '/projects/view.php?id=' . $convite['projeto_id']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Erro ao aceitar convite: ' . $e->getMessage();
        redirect(BASE_URL . '/projects/index.php');
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceitar Convite - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="form-page">
            <div class="form-header">
                <h1>Convite de Projeto</h1>
            </div>
            
            <div class="invite-details">
                <div class="invite-success-icon">
                    <span>📨</span>
                </div>
                
                <h2>Você foi convidado para um projeto!</h2>
                
                <div class="project-info-card">
                    <h3><?= htmlspecialchars($convite['projeto_nome']) ?></h3>
                    <?php if ($convite['projeto_descricao']): ?>
                        <p class="project-description"><?= htmlspecialchars($convite['projeto_descricao']) ?></p>
                    <?php endif; ?>
                    
                    <div class="invite-details-list">
                        <div class="detail-item">
                            <strong>Convidado por:</strong>
                            <span><?= htmlspecialchars($convite['convidador_nome']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong>Nível de permissão:</strong>
                            <span class="permission-badge permission-<?= $convite['nivel_permissao'] ?>">
                                <?= ucfirst($convite['nivel_permissao']) ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <strong>Convite expira em:</strong>
                            <span><?= date('d/m/Y \à\s H:i', strtotime($convite['data_expiracao'])) ?></span>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="invite-actions">
                    <button type="submit" class="btn btn-primary btn-large">✅ Aceitar Convite</button>
                    <a href="<?= BASE_URL ?>/projects/index.php" class="btn btn-outline">❌ Recusar</a>
                </form>
                
                <p class="invite-note">
                    Ao aceitar, você terá acesso ao projeto e poderá visualizar e 
                    <?= $convite['nivel_permissao'] !== 'leitura' ? 'editar ' : '' ?>
                    suas tarefas de acordo com o nível de permissão concedido.
                </p>
            </div>
        </div>
    </div>

    <style>
    .invite-details {
        text-align: center;
        max-width: 500px;
        margin: 0 auto;
    }
    .invite-success-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    .project-info-card {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        margin: 20px 0;
        text-align: left;
    }
    .project-info-card h3 {
        margin: 0 0 10px 0;
        color: var(--primary);
        text-align: center;
    }
    .project-description {
        color: #666;
        margin-bottom: 20px;
        text-align: center;
    }
    .invite-details-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .detail-item:last-child {
        border-bottom: none;
    }
    .permission-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    .permission-leitura {
        background: #e3f2fd;
        color: #1565c0;
    }
    .permission-edicao {
        background: #fff3e0;
        color: #ef6c00;
    }
    .permission-administrador {
        background: #e8f5e8;
        color: #2e7d32;
    }
    .invite-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 25px 0;
    }
    .btn-large {
        padding: 12px 30px;
        font-size: 16px;
    }
    .btn-outline {
        background: transparent;
        border: 2px solid #ddd;
        color: #666;
    }
    .btn-outline:hover {
        background: #f8f9fa;
    }
    .invite-note {
        color: #666;
        font-size: 14px;
        line-height: 1.5;
    }
    </style>
</body>
</html>