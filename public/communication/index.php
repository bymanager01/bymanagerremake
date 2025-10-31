<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/contacts_functions.php';
require_once '../../includes/messages_functions.php';
require_once '../../includes/calls_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$contactsManager = new ContactsManager();
$messagesManager = new MessagesManager();
$callsManager = new CallsManager();

$contacts = $contactsManager->getContacts($_SESSION['usuario']['id']);
$conversations = $messagesManager->getConversations($_SESSION['usuario']['id']);
$pending_requests = $contactsManager->getPendingRequests($_SESSION['usuario']['id']);
$unread_count = $messagesManager->countUnreadMessages($_SESSION['usuario']['id']);
$active_call = $callsManager->getActiveCall($_SESSION['usuario']['id']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunicação - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="communication-header">
            <h1>💬 Centro de Comunicação</h1>
            <div class="header-actions">
                <a href="search.php" class="btn btn-primary">🔍 Buscar Usuários</a>
                <a href="contacts.php" class="btn">👥 Meus Contatos</a>
            </div>
        </div>

        <?php if ($active_call): ?>
            <div class="active-call-banner">
                <div class="call-info">
                    <span class="call-icon">📞</span>
                    <div>
                        <strong>Chamada <?= $active_call['tipo'] == 'video' ? 'de Vídeo' : 'de Áudio' ?> Ativa</strong>
                        <p>Com: <?= $active_call['chamador_id'] == $_SESSION['usuario']['id'] ? $active_call['receptor_nome'] : $active_call['chamador_nome'] ?></p>
                    </div>
                </div>
                <a href="call.php?id=<?= $active_call['id'] ?>" class="btn btn-primary">Entrar na Chamada</a>
            </div>
        <?php endif; ?>

        <div class="communication-grid">
            <!-- Conversas Recentes -->
            <div class="communication-card">
                <div class="card-header">
                    <h2>💬 Conversas Recentes</h2>
                    <span class="badge"><?= count($conversations) ?></span>
                </div>
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <p class="empty-message">Nenhuma conversa iniciada</p>
                    <?php else: ?>
                        <?php foreach (array_slice($conversations, 0, 5) as $conversation): ?>
                            <a href="chat.php?contact=<?= $conversation['contact_id'] ?>" class="conversation-item">
                                <div class="contact-avatar">
                                    <?php if ($conversation['foto']): ?>
                                        <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $conversation['foto'] ?>" alt="<?= $conversation['nome'] ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?= substr($conversation['nome'], 0, 1) ?></div>
                                    <?php endif; ?>
                                    <?php if ($conversation['nao_lidas'] > 0): ?>
                                        <span class="unread-badge"><?= $conversation['nao_lidas'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-info">
                                    <h4><?= htmlspecialchars($conversation['nome']) ?></h4>
                                    <p class="last-message"><?= htmlspecialchars($conversation['ultimo_texto'] ?: 'Nenhuma mensagem') ?></p>
                                    <?php if ($conversation['ultima_mensagem']): ?>
                                        <small><?= date('d/m H:i', strtotime($conversation['ultima_mensagem'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($conversations) > 5): ?>
                    <div class="card-footer">
                        <a href="messages.php" class="btn-link">Ver todas as conversas</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contatos Online -->
            <div class="communication-card">
                <div class="card-header">
                    <h2>👥 Meus Contatos</h2>
                    <span class="badge"><?= count($contacts) ?></span>
                </div>
                <div class="contacts-list">
                    <?php if (empty($contacts)): ?>
                        <p class="empty-message">Nenhum contato adicionado</p>
                    <?php else: ?>
                        <?php foreach (array_slice($contacts, 0, 6) as $contact): ?>
                            <div class="contact-item">
                                <div class="contact-avatar">
                                    <?php if ($contact['foto']): ?>
                                        <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $contact['foto'] ?>" alt="<?= $contact['nome'] ?>">
                                    <?php else: ?>
                                        <div class="avatar-placeholder"><?= substr($contact['nome'], 0, 1) ?></div>
                                    <?php endif; ?>
                                    <div class="online-indicator"></div>
                                </div>
                                <div class="contact-info">
                                    <h4><?= htmlspecialchars($contact['nome']) ?></h4>
                                    <p><?= htmlspecialchars($contact['email']) ?></p>
                                </div>
                                <div class="contact-actions">
    <a href="chat.php?contact=<?= $contact['id'] ?>" class="btn-icon" title="Mensagem">💬</a>
    <div class="call-options">
        <form method="POST" action="call_action.php" class="inline-form">
            <input type="hidden" name="action" value="start_call">
            <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
            <input type="hidden" name="type" value="audio">
            <button type="submit" class="btn-icon" title="Chamada de Áudio">🎧</button>
        </form>
        <form method="POST" action="call_action.php" class="inline-form">
            <input type="hidden" name="action" value="start_call">
            <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
            <input type="hidden" name="type" value="video">
            <button type="submit" class="btn-icon" title="Chamada de Vídeo">📹</button>
        </form>
    </div>
</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($contacts) > 6): ?>
                    <div class="card-footer">
                        <a href="contacts.php" class="btn-link">Ver todos os contatos</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Solicitações Pendentes -->
            <div class="communication-card">
                <div class="card-header">
                    <h2>📩 Solicitações</h2>
                    <?php if (!empty($pending_requests)): ?>
                        <span class="badge badge-warning"><?= count($pending_requests) ?></span>
                    <?php endif; ?>
                </div>
                <div class="requests-list">
                    <?php if (empty($pending_requests)): ?>
                        <p class="empty-message">Nenhuma solicitação pendente</p>
                    <?php else: ?>
                        <?php foreach (array_slice($pending_requests, 0, 3) as $request): ?>
                            <div class="request-item">
                                <div class="request-info">
                                    <div class="contact-avatar">
                                        <?php if ($request['foto']): ?>
                                            <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $request['foto'] ?>" alt="<?= $request['nome'] ?>">
                                        <?php else: ?>
                                            <div class="avatar-placeholder"><?= substr($request['nome'], 0, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4><?= htmlspecialchars($request['nome']) ?></h4>
                                        <p><?= htmlspecialchars($request['email']) ?></p>
                                        <small>Solicitado em: <?= date('d/m/Y', strtotime($request['data_solicitacao'])) ?></small>
                                    </div>
                                </div>
                                <div class="request-actions">
                                    <form method="POST" action="contact_action.php" class="inline-form">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="contact_id" value="<?= $request['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">✓ Aceitar</button>
                                    </form>
                                    <form method="POST" action="contact_action.php" class="inline-form">
                                        <input type="hidden" name="action" value="block">
                                        <input type="hidden" name="contact_id" value="<?= $request['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">✗ Recusar</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php if (count($pending_requests) > 3): ?>
                    <div class="card-footer">
                        <a href="requests.php" class="btn-link">Ver todas as solicitações</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Estatísticas -->
            <div class="communication-card">
                <div class="card-header">
                    <h2>📊 Estatísticas</h2>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">💬</div>
                        <div class="stat-info">
                            <span class="stat-number"><?= $unread_count ?></span>
                            <span class="stat-label">Mensagens não lidas</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">👥</div>
                        <div class="stat-info">
                            <span class="stat-number"><?= count($contacts) ?></span>
                            <span class="stat-label">Contatos</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">📞</div>
                        <div class="stat-info">
                            <span class="stat-number"><?= count($callsManager->getUserCalls($_SESSION['usuario']['id'], 100)) ?></span>
                            <span class="stat-label">Chamadas</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">⏰</div>
                        <div class="stat-info">
                            <span class="stat-number"><?= count($pending_requests) ?></span>
                            <span class="stat-label">Solicitações</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .communication-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e1e1;
        }
        .active-call-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .call-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .call-icon {
            font-size: 24px;
        }
        .communication-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        .communication-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .card-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .badge {
            background: var(--primary);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-warning {
            background: var(--warning);
        }
        .conversations-list, .contacts-list, .requests-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .conversation-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: background 0.3s;
        }
        .conversation-item:hover {
            background: #f8f9fa;
        }
        .contact-avatar {
            position: relative;
            flex-shrink: 0;
        }
        .contact-avatar img, .avatar-placeholder {
            width: 45px;
            height: 45px;
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
            font-size: 16px;
        }
        .unread-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }
        .online-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: var(--success);
            border: 2px solid white;
            border-radius: 50%;
        }
        .conversation-info h4, .contact-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .last-message {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 13px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 200px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .contact-item:hover {
            background: #f8f9fa;
        }
        .contact-info {
            flex: 1;
        }
        .contact-info h4 {
            margin: 0 0 2px 0;
        }
        .contact-info p {
            margin: 0;
            color: #666;
            font-size: 12px;
        }
        .contact-actions {
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .contact-item:hover .contact-actions {
            opacity: 1;
        }
        .btn-icon {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn-icon:hover {
            background: #f0f0f0;
        }
        .call-options {
            display: flex;
            gap: 2px;
        }
        .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .request-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .request-info h4 {
            margin: 0 0 2px 0;
            font-size: 14px;
        }
        .request-info p {
            margin: 0 0 2px 0;
            color: #666;
            font-size: 12px;
        }
        .request-info small {
            color: #888;
            font-size: 11px;
        }
        .request-actions {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .btn-success {
            background: var(--success);
            color: white;
        }
        .inline-form {
            display: inline;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stat-icon {
            font-size: 24px;
        }
        .stat-number {
            display: block;
            font-size: 20px;
            font-weight: bold;
            color: var(--primary);
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        .card-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            text-align: center;
        }
        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .empty-message {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
            margin: 0;
        }
        @media (max-width: 1024px) {
            .communication-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>