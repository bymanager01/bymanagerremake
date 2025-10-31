<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/contacts_functions.php';
require_once '../../includes/messages_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$contactsManager = new ContactsManager();
$messagesManager = new MessagesManager();

$contact_id = $_GET['contact'] ?? 0;
if (!$contact_id) {
    redirect(BASE_URL . '/communication/index.php');
}

// Verificar se o contato existe e é um contato aceito
$contact = null;
$contacts = $contactsManager->getContacts($_SESSION['usuario']['id']);
foreach ($contacts as $c) {
    if ($c['id'] == $contact_id) {
        $contact = $c;
        break;
    }
}

if (!$contact) {
    $_SESSION['error'] = 'Contato não encontrado ou não aceito.';
    redirect(BASE_URL . '/communication/index.php');
}

// Marcar mensagens como lidas
$messagesManager->markMessagesAsRead($contact_id, $_SESSION['usuario']['id']);

// Buscar mensagens
$messages = $messagesManager->getMessages($_SESSION['usuario']['id'], $contact_id, 100);

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $result = $messagesManager->sendMessage($_SESSION['usuario']['id'], $contact_id, $message);
        if ($result['success']) {
            // Recarregar a página para mostrar a nova mensagem
            redirect($_SERVER['REQUEST_URI']);
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?= htmlspecialchars($contact['nome']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="chat-container">
            <!-- Cabeçalho do Chat -->
            <div class="chat-header">
                <div class="chat-partner">
                    <a href="index.php" class="btn-back">←</a>
                    <div class="partner-avatar">
                        <?php if ($contact['foto']): ?>
                            <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $contact['foto'] ?>" alt="<?= $contact['nome'] ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?= substr($contact['nome'], 0, 1) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="partner-info">
                        <h2><?= htmlspecialchars($contact['nome']) ?></h2>
                        <p><?= htmlspecialchars($contact['email']) ?></p>
                    </div>
                </div>
                <div class="chat-actions">
                    <form method="POST" action="call_action.php" class="inline-form">
                        <input type="hidden" name="action" value="start_call">
                        <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                        <input type="hidden" name="type" value="audio">
                        <button type="submit" class="btn btn-primary" title="Chamada de Áudio">🎧 Áudio</button>
                    </form>
                    <form method="POST" action="call_action.php" class="inline-form">
                        <input type="hidden" name="action" value="start_call">
                        <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                        <input type="hidden" name="type" value="video">
                        <button type="submit" class="btn btn-primary" title="Chamada de Vídeo">📹 Vídeo</button>
                    </form>
                </div>
            </div>

            <!-- Área de Mensagens -->
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        <p>Nenhuma mensagem ainda. Inicie a conversa!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message['direcao'] ?>">
                            <div class="message-content">
                                <p><?= htmlspecialchars($message['mensagem']) ?></p>
                                <span class="message-time">
                                    <?= date('H:i', strtotime($message['data_envio'])) ?>
                                    <?php if ($message['direcao'] == 'sent'): ?>
                                        <?php if ($message['lida']): ?>
                                            ✓✓
                                        <?php else: ?>
                                            ✓
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Formulário de Envio -->
            <div class="chat-input">
                <form method="POST" class="message-form">
                    <div class="input-group">
                        <input type="text" name="message" placeholder="Digite sua mensagem..." required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 80vh;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 2px solid #f0f0f0;
            background: #f8f9fa;
        }
        .chat-partner {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .btn-back {
            text-decoration: none;
            font-size: 18px;
            color: #333;
            padding: 5px;
        }
        .partner-avatar img, .avatar-placeholder {
            width: 50px;
            height: 50px;
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
            font-size: 18px;
        }
        .partner-info h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .partner-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .chat-actions {
            display: flex;
            gap: 10px;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .no-messages {
            text-align: center;
            color: #888;
            padding: 40px;
        }
        .message {
            display: flex;
            max-width: 70%;
        }
        .message.sent {
            align-self: flex-end;
        }
        .message.received {
            align-self: flex-start;
        }
        .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
        }
        .message.sent .message-content {
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 5px;
        }
        .message.received .message-content {
            background: #f0f0f0;
            color: #333;
            border-bottom-left-radius: 5px;
        }
        .message-content p {
            margin: 0 0 5px 0;
            word-wrap: break-word;
        }
        .message-time {
            font-size: 11px;
            opacity: 0.8;
        }
        .chat-input {
            padding: 20px;
            border-top: 2px solid #f0f0f0;
            background: #f8f9fa;
        }
        .input-group {
            display: flex;
            gap: 10px;
        }
        .input-group input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 25px;
            font-size: 16px;
        }
    </style>

    <script>
        // Rolagem automática para a última mensagem
        window.addEventListener('load', function() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    </script>
</body>
</html>