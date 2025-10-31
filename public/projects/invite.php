<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';
require_once '../../includes/project_permissions_functions.php';
require_once '../../includes/email.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$project_id = $_GET['project'] ?? null;
if (!$project_id) {
    redirect(BASE_URL . '/projects/index.php');
}

$projectManager = new ProjectManager();
$permissionsManager = new ProjectPermissionsManager();

$project = $projectManager->getProject($project_id, $_SESSION['usuario']['id']);
if (!$project) {
    redirect(BASE_URL . '/projects/index.php');
}

// Verificar se usuário tem permissão para convidar membros
if (!$permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'gerenciar_membros')) {
    $_SESSION['error'] = 'Você não tem permissão para convidar membros para este projeto.';
    redirect(BASE_URL . '/projects/view.php?id=' . $project_id);
}

$error = '';
$success = '';

// Processar convite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_convidado = trim($_POST['email'] ?? '');
    $nivel_permissao = $_POST['nivel_permissao'] ?? 'leitura';
    
    if (empty($email_convidado)) {
        $error = 'Email é obrigatório';
    } else {
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        $data_expiracao = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $db = new Database();
        $pdo = $db->getConnection();
        
        try {
            // Verificar se o email existe na plataforma
            $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
            $stmt->execute([$email_convidado]);
            $usuario_convidado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario_convidado) {
                $error = 'Usuário com este email não encontrado na plataforma.';
            } else {
                // Verificar se já é membro
                $stmt = $pdo->prepare("SELECT * FROM projeto_membros WHERE projeto_id = ? AND usuario_id = ?");
                $stmt->execute([$project_id, $usuario_convidado['id']]);
                if ($stmt->fetch()) {
                    $error = 'Este usuário já é membro do projeto.';
                } else {
                    // Verificar se já existe convite pendente
                    $stmt = $pdo->prepare("SELECT * FROM convites_projeto WHERE projeto_id = ? AND email_convidado = ? AND status = 'pendente' AND data_expiracao > NOW()");
                    $stmt->execute([$project_id, $email_convidado]);
                    if ($stmt->fetch()) {
                        $error = 'Já existe um convite pendente para este usuário.';
                    } else {
                        // Criar convite
                        $stmt = $pdo->prepare("
                            INSERT INTO convites_projeto 
                            (projeto_id, usuario_convidador_id, email_convidado, token, nivel_permissao, data_expiracao) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $project_id, 
                            $_SESSION['usuario']['id'], 
                            $email_convidado, 
                            $token, 
                            $nivel_permissao, 
                            $data_expiracao
                        ]);
                        
                        $invite_id = $pdo->lastInsertId();
                        
                        // Enviar email de convite
                        $emailManager = new EmailManager();
                        $inviteLink = BASE_URL . '/projects/accept_invite.php?token=' . $token;
                        $subject = 'Convite para o projeto ' . $project['nome'];
                        $message = "
                            <h2>Você foi convidado para o projeto {$project['nome']}</h2>
                            <p>Olá {$usuario_convidado['nome']},</p>
                            <p>Você foi convidado por {$_SESSION['usuario']['nome']} para participar do projeto <strong>{$project['nome']}</strong>.</p>
                            <p>Nível de permissão: <strong>" . ucfirst($nivel_permissao) . "</strong></p>
                            <p>Clique no link abaixo para aceitar o convite:</p>
                            <div style='text-align: center; margin: 20px 0;'>
                                <a href='{$inviteLink}' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Aceitar Convite</a>
                            </div>
                            <p>Ou copie e cole este link no seu navegador:<br>
                            <code>{$inviteLink}</code></p>
                            <p><em>Este link expira em 7 dias.</em></p>
                        ";
                        
                        if ($emailManager->sendEmail($email_convidado, $subject, $message)) {
                            $success = 'Convite enviado com sucesso para ' . $email_convidado . '!';
                        } else {
                            $success = 'Convite criado! Link para compartilhar: ' . $inviteLink;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao criar convite: ' . $e->getMessage();
        }
    }
}

// Buscar convites pendentes
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->prepare("
    SELECT c.*, u.nome as convidador_nome 
    FROM convites_projeto c 
    JOIN usuarios u ON c.usuario_convidador_id = u.id 
    WHERE c.projeto_id = ? AND c.status = 'pendente' AND c.data_expiracao > NOW()
    ORDER BY c.data_criacao DESC
");
$stmt->execute([$project_id]);
$convites_pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convidar Membros - <?= $project['nome'] ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="page-header">
            <div>
                <h1>📨 Convidar Membros</h1>
                <p>Projeto: <?= htmlspecialchars($project['nome']) ?></p>
            </div>
            <a href="members.php?project=<?= $project_id ?>" class="btn">← Voltar aos Membros</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="invite-management">
            <!-- Formulário de Convite -->
            <div class="invite-form-section">
                <h2>Enviar Convite por Email</h2>
                <form method="POST" class="form-card">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email do Usuário</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="Digite o email do usuário" value="<?= $_POST['email'] ?? '' ?>">
                            <small>O usuário deve estar cadastrado na plataforma</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="nivel_permissao">Nível de Permissão</label>
                            <select id="nivel_permissao" name="nivel_permissao" required>
                                <option value="leitura" <?= ($_POST['nivel_permissao'] ?? '') == 'leitura' ? 'selected' : '' ?>>Leitura (Apenas visualizar)</option>
                                <option value="edicao" <?= ($_POST['nivel_permissao'] ?? '') == 'edicao' ? 'selected' : '' ?>>Edição (Criar e editar tarefas)</option>
                                <option value="administrador" <?= ($_POST['nivel_permissao'] ?? '') == 'administrador' ? 'selected' : '' ?>>Administrador (Gerenciar membros)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Enviar Convite</button>
                    </div>
                </form>
            </div>

            <!-- Convites Pendentes -->
            <div class="pending-invites-section">
                <h2>Convites Pendentes</h2>
                
                <?php if (empty($convites_pendentes)): ?>
                    <p class="empty-message">Nenhum convite pendente</p>
                <?php else: ?>
                    <div class="invites-grid">
                        <?php foreach ($convites_pendentes as $convite): ?>
                            <div class="invite-card">
                                <div class="invite-info">
                                    <h4><?= htmlspecialchars($convite['email_convidado']) ?></h4>
                                    <div class="invite-details">
                                        <span class="invite-permission">Nível: <?= ucfirst($convite['nivel_permissao']) ?></span>
                                        <span class="invite-sender">Enviado por: <?= htmlspecialchars($convite['convidador_nome']) ?></span>
                                        <span class="invite-expiry">Expira em: <?= date('d/m/Y H:i', strtotime($convite['data_expiracao'])) ?></span>
                                    </div>
                                </div>
                                <div class="invite-actions">
                                    <button onclick="copyInviteLink('<?= $convite['token'] ?>')" class="btn btn-sm" title="Copiar link de convite">📋 Copiar Link</button>
                                    <form method="POST" action="cancel_invite.php" class="inline-form">
                                        <input type="hidden" name="invite_id" value="<?= $convite['id'] ?>">
                                        <input type="hidden" name="project_id" value="<?= $project_id ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Cancelar este convite?')">Cancelar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function copyInviteLink(token) {
        const link = `<?= BASE_URL ?>/projects/accept_invite.php?token=${token}`;
        navigator.clipboard.writeText(link).then(function() {
            alert('Link copiado para a área de transferência!');
        }, function(err) {
            alert('Erro ao copiar link: ' + err);
        });
    }
    </script>

    <style>
    .invite-management {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    .invite-form-section, .pending-invites-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }
    .invites-grid {
        display: grid;
        gap: 15px;
    }
    .invite-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border: 1px solid #e1e1e1;
        border-radius: 8px;
        background: #f8f9fa;
    }
    .invite-info h4 {
        margin: 0 0 10px 0;
        font-size: 16px;
        color: #333;
    }
    .invite-details {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .invite-details span {
        font-size: 12px;
        color: #666;
    }
    .invite-actions {
        display: flex;
        gap: 8px;
    }
    .empty-message {
        text-align: center;
        color: #888;
        font-style: italic;
        padding: 20px;
    }
    </style>
</body>
</html>