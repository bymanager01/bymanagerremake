<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/contacts_functions.php';
require_once '../../includes/notification_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/communication/index.php');
}

$action = $_POST['action'] ?? '';
$contact_id = $_POST['contact_id'] ?? 0;

if (!$contact_id) {
    $_SESSION['error'] = 'ID do contato não fornecido.';
    redirect(BASE_URL . '/communication/index.php');
}

$contactsManager = new ContactsManager();
$notificationManager = new NotificationManager();

switch ($action) {
    case 'send_request':
    case 'add': // Adicionando o caso 'add' que está sendo usado no search.php
        $result = $contactsManager->sendContactRequest($_SESSION['usuario']['id'], $contact_id);
        if ($result['success']) {
            // Criar notificação para o usuário
            $notificationManager->create(
                $contact_id,
                'solicitacao_contato',
                'Nova solicitação de contato',
                $_SESSION['usuario']['nome'] . ' quer se conectar com você',
                BASE_URL . '/communication/index.php'
            );
            
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        break;

    case 'accept':
        $result = $contactsManager->acceptContactRequest($_SESSION['usuario']['id'], $contact_id);
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        break;

    case 'block':
        $result = $contactsManager->blockContact($_SESSION['usuario']['id'], $contact_id);
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        break;

    default:
        $_SESSION['error'] = 'Ação inválida.';
        break;
}

redirect(BASE_URL . '/communication/index.php');
?>