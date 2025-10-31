<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';
require_once '../../includes/project_permissions_functions.php';
require_once '../../includes/contacts_functions.php';

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
$contactsManager = new ContactsManager();

$project = $projectManager->getProject($project_id, $_SESSION['usuario']['id']);
if (!$project) {
    redirect(BASE_URL . '/projects/index.php');
}

// Verificar se usuário tem permissão para gerenciar membros
if (!$permissionsManager->checkPermission($project_id, $_SESSION['usuario']['id'], 'gerenciar_membros')) {
    $_SESSION['error'] = 'Você não tem permissão para gerenciar membros deste projeto.';
    redirect(BASE_URL . '/projects/view.php?id=' . $project_id);
}

$members = $permissionsManager->getProjectMembers($project_id, $_SESSION['usuario']['id']);
$contacts = $contactsManager->getContacts($_SESSION['usuario']['id']);

// Filtrar contatos que ainda não são membros
$available_contacts = array_filter($contacts, function($contact) use ($members) {
    foreach ($members as $member) {
        if ($member['id'] == $contact['id']) {
            return false;
        }
    }
    return true;
});

$error = '';
$success = '';

// Adicionar membro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $contact_id = $_POST['contact_id'];
    $nivel_permissao = $_POST['nivel_permissao'];
    
    $result = $permissionsManager->addMember($project_id, $contact_id, $nivel_permissao);
    
    if ($result['success']) {
        $success = $result['message'];
        // Recarregar a página para atualizar a lista
        header("Refresh: 0");
        exit;
    } else {
        $error = $result['message'];
    }
}

// Remover membro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
    $membro_id = $_POST['membro_id'];
    
    $result = $permissionsManager->removeMember($project_id, $membro_id, $_SESSION['usuario']['id']);
    
    if ($result['success']) {
        $success = $result['message'];
        header("Refresh: 0");
        exit;
    } else {
        $error = $result['message'];
    }
}

// Atualizar permissão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permission'])) {
    $membro_id = $_POST['membro_id'];
    $novo_nivel = $_POST['novo_nivel'];
    
    $result = $permissionsManager->updateMemberPermission($project_id, $membro_id, $novo_nivel, $_SESSION['usuario']['id']);
    
    if ($result['success']) {
        $success = $result['message'];
        header("Refresh: 0");
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Membros - <?= $project['nome'] ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
<div class="page-header">
    <div>
        <h1>👥 Gerenciar Membros</h1>
        <p>Projeto: <?= htmlspecialchars($project['nome']) ?></p>
    </div>
    <div>
        <a href="invite.php?project=<?= $project_id ?>" class="btn btn-primary">📨 Convidar Membro</a>
        <a href="view.php?id=<?= $project_id ?>" class="btn">← Voltar ao Projeto</a>
    </div>
</div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="members-management">
            <!-- Adicionar Novo Membro -->
            <div class="add-member-section">
                <h2>Adicionar Novo Membro</h2>
                <form method="POST" class="add-member-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_id">Selecionar Contato</label>
                            <select id="contact_id" name="contact_id" required>
                                <option value="">Selecione um contato</option>
                                <?php foreach ($available_contacts as $contact): ?>
                                    <option value="<?= $contact['id'] ?>">
                                        <?= htmlspecialchars($contact['nome']) ?> (<?= $contact['email'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="nivel_permissao">Nível de Permissão</label>
                            <select id="nivel_permissao" name="nivel_permissao" required>
                                <option value="leitura">Leitura (Apenas visualizar)</option>
                                <option value="edicao">Edição (Criar e editar tarefas)</option>
                                <option value="administrador">Administrador (Gerenciar membros)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="add_member" class="btn btn-primary">Adicionar Membro</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lista de Membros -->
            <div class="members-list-section">
                <h2>Membros do Projeto (<?= count($members) ?>)</h2>
                
                <div class="members-grid">
                    <?php foreach ($members as $member): ?>
                        <div class="member-card <?= $member['eh_dono'] ? 'owner' : '' ?>">
                            <div class="member-avatar">
                                <?php if ($member['foto']): ?>
                                    <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $member['foto'] ?>" alt="<?= $member['nome'] ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?= substr($member['nome'], 0, 1) ?></div>
                                <?php endif; ?>
                                <?php if ($member['eh_dono']): ?>
                                    <span class="owner-badge">Dono</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="member-info">
                                <h3><?= htmlspecialchars($member['nome']) ?></h3>
                                <p><?= htmlspecialchars($member['email']) ?></p>
                                <div class="member-meta">
                                    <span class="member-role">
                                        <?php
                                        $role_names = [
                                            'dono' => '👑 Dono do Projeto',
                                            'administrador' => '⚡ Administrador',
                                            'edicao' => '✏️ Editor',
                                            'leitura' => '👀 Leitor'
                                        ];
                                        echo $role_names[$member['nivel_permissao']] ?? $member['nivel_permissao'];
                                        ?>
                                    </span>
                                    <span class="member-since">
                                        Desde: <?= date('d/m/Y', strtotime($member['data_adicao'])) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="member-actions">
                                <?php if (!$member['eh_dono']): ?>
                                    <!-- Alterar Permissão -->
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="membro_id" value="<?= $member['id'] ?>">
                                        <select name="novo_nivel" onchange="this.form.submit()" class="permission-select">
                                            <option value="leitura" <?= $member['nivel_permissao'] == 'leitura' ? 'selected' : '' ?>>Leitura</option>
                                            <option value="edicao" <?= $member['nivel_permissao'] == 'edicao' ? 'selected' : '' ?>>Edição</option>
                                            <option value="administrador" <?= $member['nivel_permissao'] == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                        </select>
                                        <input type="hidden" name="update_permission" value="1">
                                    </form>
                                    
                                    <!-- Remover Membro -->
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="membro_id" value="<?= $member['id'] ?>">
                                        <button type="submit" name="remove_member" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Tem certeza que deseja remover este membro?')">
                                            Remover
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="no-actions">Ações não disponíveis</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e1e1;
        }
        .members-management {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .add-member-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .add-member-section h2 {
            margin: 0 0 20px 0;
            color: var(--dark);
        }
        .add-member-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        .members-list-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .members-list-section h2 {
            margin: 0 0 20px 0;
            color: var(--dark);
        }
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .member-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        .member-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .member-card.owner {
            border-color: #ffd700;
            background: #fffdf0;
        }
        .member-avatar {
            position: relative;
            flex-shrink: 0;
        }
        .member-avatar img, .avatar-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .avatar-placeholder {
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }
        .owner-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ffd700;
            color: #333;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: bold;
        }
        .member-info {
            flex: 1;
        }
        .member-info h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .member-info p {
            margin: 0 0 8px 0;
            color: #666;
            font-size: 14px;
        }
        .member-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .member-role {
            font-size: 12px;
            font-weight: 500;
            color: var(--primary);
        }
        .member-since {
            font-size: 11px;
            color: #888;
        }
        .member-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }
        .inline-form {
            display: flex;
            gap: 8px;
        }
        .permission-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 12px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .btn-danger {
            background: var(--danger);
            color: white;
            border: none;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .no-actions {
            font-size: 11px;
            color: #888;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .add-member-form .form-row {
                grid-template-columns: 1fr;
            }
            .members-grid {
                grid-template-columns: 1fr;
            }
            .member-card {
                flex-direction: column;
                text-align: center;
            }
            .member-actions {
                align-items: center;
            }
        }
    </style>
</body>
</html>