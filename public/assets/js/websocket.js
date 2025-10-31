class ByManagerWebSocket {
    constructor(userId) {
        this.userId = userId;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectInterval = 3000;
        
        this.connect();
    }

    connect() {
        try {
            this.ws = new WebSocket(`ws://localhost:8080?user_id=${this.userId}`);
            
            this.ws.onopen = () => {
                console.log('WebSocket conectado');
                this.reconnectAttempts = 0;
            };

            this.ws.onmessage = (event) => {
                this.handleMessage(JSON.parse(event.data));
            };

            this.ws.onclose = () => {
                console.log('WebSocket desconectado');
                this.handleReconnect();
            };

            this.ws.onerror = (error) => {
                console.error('Erro WebSocket:', error);
            };

        } catch (error) {
            console.error('Erro ao conectar WebSocket:', error);
        }
    }

    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => {
                console.log(`Tentando reconectar... (${this.reconnectAttempts})`);
                this.connect();
            }, this.reconnectInterval);
        }
    }

    handleMessage(data) {
        const { type, payload } = data;

        switch (type) {
            case 'NEW_MESSAGE':
                this.handleNewMessage(payload);
                break;
            case 'CALL_ACTION':
                this.handleCallAction(payload);
                break;
            case 'CALL_NOTIFICATION':
                this.handleCallNotification(payload);
                break;
            case 'TYPING':
                this.handleTyping(payload);
                break;
            default:
                console.log('Tipo de mensagem desconhecido:', type);
        }
    }

    handleNewMessage(payload) {
        // Disparar evento customizado
        const event = new CustomEvent('newMessage', { detail: payload });
        document.dispatchEvent(event);
    }

    handleCallAction(payload) {
        const event = new CustomEvent('callAction', { detail: payload });
        document.dispatchEvent(event);
    }

    handleCallNotification(payload) {
        const event = new CustomEvent('callNotification', { detail: payload });
        document.dispatchEvent(event);
    }

    handleTyping(payload) {
        const event = new CustomEvent('typing', { detail: payload });
        document.dispatchEvent(event);
    }

    sendMessage(type, payload) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({ type, payload }));
        }
    }

    sendChatMessage(recipientId, message) {
        this.sendMessage('SEND_MESSAGE', {
            recipientId,
            message
        });
    }

    sendCallAction(action, callId, recipientId) {
        this.sendMessage('CALL_ACTION', {
            action,
            callId,
            recipientId
        });
    }

    sendTypingIndicator(recipientId, isTyping) {
        this.sendMessage('TYPING', {
            recipientId,
            isTyping
        });
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

// Inicializar WebSocket quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    if (typeof CURRENT_USER_ID !== 'undefined') {
        window.byManagerWS = new ByManagerWebSocket(CURRENT_USER_ID);
    }
});