<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/calls_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$callsManager = new CallsManager();

$call_id = $_GET['id'] ?? 0;
if (!$call_id) {
    redirect(BASE_URL . '/communication/index.php');
}

$call = $callsManager->getCall($call_id);
if (!$call) {
    $_SESSION['error'] = 'Chamada não encontrada.';
    redirect(BASE_URL . '/communication/index.php');
}

// Verificar se o usuário tem permissão para ver esta chamada
if ($call['chamador_id'] != $_SESSION['usuario']['id'] && $call['receptor_id'] != $_SESSION['usuario']['id']) {
    $_SESSION['error'] = 'Você não tem permissão para acessar esta chamada.';
    redirect(BASE_URL . '/communication/index.php');
}

// Atualizar status para "em_andamento" se for o receptor e a chamada estava "chamando"
if ($call['receptor_id'] == $_SESSION['usuario']['id'] && $call['status'] == 'chamando') {
    $callsManager->updateCallStatus($call_id, 'em_andamento');
    $call['status'] = 'em_andamento';
}

$partner = $call['chamador_id'] == $_SESSION['usuario']['id'] ? 
    ['id' => $call['receptor_id'], 'nome' => $call['receptor_nome'], 'foto' => $call['receptor_foto']] :
    ['id' => $call['chamador_id'], 'nome' => $call['chamador_nome'], 'foto' => $call['chamador_foto']];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada <?= $call['tipo'] == 'video' ? 'de Vídeo' : 'de Áudio' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="call-page">
    <div class="call-container">
        <!-- Informações da Chamada -->
        <div class="call-info-bar">
            <div class="call-partner">
                <div class="partner-avatar">
                    <?php if ($partner['foto']): ?>
                        <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $partner['foto'] ?>" alt="<?= $partner['nome'] ?>">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?= substr($partner['nome'], 0, 1) ?></div>
                    <?php endif; ?>
                </div>
                <div class="partner-details">
                    <h2><?= htmlspecialchars($partner['nome']) ?></h2>
                    <p class="call-status">
                        <?php if ($call['status'] == 'chamando'): ?>
                            Chamando...
                        <?php elseif ($call['status'] == 'em_andamento'): ?>
                            Chamada em andamento
                        <?php else: ?>
                            Chamada <?= $call['status'] ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="call-duration" id="callDuration">
                00:00
            </div>
        </div>

        <!-- Área de Vídeo -->
        <div class="video-container">
            <!-- Vídeo do Parceiro -->
            <div class="video-wrapper partner-video">
                <video id="partnerVideo" autoplay playsinline></video>
                <div class="video-overlay">
                    <div class="user-info">
                        <div class="avatar">
                            <?php if ($partner['foto']): ?>
                                <img src="<?= BASE_URL ?>/assets/images/avatars/<?= $partner['foto'] ?>" alt="<?= $partner['nome'] ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?= substr($partner['nome'], 0, 1) ?></div>
                            <?php endif; ?>
                        </div>
                        <h3><?= htmlspecialchars($partner['nome']) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Vídeo Local (PIP) -->
            <div class="video-wrapper local-video">
                <video id="localVideo" autoplay playsinline muted></video>
            </div>
        </div>

        <!-- Controles da Chamada -->
        <div class="call-controls">
            <?php if ($call['status'] == 'chamando' && $call['receptor_id'] == $_SESSION['usuario']['id']): ?>
                <!-- Controles para receptor durante chamada pendente -->
                <button class="control-btn accept-btn" onclick="acceptCall()">
                    <span class="btn-icon">📞</span>
                    <span class="btn-text">Atender</span>
                </button>
                <button class="control-btn reject-btn" onclick="rejectCall()">
                    <span class="btn-icon">📵</span>
                    <span class="btn-text">Recusar</span>
                </button>
            <?php else: ?>
                <!-- Controles normais para chamada em andamento -->
                <button class="control-btn mute-btn" onclick="toggleMute()">
                    <span class="btn-icon">🎤</span>
                    <span class="btn-text">Mudo</span>
                </button>
                
                <?php if ($call['tipo'] == 'video'): ?>
                    <button class="control-btn video-btn" onclick="toggleVideo()">
                        <span class="btn-icon">📹</span>
                        <span class="btn-text">Câmera</span>
                    </button>
                <?php endif; ?>
                
                <button class="control-btn end-btn" onclick="endCall()">
                    <span class="btn-icon">📵</span>
                    <span class="btn-text">Encerrar</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Chat durante a chamada (para vídeo) -->
        <?php if ($call['tipo'] == 'video'): ?>
            <div class="call-chat-toggle" onclick="toggleChat()">
                💬
            </div>
            <div class="call-chat" id="callChat">
                <div class="chat-header">
                    <h4>Chat</h4>
                    <button class="close-chat" onclick="toggleChat()">×</button>
                </div>
                <div class="chat-messages" id="callChatMessages"></div>
                <div class="chat-input">
                    <input type="text" id="callChatInput" placeholder="Digite uma mensagem...">
                    <button onclick="sendChatMessage()">Enviar</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Variáveis globais
        let localStream = null;
        let partnerStream = null;
        let callStartTime = Date.now();
        let callDurationInterval = null;
        let isMuted = false;
        let isVideoOff = false;

        // Elementos DOM
        const localVideo = document.getElementById('localVideo');
        const partnerVideo = document.getElementById('partnerVideo');
        const callDuration = document.getElementById('callDuration');

        // Inicializar chamada
        async function initializeCall() {
            try {
                // Solicitar acesso à mídia
                localStream = await navigator.mediaDevices.getUserMedia({
                    video: <?= $call['tipo'] == 'video' ? 'true' : 'false' ?>,
                    audio: true
                });
                
                localVideo.srcObject = localStream;

                // Iniciar temporizador
                startCallTimer();

                // Simular stream do parceiro (em produção, isso viria de WebRTC)
                // partnerVideo.srcObject = partnerStream;

            } catch (error) {
                console.error('Erro ao acessar mídia:', error);
                alert('Erro ao acessar câmera/microfone. Verifique as permissões.');
            }
        }

        // Temporizador da chamada
        function startCallTimer() {
            callStartTime = Date.now();
            callDurationInterval = setInterval(() => {
                const seconds = Math.floor((Date.now() - callStartTime) / 1000);
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                callDuration.textContent = 
                    `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }, 1000);
        }

        // Aceitar chamada
        function acceptCall() {
            // Em produção, isso enviaria um sinal via WebSocket/WebRTC
            window.location.href = '<?= BASE_URL ?>/communication/call.php?id=<?= $call_id ?>';
        }

        // Recusar chamada
        function rejectCall() {
            if (confirm('Recusar esta chamada?')) {
                // Em produção, enviar sinal de recusa
                window.location.href = '<?= BASE_URL ?>/communication/call_action.php?action=reject&call_id=<?= $call_id ?>';
            }
        }

        // Encerrar chamada
        function endCall() {
            if (confirm('Encerrar a chamada?')) {
                // Parar streams
                if (localStream) {
                    localStream.getTracks().forEach(track => track.stop());
                }
                
                // Parar temporizador
                if (callDurationInterval) {
                    clearInterval(callDurationInterval);
                }

                // Redirecionar
                window.location.href = '<?= BASE_URL ?>/communication/call_action.php?action=end&call_id=<?= $call_id ?>';
            }
        }

        // Alternar mudo
        function toggleMute() {
            if (localStream) {
                const audioTracks = localStream.getAudioTracks();
                audioTracks.forEach(track => {
                    track.enabled = !track.enabled;
                });
                isMuted = !isMuted;
                updateControlButtons();
            }
        }

        // Alternar vídeo
        function toggleVideo() {
            if (localStream) {
                const videoTracks = localStream.getVideoTracks();
                videoTracks.forEach(track => {
                    track.enabled = !track.enabled;
                });
                isVideoOff = !isVideoOff;
                updateControlButtons();
            }
        }

        // Atualizar botões de controle
        function updateControlButtons() {
            const muteBtn = document.querySelector('.mute-btn');
            const videoBtn = document.querySelector('.video-btn');
            
            if (muteBtn) {
                muteBtn.querySelector('.btn-icon').textContent = isMuted ? '🎤❌' : '🎤';
                muteBtn.querySelector('.btn-text').textContent = isMuted ? 'Ativar' : 'Mudo';
            }
            
            if (videoBtn) {
                videoBtn.querySelector('.btn-icon').textContent = isVideoOff ? '📹❌' : '📹';
                videoBtn.querySelector('.btn-text').textContent = isVideoOff ? 'Câmera' : 'Desligar';
            }
        }

        // Chat durante chamada
        function toggleChat() {
            const chat = document.getElementById('callChat');
            chat.classList.toggle('open');
        }

        function sendChatMessage() {
            const input = document.getElementById('callChatInput');
            const message = input.value.trim();
            
            if (message) {
                // Em produção, enviar via WebSocket
                const messagesContainer = document.getElementById('callChatMessages');
                const messageElement = document.createElement('div');
                messageElement.className = 'chat-message own';
                messageElement.innerHTML = `
                    <div class="message-content">
                        <p>${message}</p>
                        <span class="message-time">${new Date().toLocaleTimeString()}</span>
                    </div>
                `;
                messagesContainer.appendChild(messageElement);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                input.value = '';
            }
        }

        // Inicializar quando a página carregar
        window.addEventListener('load', initializeCall);

        // Encerrar chamada se a página for fechada
        window.addEventListener('beforeunload', () => {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
        });
    </script>

    <style>
        .call-page {
            background: #1a1a1a;
            color: white;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        .call-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .call-info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(0,0,0,0.8);
            z-index: 10;
        }
        .call-partner {
            display: flex;
            align-items: center;
            gap: 15px;
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
        .partner-details h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .call-status {
            margin: 0;
            color: #ccc;
            font-size: 14px;
        }
        .call-duration {
            font-size: 18px;
            font-weight: bold;
            color: white;
        }
        .video-container {
            flex: 1;
            position: relative;
            background: #000;
        }
        .video-wrapper {
            position: absolute;
            background: #000;
        }
        .partner-video {
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .local-video {
            bottom: 20px;
            right: 20px;
            width: 200px;
            height: 150px;
            border: 2px solid white;
            border-radius: 10px;
            overflow: hidden;
        }
        .video-wrapper video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.5);
        }
        .user-info {
            text-align: center;
        }
        .user-info .avatar img, .user-info .avatar-placeholder {
            width: 80px;
            height: 80px;
            margin-bottom: 15px;
        }
        .user-info h3 {
            margin: 0;
            font-size: 24px;
        }
        .call-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 20px;
            background: rgba(0,0,0,0.8);
        }
        .control-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            justify-content: center;
        }
        .control-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        .control-btn .btn-icon {
            font-size: 24px;
        }
        .control-btn .btn-text {
            font-size: 12px;
        }
        .end-btn {
            background: #e74c3c;
        }
        .end-btn:hover {
            background: #c0392b;
        }
        .accept-btn {
            background: #2ecc71;
        }
        .accept-btn:hover {
            background: #27ae60;
        }
        .reject-btn {
            background: #e74c3c;
        }
        .reject-btn:hover {
            background: #c0392b;
        }
        .call-chat-toggle {
            position: absolute;
            top: 80px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 20;
        }
        .call-chat {
            position: absolute;
            top: 120px;
            right: 20px;
            width: 300px;
            height: 400px;
            background: white;
            border-radius: 10px;
            display: none;
            flex-direction: column;
            z-index: 20;
        }
        .call-chat.open {
            display: flex;
        }
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e1e1e1;
            border-radius: 10px 10px 0 0;
        }
        .chat-header h4 {
            margin: 0;
            color: #333;
        }
        .close-chat {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #fafafa;
        }
        .chat-input {
            display: flex;
            padding: 15px;
            border-top: 1px solid #e1e1e1;
            background: white;
            border-radius: 0 0 10px 10px;
        }
        .chat-input input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            margin-right: 10px;
        }
        .chat-input button {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 15px;
            cursor: pointer;
        }
        .chat-message {
            margin-bottom: 10px;
        }
        .chat-message.own {
            text-align: right;
        }
        .message-content {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 15px;
            background: #e1e1e1;
            color: #333;
            max-width: 80%;
        }
        .chat-message.own .message-content {
            background: var(--primary);
            color: white;
        }
        .message-time {
            font-size: 10px;
            opacity: 0.7;
            display: block;
            margin-top: 5px;
        }
    </style>
</body>
</html>