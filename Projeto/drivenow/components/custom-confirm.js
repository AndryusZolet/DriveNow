function showConfirmDialog(message, onConfirm) {
    // Criar o overlay
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4';
    
    // Criar o modal
    const modal = document.createElement('div');
    modal.className = 'bg-slate-800 border border-white/20 rounded-2xl p-6 max-w-md w-full shadow-2xl transform transition-all';
    modal.innerHTML = `
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-500/20 border border-yellow-400/30 mb-4">
                <svg class="h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-white mb-2">Confirmação</h3>
            <p class="text-white/70 mb-6">${message}</p>
            <div class="flex gap-3 justify-center">
                <button id="confirm-no" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Não
                </button>
                <button id="confirm-yes" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Sim
                </button>
            </div>
        </div>
    `;
    
    overlay.appendChild(modal);
    document.body.appendChild(overlay);
    
    // Adicionar event listeners
    const yesBtn = modal.querySelector('#confirm-yes');
    const noBtn = modal.querySelector('#confirm-no');
    
    yesBtn.addEventListener('click', () => {
        document.body.removeChild(overlay);
        onConfirm();
    });
    
    noBtn.addEventListener('click', () => {
        document.body.removeChild(overlay);
    });
    
    // Fechar ao clicar no overlay
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            document.body.removeChild(overlay);
        }
    });
    
    // Focar no botão "Não" por padrão
    noBtn.focus();
}

// Função para configurar os formulários
function setupCustomConfirms() {
    // Confirmar reserva
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        const form = button.closest('form');
        const acao = form.querySelector('input[name="acao"]')?.value;
        
        if (acao) {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                let message = '';
                switch(acao) {
                    case 'confirmar':
                        message = 'Tem certeza que deseja confirmar esta reserva?';
                        break;
                    case 'rejeitar':
                        message = 'Tem certeza que deseja rejeitar esta reserva?';
                        break;
                    case 'finalizar':
                        message = 'Tem certeza que deseja finalizar esta reserva antecipadamente?';
                        break;
                }
                
                showConfirmDialog(message, () => {
                    form.submit();
                });
            });
        }
    });
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', setupCustomConfirms);