<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/notification_functions.php';

// Verificar se é uma requisição AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    exit('Acesso direto não permitido');
}

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    $notificationManager = new NotificationManager();
    
    switch ($action) {
        case 'mark_notification_read':
            $notification_id = $_POST['notification_id'] ?? null;
            if ($notification_id) {
                $success = $notificationManager->markAsRead($notification_id, $_SESSION['usuario']['id']);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
            }
            break;
            
        case 'mark_all_read':
            $success = $notificationManager->markAllAsRead($_SESSION['usuario']['id']);
            echo json_encode(['success' => $success]);
            break;
            
        case 'get_notifications':
            $notifications = $notificationManager->getUserNotifications($_SESSION['usuario']['id'], 5);
            $unread_count = $notificationManager->countUnread($_SESSION['usuario']['id']);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>