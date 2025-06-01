// Sistema de chat em tempo real corrigido
let chatInitialized = false;
let lastMessageId = 0;
let updateTimer = null;
let reservaId = null;
let messageIds = new Set(); // Controle de IDs de mensagens já exibidas

// Função para obter o ID da reserva da URL
function getReservaId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('reserva');
}

// Função para inicializar o sistema de chat
function initializeChat(options = {}) {
    const defaults = {
        updateInterval: 3000,
        scrollOnNewMessages: true,
        showNotification: true,
        isReconnection: false
    };
    
    const settings = { ...defaults, ...options };
    
    reservaId = getReservaId();
    if (!reservaId) {
        console.error('ID da reserva não encontrado');
        return;
    }
    
    console.log('Inicializando chat para reserva:', reservaId);
    
    // Coletar IDs das mensagens já existentes no DOM
    collectExistingMessageIds();
    
    // Definir o último ID de mensagem
    updateLastMessageId();
    
    console.log('Último ID de mensagem:', lastMessageId);
    console.log('IDs de mensagens existentes:', Array.from(messageIds));
    
    chatInitialized = true;
    
    // Iniciar verificação periódica
    startUpdateTimer(settings.updateInterval);
    
    console.log('Chat inicializado com sucesso');
}

// Função para coletar IDs de mensagens já existentes
function collectExistingMessageIds() {
    const existingMessages = document.querySelectorAll('.message-item[data-message-id]');
    existingMessages.forEach(msg => {
        const id = parseInt(msg.getAttribute('data-message-id'));
        if (id) {
            messageIds.add(id);
            lastMessageId = Math.max(lastMessageId, id);
        }
    });
}

// Função para atualizar o último ID de mensagem
function updateLastMessageId() {
    const messages = document.querySelectorAll('.message-item[data-message-id]');
    messages.forEach(msg => {
        const id = parseInt(msg.getAttribute('data-message-id'));
        if (id > lastMessageId) {
            lastMessageId = id;
        }
    });
}

// Função para iniciar o timer de atualização
function startUpdateTimer(interval) {
    // Limpar timer existente
    if (updateTimer) {
        clearInterval(updateTimer);
    }
    
    // Criar novo timer
    updateTimer = setInterval(() => {
        checkNewMessages();
    }, interval);
}

// Função para verificar novas mensagens
function checkNewMessages() {
    if (!chatInitialized || !reservaId) return;
    
    console.log('Verificando novas mensagens... último ID:', lastMessageId);
    
    // Fazer requisição para verificar novas mensagens
    fetch(`check_messages.php?reserva=${reservaId}&last_id=${lastMessageId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta da API:', data);
        
        if (data.success && data.messages && data.messages.length > 0) {
            console.log('Novas mensagens encontradas:', data.messages.length);
            
            // Processar apenas mensagens novas
            const newMessages = data.messages.filter(msg => {
                const msgId = parseInt(msg.id);
                return !messageIds.has(msgId) && msgId > lastMessageId;
            });
            
            console.log('Mensagens realmente novas:', newMessages.length);
            
            if (newMessages.length > 0) {
                addNewMessages(newMessages);
                
                // Atualizar o último ID
                const maxId = Math.max(...newMessages.map(m => parseInt(m.id)));
                lastMessageId = maxId;
                console.log('Novo último ID:', lastMessageId);
            }
        } else {
            console.log('Nenhuma mensagem nova encontrada');
        }
    })
    .catch(error => {
        console.error('Erro ao verificar novas mensagens:', error);
        
        // Em caso de erro, tentar novamente em 10 segundos
        setTimeout(() => {
            if (chatInitialized) {
                console.log('Tentando reconectar...');
                checkNewMessages();
            }
        }, 10000);
    });
}

// Função para adicionar novas mensagens ao chat
function addNewMessages(messages) {
    const container = document.getElementById('message-container');
    if (!container) return;
    
    // Verificar se o usuário está no final do scroll
    const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
    
    messages.forEach(message => {
        const msgId = parseInt(message.id);
        
        // Verificação dupla para evitar duplicatas
        if (messageIds.has(msgId)) return;
        
        // Para mensagens temporárias (IDs grandes), verificar se já existe uma mensagem similar
        if (msgId > 1000000000000) { // IDs temporários são timestamps
            const existingMessages = container.querySelectorAll('.message-item');
            let isDuplicate = false;
            existingMessages.forEach(existingMsg => {
                const existingContent = existingMsg.querySelector('.message-content')?.textContent;
                if (existingContent && existingContent.trim() === message.mensagem.trim()) {
                    const existingTime = existingMsg.querySelector('.message-time')?.textContent;
                    const newTime = formatDateTime(message.data_envio);
                    // Se o conteúdo é igual e o tempo é muito próximo, é duplicata
                    if (Math.abs(new Date(existingTime) - new Date(newTime)) < 60000) { // 1 minuto
                        isDuplicate = true;
                    }
                }
            });
            if (isDuplicate) return;
        }
        
        // Adicionar ID ao conjunto
        messageIds.add(msgId);
        
        // Criar elemento da mensagem
        const messageElement = createMessageElement(message);
        
        // Adicionar ao container
        container.appendChild(messageElement);
        
        // Mostrar notificação se não for mensagem do próprio usuário
        if (message.remetente_id != window.userId) {
            showMessageNotification(message);
        }
    });
    
    // Scroll para o final se estava no final
    if (isAtBottom) {
        container.scrollTop = container.scrollHeight;
    }
    
    // Remover mensagem de "nenhuma mensagem"
    const emptyMessage = container.querySelector('.text-center.py-8');
    if (emptyMessage) {
        emptyMessage.remove();
    }
}

// Função para criar elemento de mensagem
function createMessageElement(message) {
    const div = document.createElement('div');
    const isSent = message.remetente_id == window.userId;
    
    div.className = `message-item ${isSent ? 'sent' : 'received'}`;
    div.setAttribute('data-message-id', message.id);
    
    // Nome do remetente (apenas para mensagens recebidas)
    if (!isSent) {
        const nameDiv = document.createElement('div');
        nameDiv.className = 'text-xs text-white/60 mb-1';
        nameDiv.textContent = `${message.primeiro_nome} ${message.segundo_nome}`;
        div.appendChild(nameDiv);
    }
    
    // Conteúdo da mensagem
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    // Escapar HTML e converter quebras de linha
    const escapedMessage = message.mensagem
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\n/g, '<br>');
    contentDiv.innerHTML = escapedMessage;
    div.appendChild(contentDiv);
    
    // Horário
    const timeDiv = document.createElement('div');
    timeDiv.className = 'message-time';
    timeDiv.textContent = formatDateTime(message.data_envio);
    div.appendChild(timeDiv);
    
    return div;
}

// Função para formatar data e hora
function formatDateTime(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

// Função para mostrar notificação de nova mensagem
function showMessageNotification(message) {
    // Verificar se o usuário permite notificações
    if (window.Notification && Notification.permission === 'granted') {
        const notification = new Notification('Nova mensagem', {
            body: `${message.primeiro_nome}: ${message.mensagem.substring(0, 50)}...`,
            icon: '/favicon.ico'
        });
        
        notification.onclick = function() {
            window.focus();
            notification.close();
        };
    }
}

// Função para parar o timer de atualização
function stopUpdateTimer() {
    if (updateTimer) {
        clearInterval(updateTimer);
        updateTimer = null;
    }
}

// Limpar ao sair da página
window.addEventListener('beforeunload', function() {
    stopUpdateTimer();
});

// Pausar quando a janela perde o foco
window.addEventListener('blur', function() {
    stopUpdateTimer();
});

// Retomar quando a janela ganha o foco
window.addEventListener('focus', function() {
    if (chatInitialized) {
        startUpdateTimer(3000);
        checkNewMessages();
    }
});

// Exportar funções globais
window.initializeChat = initializeChat;
window.checkNewMessages = checkNewMessages;
window.stopUpdateTimer = stopUpdateTimer;