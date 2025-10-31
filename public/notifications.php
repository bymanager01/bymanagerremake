<?php
require_once '../config/config.php';
require_once '../includes/auth_functions.php';
require_once '../includes/notification_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$notificationManager = new NotificationManager();

// Marcar todas como lidas se solicitado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $notificationManager->markAllAsRead($_SESSION['usuario']['id']);
    redirect(BASE_URL . '/notifications.php');
}

// Buscar todas as notificações
$notifications = $notificationManager->getUserNotifications($_SESSION['usuario']['id'], 50);
$unread_count = $notificationManager->countUnread($_SESSION['usuario']['id']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <div class="page-header">
            <div>
                <h1>Notificações</h1>
                <p>Suas notificações e alertas</p>
            </div>
            <?php if ($unread_count > 0): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="mark_all_read" class="btn btn-primary">
                        Marcar todas como lida
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="notifications-container">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <h2>📭 Nenhuma notificação</h2>
                    <p>Quando você tiver novas notificações, elas aparecerão aqui.</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?= !$notification['lida'] ? 'unread' : '' ?>">
                            <div class="notification-icon">
                                <?php 
                                $icons = [
                                    'deadline' => '⏰',
                                    'project' => '📁',
                                    'task' => '✅',
                                    'system' => '🔔'
                                ];
                                echo $icons[$notification['tipo']] ?? '📋';
                                ?>
                            </div>
                            <div class="notification-content">
                                <h3><?= htmlspecialchars($notification['titulo']) ?></h3>
                                <p><?= htmlspecialchars($notification['mensagem']) ?></p>
                                <div class="notification-meta">
                                    <span class="notification-time">
                                        <?= date('d/m/Y H:i', strtotime($notification['data_criacao'])) ?>
                                    </span>
                                    <?php if (!$notification['lida']): ?>
                                        <span class="unread-badge">Nova</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($notification['link']): ?>
                                <div class="notification-actions">
                                    <a href="<?= $notification['link'] ?>" class="btn btn-sm">Ver</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .notification-card {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .notification-card.unread {
            border-left: 4px solid var(--primary);
            background: #f8fdff;
        }
        .notification-icon {
            font-size: 24px;
            margin-top: 2px;
        }
        .notification-content {
            flex: 1;
        }
        .notification-content h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #333;
        }
        .notification-content p {
            margin: 0 0 10px 0;
            color: #666;
            line-height: 1.4;
        }
        .notification-meta {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notification-time {
            font-size: 12px;
            color: #888;
        }
        .unread-badge {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        .notification-actions {
            flex-shrink: 0;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
    </style>
</body>
</html>