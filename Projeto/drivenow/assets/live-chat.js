/**
 * Chat em tempo real DriveNow
 * Este script realiza polling periódico para verificar novas mensagens
 */

// Configuração do sistema de chat
const chatConfig = {
    updateInterval: 5000, // Intervalo de atualização em ms (5 segundos)
    scrollOnNewMessages: true, // Rolar automaticamente para novas mensagens
    playSound: true, // Reproduzir som para novas mensagens
    showNotification: true // Mostrar notificação para novas mensagens
};

// Variáveis globais
let lastMessageTimestamp = null;
let chatUpdateInterval = null;
let messageContainer = null;
let reservaId = null;
let isActiveTab = true;
let unreadMessages = 0;

// Inicializar o sistema de chat
function initializeChat(options = {}) {
    // Mesclar opções
    Object.assign(chatConfig, options);
    
    // Obter o contêiner de mensagens
    messageContainer = document.querySelector('#message-container');
    if (!messageContainer) {
        console.error('Container de mensagens não encontrado');
        return;
    }
    
    // Obter ID da reserva da URL
    const urlParams = new URLSearchParams(window.location.search);
    reservaId = urlParams.get('reserva');
    if (!reservaId) {
        console.error('ID da reserva não encontrado na URL');
        return;
    }
    
    // Obter timestamp da última mensagem
    const mensagens = messageContainer.querySelectorAll('.message-item');
    if (mensagens.length > 0) {
        const ultimaMensagem = mensagens[mensagens.length - 1];
        const dataHora = ultimaMensagem.querySelector('.message-time').textContent.trim();
        lastMessageTimestamp = formatDateToDatabase(dataHora);
    }
    
    // Iniciar polling para novas mensagens
    startChatPolling();
    
    // Monitorar visibilidade da página
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    // Rolar para o final das mensagens
    scrollToBottom();
    
    // Adicionar indicadores de status como enviando/online
    setupStatusIndicators();
    
    console.log('Sistema de chat inicializado com sucesso', {
        reservaId,
        lastMessageTimestamp,
        config: chatConfig
    });
}

// Configurar indicadores de status
function setupStatusIndicators() {
    // Obter o formulário de mensagem
    const messageForm = document.querySelector('form');
    const messageInput = document.querySelector('textarea[name="mensagem"]');
    
    if (!messageForm || !messageInput) {
        return;
    }
    
    // Botão de envio
    const sendButton = messageForm.querySelector('button[type="submit"]');
    
    // Indicador de digitação
    const typingIndicator = document.getElementById('typing-indicator');
    
    // Adicionar efeito ao botão quando estiver digitando
    messageInput.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            sendButton.classList.add('animate-pulse');
        } else {
            sendButton.classList.remove('animate-pulse');
        }
    });
    
    // Esconder indicador de digitação inicialmente
    if (typingIndicator) {
        typingIndicator.style.display = 'none';
    }
}

// Iniciar polling para novas mensagens
function startChatPolling() {
    // Limpar intervalo existente
    if (chatUpdateInterval) {
        clearInterval(chatUpdateInterval);
    }
    
    // Verificar imediatamente
    checkNewMessages();
    
    // Configurar intervalo para verificações periódicas
    chatUpdateInterval = setInterval(checkNewMessages, chatConfig.updateInterval);
}

// Verificar novas mensagens
function checkNewMessages() {
    // Obter o indicador de atualização
    const typingIndicator = document.getElementById('typing-indicator');
    
    // Construir URL para solicitação AJAX
    let url = `../api/verificar_novas_mensagens.php?reserva=${reservaId}`;
    if (lastMessageTimestamp) {
        url += `&timestamp=${encodeURIComponent(lastMessageTimestamp)}`;
    }
    
    // Mostrar indicador durante a verificação (com atraso para evitar flickering)
    let indicatorTimeout = setTimeout(() => {
        if (typingIndicator) {
            typingIndicator.style.display = 'block';
            typingIndicator.querySelector('span').textContent = 'Verificando novas mensagens...';
        }
    }, 500);
    
    // Fazer solicitação AJAX
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Limpar timeout para evitar mostrar o indicador desnecessariamente
            clearTimeout(indicatorTimeout);
            
            // Esconder indicador
            if (typingIndicator) {
                typingIndicator.style.display = 'none';
            }
            
            if (data.success && data.mensagens && data.mensagens.length > 0) {
                // Novas mensagens encontradas
                updateChatWithNewMessages(data.mensagens);
                
                // Atualizar timestamp da última mensagem
                if (data.mensagens.length > 0) {
                    const lastMsg = data.mensagens[data.mensagens.length - 1];
                    lastMessageTimestamp = lastMsg.data_envio;
                }
                
                // Notificar o usuário se a guia não estiver ativa
                if (!isActiveTab && chatConfig.showNotification) {
                    notifyNewMessages(data.mensagens.length);
                }
            }
        })
        .catch(error => {
            // Limpar timeout e esconder indicador em caso de erro
            clearTimeout(indicatorTimeout);
            if (typingIndicator) {
                typingIndicator.style.display = 'none';
            }
            
            console.error('Erro ao verificar novas mensagens:', error);
        });
}

// Atualizar chat com novas mensagens
function updateChatWithNewMessages(mensagens) {
    // Lembrar posição de rolagem
    const wasAtBottom = isScrolledToBottom();
    
    // Adicionar novas mensagens ao container
    mensagens.forEach(msg => {
        const messageElement = createMessageElement(msg);
        messageContainer.appendChild(messageElement);
    });
    
    // Rolar para o final se necessário
    if (wasAtBottom && chatConfig.scrollOnNewMessages) {
        scrollToBottom();
    }
    
    // Reproduzir som se estiver configurado
    if (chatConfig.playSound && !isActiveTab) {
        playNotificationSound();
    }
}

// Criar elemento de mensagem
function createMessageElement(mensagem) {
    // Verificar dados necessários
    if (!mensagem || !mensagem.mensagem || !mensagem.data_envio) {
        console.error('Dados de mensagem inválidos:', mensagem);
        return null;
    }
    
    // Determinar se é uma mensagem enviada pelo usuário atual
    const ehRemetente = mensagem.remetente_id == window.userId;
    
    // Formatar a data para exibição
    const dataEnvio = formatDatabaseDate(mensagem.data_envio);
    
    // Obter nome do remetente (importante para mensagens recebidas)
    const nomeRemetente = mensagem.nome_remetente || 
                         (mensagem.primeiro_nome && mensagem.segundo_nome ? 
                          `${mensagem.primeiro_nome} ${mensagem.segundo_nome}` : 
                          'Usuário');
    
    // Criar elemento da mensagem
    const messageItem = document.createElement('div');
    messageItem.className = `message-item ${ehRemetente ? 'sent' : 'received'}`;
    
    // Construir HTML interno da mensagem
    let innerHtml = '';
    
    // Adicionar nome do remetente apenas para mensagens recebidas
    if (!ehRemetente) {
        innerHtml += `<div class="text-xs text-white/60 mb-1">${nomeRemetente}</div>`;
    }
    
    // Adicionar conteúdo da mensagem e hora
    innerHtml += `
        <div class="message-content">
            ${mensagem.mensagem.replace(/\n/g, '<br>')}
        </div>
        <div class="message-time">
            ${dataEnvio}
        </div>
    `;
    
    // Aplicar HTML ao elemento
    messageItem.innerHTML = innerHtml;
    
    // Adicionar uma pequena animação de entrada
    messageItem.style.opacity = '0';
    messageItem.style.transform = 'translateY(10px)';
    
    // Aplicar animação após um curto atraso
    setTimeout(() => {
        messageItem.style.transition = 'all 0.3s ease';
        messageItem.style.opacity = '1';
        messageItem.style.transform = 'translateY(0)';
    }, 10);
    
    return messageItem;
}

// Verificar se o chat está rolado até o final
function isScrolledToBottom() {
    return messageContainer.scrollHeight - messageContainer.clientHeight <= messageContainer.scrollTop + 50;
}

// Rolar para o final do chat
function scrollToBottom() {
    messageContainer.scrollTop = messageContainer.scrollHeight;
}

// Lidar com a mudança de visibilidade da página
function handleVisibilityChange() {
    isActiveTab = !document.hidden;
    
    if (isActiveTab && unreadMessages > 0) {
        // Resetar contador de mensagens não lidas
        unreadMessages = 0;
        // Atualizar título da página
        document.title = document.title.replace(/^\(\d+\) /, '');
    }
}

// Notificar usuário sobre novas mensagens
function notifyNewMessages(count) {
    unreadMessages += count;
    
    // Atualizar título da página
    const currentTitle = document.title.replace(/^\(\d+\) /, '');
    document.title = `(${unreadMessages}) ${currentTitle}`;
    
    // Mostrar notificação visual
    if (typeof notify === 'function') {
        notify(`Você recebeu ${count} nova${count > 1 ? 's' : ''} mensage${count > 1 ? 'ns' : 'm'}.`, 'info');
    }
}

// Reproduzir som de notificação
function playNotificationSound() {
    // Implementação opcional de som
    try {
        const audio = new Audio('../assets/notification.mp3');
        audio.volume = 0.5;
        audio.play();
    } catch (e) {
        console.error('Erro ao reproduzir som de notificação:', e);
    }
}

// Formatar data do banco para exibição (YYYY-MM-DD HH:MM:SS para DD/MM/YYYY HH:MM)
function formatDatabaseDate(dbDate) {
    if (!dbDate) return '';
    
    try {
        const date = new Date(dbDate.replace(' ', 'T'));
        return `${padZero(date.getDate())}/${padZero(date.getMonth() + 1)}/${date.getFullYear()} ${padZero(date.getHours())}:${padZero(date.getMinutes())}`;
    } catch (e) {
        console.error('Erro ao formatar data:', e, dbDate);
        return dbDate;
    }
}

// Formatar data de exibição para o formato do banco (DD/MM/YYYY HH:MM para YYYY-MM-DD HH:MM:SS)
function formatDateToDatabase(displayDate) {
    if (!displayDate) return '';
    
    try {
        const parts = displayDate.split(' ');
        const dateParts = parts[0].split('/');
        const timeParts = parts[1].split(':');
        
        return `${dateParts[2]}-${dateParts[1]}-${dateParts[0]} ${timeParts[0]}:${timeParts[1]}:00`;
    } catch (e) {
        console.error('Erro ao formatar data para o banco:', e, displayDate);
        return null;
    }
}

// Adicionar zeros à esquerda
function padZero(num) {
    return num.toString().padStart(2, '0');
}

// Inicializar automaticamente
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar depois que a página estiver carregada
    setTimeout(initializeChat, 500);
});
