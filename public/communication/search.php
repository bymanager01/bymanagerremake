<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/contacts_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$contactsManager = new ContactsManager();

$search_results = [];
$search_query = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])) {
    $search_query = trim($_GET['q']);
    if (!empty($search_query)) {
        $search_results = $contactsManager->searchUsers($_SESSION['usuario']['id'], $search_query);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Usuários - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="search-header">
            <h1>🔍 Buscar Usuários</h1>
            <a href="index.php" class="btn">← Voltar</a>
        </div>

        <div class="search-container">
            <form method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" 
                           placeholder="Digite o nome ou email do usuário..." required>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['q'])): ?>
                <div class="search-results">
                    <h2>Resultados para "<?= htmlspecialchars($search_query) ?>"</h2>
                    
                    <?php if (empty($search_results)): ?>
                        <p class="no-results">Nenhum usuário encontrado.</p>
                    <?php else: ?>
                        <div class="users-grid">
                            <?php foreach ($search_results as $user): ?>
                                <div class="user-card">
                                    <div class="user-avatar">
                                        <?php if ($user['foto']): ?>
                                            <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $user['foto'] ?>" alt="<?= $user['nome'] ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder"><?= substr($user['nome'], 0, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-info">
                                        <h3><?= htmlspecialchars($user['nome']) ?></h3>
                                        <p><?= htmlspecialchars($user['email']) ?></p>
                                        <?php if ($user['bio']): ?>
                                            <p class="user-bio"><?= htmlspecialchars($user['bio']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-actions">
                                        <?php
                                        $status = $contactsManager->getContactStatus($_SESSION['usuario']['id'], $user['id']);
                                        if ($status === null): ?>
                                            <form method="POST" action="contact_action.php" class="inline-form">
                                                <input type="hidden" name="action" value="send_request">
                                                <input type="hidden" name="contact_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Adicionar</button>
                                            </form>
                                        <?php elseif ($status === 'pendente'): ?>
                                            <span class="status-badge status-pending">Solicitação enviada</span>
                                        <?php elseif ($status === 'aceito'): ?>
                                            <span class="status-badge status-accepted">Já é contato</span>
                                        <?php elseif ($status === 'bloqueado'): ?>
                                            <span class="status-badge status-blocked">Bloqueado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e1e1;
        }
        .search-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .search-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .search-input-group input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 16px;
        }
        .search-results h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .users-grid {
            display: grid;
            gap: 15px;
        }
        .user-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .user-avatar img, .avatar-placeholder {
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
        .user-info {
            flex: 1;
        }
        .user-info h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .user-info p {
            margin: 0 0 5px 0;
            color: #666;
        }
        .user-bio {
            font-style: italic;
            color: #888;
            font-size: 14px;
        }
        .user-actions {
            flex-shrink: 0;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-accepted {
            background: #d4edda;
            color: #155724;
        }
        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }
    </style>
</body>
</html>