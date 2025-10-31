<?php
// Verificar se a sessão está ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Adicionar gerenciadores
require_once __DIR__ . '/../../includes/contacts_functions.php';
require_once __DIR__ . '/../../includes/messages_functions.php';
require_once __DIR__ . '/../../includes/notification_functions.php';

$contactsManager = new ContactsManager();
$messagesManager = new MessagesManager();
$notificationManager = new NotificationManager();

$unread_count = $messagesManager->countUnreadMessages($_SESSION['usuario']['id']);
$notifications = $notificationManager->getUserNotifications($_SESSION['usuario']['id'], 5);
?>
<header class="header">
    <nav class="navbar">
        <div class="navbar-brand">
            <a href="<?= BASE_URL ?>/home.php" style="font-size: 1.5em; font-weight: bold; color: var(--primary); text-decoration: none;">
                <?= SITE_NAME ?>
            </a>
        </div>
        
        <div class="navbar-menu">
            <a href="<?= BASE_URL ?>/home.php" class="nav-link">🏠 Dashboard</a>
            <a href="<?= BASE_URL ?>/projects/index.php" class="nav-link">📁 Projetos</a>
            <a href="<?= BASE_URL ?>/tasks/index.php" class="nav-link">✅ Tarefas</a>
            <a href="<?= BASE_URL ?>/communication/index.php" class="nav-link">
                💬 Comunicação
                <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Notificações -->
            <div class="nav-notifications">
                <button class="notification-btn" id="notificationBtn">
                    🔔
                    <?php if ($notificationManager->countUnread($_SESSION['usuario']['id']) > 0): ?>
                        <span class="notification-badge"><?= $notificationManager->countUnread($_SESSION['usuario']['id']) ?></span>
                    <?php endif; ?>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h4>Notificações</h4>
                        <?php if ($notificationManager->countUnread($_SESSION['usuario']['id']) > 0): ?>
                            <button class="mark-all-read" id="markAllRead">Marcar todas como lida</button>
                        <?php endif; ?>
                    </div>
                    <div class="notification-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= !$notification['lida'] ? 'unread' : '' ?>" 
                                 data-id="<?= $notification['id'] ?>">
                                <div class="notification-content">
                                    <strong><?= htmlspecialchars($notification['titulo']) ?></strong>
                                    <p><?= htmlspecialchars($notification['mensagem']) ?></p>
                                    <small><?= date('d/m H:i', strtotime($notification['data_criacao'])) ?></small>
                                </div>
                                <?php if (!$notification['lida']): ?>
                                    <div class="notification-dot"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($notifications)): ?>
                            <p class="no-notifications">Nenhuma notificação</p>
                        <?php endif; ?>
                    </div>
                    <div class="notification-footer">
                        <a href="<?= BASE_URL ?>/notifications.php">Ver todas</a>
                    </div>
                </div>
            </div>
            
            <!-- Usuário -->
            <div class="nav-user">
                <span>👤 <?= $_SESSION['usuario']['nome'] ?></span>
                <div class="user-dropdown">
                    <a href="<?= BASE_URL ?>/profile.php">Meu Perfil</a>
                    <a href="<?= BASE_URL ?>/logout.php">Sair</a>
                </div>
            </div>
        </div>
    </nav>
</header>

<style>
/* Estilos existentes da navbar */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    height: 70px;
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navbar-menu {
    display: flex;
    align-items: center;
    gap: 25px;
}

.nav-link {
    text-decoration: none;
    color: var(--dark);
    font-weight: 500;
    padding: 8px 15px;
    border-radius: 5px;
    transition: all 0.3s;
    position: relative;
}

.nav-link:hover {
    background: var(--light);
    color: var(--primary);
}

.nav-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Estilos para notificações (manter os existentes) */
.nav-notifications {
    position: relative;
}

.notification-btn {
    background: none;
    border: none;
    font-size: 1.2em;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 5px;
    position: relative;
    transition: background 0.3s;
}

.notification-btn:hover {
    background: var(--light);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Estilos para notificações */
.nav-notifications {
    position: relative;
}

.notification-btn {
    background: none;
    border: none;
    font-size: 1.2em;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 5px;
    position: relative;
    transition: background 0.3s;
}

.notification-btn:hover {
    background: var(--light);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    min-width: 350px;
    max-height: 400px;
    display: none;
    flex-direction: column;
    z-index: 1000;
}

.notification-dropdown.show {
    display: flex;
}

.notification-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h4 {
    margin: 0;
    font-size: 16px;
}

.mark-all-read {
    background: none;
    border: none;
    color: var(--primary);
    cursor: pointer;
    font-size: 12px;
    text-decoration: underline;
}

.mark-all-read:hover {
    color: var(--primary-dark);
}

.notification-list {
    flex: 1;
    overflow-y: auto;
    max-height: 300px;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #f8f8f8;
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #f0f7ff;
}

.notification-content {
    flex: 1;
}

.notification-content strong {
    display: block;
    font-size: 13px;
    margin-bottom: 4px;
    color: #333;
}

.notification-content p {
    margin: 0 0 4px 0;
    font-size: 12px;
    color: #666;
    line-height: 1.3;
}

.notification-content small {
    font-size: 11px;
    color: #888;
}

.notification-dot {
    width: 8px;
    height: 8px;
    background: var(--primary);
    border-radius: 50%;
    margin-top: 8px;
}

.no-notifications {
    text-align: center;
    padding: 30px 20px;
    color: #888;
    font-style: italic;
    margin: 0;
}

.notification-footer {
    padding: 12px 20px;
    border-top: 1px solid #f0f0f0;
    text-align: center;
}

.notification-footer a {
    color: var(--primary);
    text-decoration: none;
    font-size: 13px;
}

.notification-footer a:hover {
    text-decoration: underline;
}
</style>

<script>
// Script para notificações (manter o existente)
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            notificationDropdown.classList.remove('show');
        });
    }
});
</script>