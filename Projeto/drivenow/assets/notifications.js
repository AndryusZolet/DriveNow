/**
 * Sistema de Notificações DriveNow
 */

// Configuração das notificações
const notifyConfig = {
    position: 'top-right', // 'top-right', 'top-left', 'bottom-right', 'bottom-left', 'top-center', 'bottom-center'
    duration: 5000, // Duração em ms (5 segundos)
    maxNotifications: 5, // Máximo de notificações visíveis simultaneamente
    animations: true // Habilitar animações
};

// Container para as notificações
let notifyContainer = null;

// Inicializa o sistema de notificações
function initializeNotifications() {
    // Criar o container se ainda não existir
    if (!notifyContainer) {
        notifyContainer = document.createElement('div');
        notifyContainer.id = 'notification-container';
        
        // Definir estilo baseado na posição
        let positionClass = '';
        switch (notifyConfig.position) {
            case 'top-right':
                positionClass = 'fixed top-4 right-4 z-50 flex flex-col gap-3 items-end';
                break;
            case 'top-left':
                positionClass = 'fixed top-4 left-4 z-50 flex flex-col gap-3 items-start';
                break;
            case 'bottom-right':
                positionClass = 'fixed bottom-4 right-4 z-50 flex flex-col gap-3 items-end';
                break;
            case 'bottom-left':
                positionClass = 'fixed bottom-4 left-4 z-50 flex flex-col gap-3 items-start';
                break;
            case 'top-center':
                positionClass = 'fixed top-4 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-3 items-center';
                break;
            case 'bottom-center':
                positionClass = 'fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-3 items-center';
                break;
            default:
                positionClass = 'fixed top-4 right-4 z-50 flex flex-col gap-3 items-end';
        }
        
        notifyContainer.className = positionClass;
        document.body.appendChild(notifyContainer);
    }
}

// Limitar o número de notificações
function limitNotifications() {
    const notifications = notifyContainer.querySelectorAll('.notification');
    if (notifications.length > notifyConfig.maxNotifications) {
        const toRemove = notifications.length - notifyConfig.maxNotifications;
        for (let i = 0; i < toRemove; i++) {
            notifications[i].remove();
        }
    }
}

// Função principal para mostrar uma notificação
function notify(message, type = 'info', duration = notifyConfig.duration) {
    // Inicializar sistema de notificações se necessário
    initializeNotifications();
    
    // Limitar número máximo de notificações
    limitNotifications();
    
    // Criar a notificação
    const notification = document.createElement('div');
    notification.className = 'notification max-w-sm backdrop-blur-lg bg-white/10 border subtle-border rounded-xl p-4 shadow-lg transform transition-all duration-300 ease-in-out';
    
    // Adicionar classes e ícones específicos para cada tipo
    let icon = '';
    let typeClasses = '';
    
    switch (type) {
        case 'success':
            typeClasses = 'border-green-400/30';
            icon = `<div class="p-2 rounded-lg bg-green-500/30 text-white border border-green-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>`;
            break;
        case 'error':
            typeClasses = 'border-red-400/30';
            icon = `<div class="p-2 rounded-lg bg-red-500/30 text-white border border-red-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>`;
            break;
        case 'warning':
            typeClasses = 'border-yellow-400/30';
            icon = `<div class="p-2 rounded-lg bg-yellow-500/30 text-white border border-yellow-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                    </div>`;
            break;
        default: // info
            typeClasses = 'border-blue-400/30';
            icon = `<div class="p-2 rounded-lg bg-blue-500/30 text-white border border-blue-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>`;
    }
    
    notification.classList.add(typeClasses);
    
    // Estrutura interna da notificação
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            ${icon}
            <div class="flex-1 text-white/90">${message}</div>
            <button type="button" class="text-white/60 hover:text-white p-1" onclick="this.parentNode.parentNode.remove()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    `;
    
    // Animação de entrada
    if (notifyConfig.animations) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(20px)';
    }
    
    // Adicionar ao container
    notifyContainer.appendChild(notification);
    
    // Iniciar animação de entrada
    if (notifyConfig.animations) {
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
    }
    
    // Auto-remover após a duração definida
    if (duration) {
        setTimeout(() => {
            if (notification.parentNode) {
                // Animação de saída
                if (notifyConfig.animations) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(20px)';
                    
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                } else {
                    notification.remove();
                }
            }
        }, duration);
    }
    
    // Retornar o elemento para possibilitar manipulação externa
    return notification;
}

// Função para mostrar notificação de sucesso
function notifySuccess(message, duration) {
    return notify(message, 'success', duration);
}

// Função para mostrar notificação de erro
function notifyError(message, duration) {
    return notify(message, 'error', duration);
}

// Função para mostrar notificação de aviso
function notifyWarning(message, duration) {
    return notify(message, 'warning', duration);
}

// Função para mostrar notificação de informação
function notifyInfo(message, duration) {
    return notify(message, 'info', duration);
}

// Inicializar automaticamente
document.addEventListener('DOMContentLoaded', initializeNotifications);