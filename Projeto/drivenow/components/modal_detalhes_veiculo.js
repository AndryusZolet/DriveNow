// Funções para gerenciar o modal de detalhes do veículo
function openVeiculoDetalhesModal(veiculoId) {
    console.log("Abrindo modal para veículo ID:", veiculoId);
    
    // Mostrar o modal
    const modal = document.getElementById('veiculoDetalhesModal');
    if (!modal) {
        console.error("Modal não encontrado!");
        return;
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Impedir rolagem do body
    
    // Mostrar o spinner de carregamento
    const modalContent = document.querySelector('#veiculoDetalhesModal #modalContent');
    if (!modalContent) {
        console.error("Elemento #modalContent não encontrado dentro do modal!");
        return;
    }
    
    modalContent.innerHTML = `
        <div class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white"></div>
        </div>
    `;
    
    // Carregar conteúdo via AJAX em vez de iframe
    fetch(`../components/obter_detalhes_veiculo.php?id=${veiculoId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta da rede: ' + response.status);
            }
            return response.text();
        })
        .then(html => {
            modalContent.innerHTML = html;
            
            // Inicializar a funcionalidade de cálculo do valor total
            const reservaDataInput = document.getElementById('reserva_data');
            const devolucaoDataInput = document.getElementById('devolucao_data');
            
            if (reservaDataInput && devolucaoDataInput) {
                const today = new Date().toISOString().split('T')[0];
                reservaDataInput.min = today;
                
                reservaDataInput.addEventListener('change', function() {
                    devolucaoDataInput.min = this.value;
                    if (devolucaoDataInput.value && devolucaoDataInput.value < this.value) {
                        devolucaoDataInput.value = this.value;
                    }
                    calcularValorTotal();
                });
                
                devolucaoDataInput.addEventListener('change', function() {
                    calcularValorTotal();
                });
                
                // Trigger inicial para definir datas mínimas
                if (reservaDataInput.value) {
                    devolucaoDataInput.min = reservaDataInput.value;
                } else {
                    devolucaoDataInput.min = today;
                }
            }
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do veículo:', error);
            modalContent.innerHTML = `
                <div class="text-center p-6 text-white">
                    <p class="text-xl">Erro ao carregar detalhes do veículo.</p>
                    <p class="mt-2 text-white/70">Tente novamente mais tarde.</p>
                </div>
            `;
        });
}

function closeVeiculoDetalhesModal() {
    console.log("Fechando modal de detalhes");
    const modal = document.getElementById('veiculoDetalhesModal');
    if (!modal) return;
    
    modal.classList.add('hidden');
    document.body.style.overflow = ''; // Restaurar rolagem do body
}

// Função para calcular o valor total baseado nas datas
function calcularValorTotal() {
    const reservaData = document.getElementById('reserva_data').value;
    const devolucaoData = document.getElementById('devolucao_data').value;
    const valorTotalElement = document.getElementById('valorTotal');
    
    if (!reservaData || !devolucaoData || !valorTotalElement) return;
    
    const diariaElement = document.getElementById('diaria_valor');
    const taxaUsoElement = document.getElementById('taxa_uso');
    const taxaLimpezaElement = document.getElementById('taxa_limpeza');
    
    if (!diariaElement || !taxaUsoElement || !taxaLimpezaElement) return;
    
    const diaria = parseFloat(diariaElement.dataset.valor);
    const taxaUso = parseFloat(taxaUsoElement.dataset.valor);
    const taxaLimpeza = parseFloat(taxaLimpezaElement.dataset.valor);
    
    const start = new Date(reservaData);
    const end = new Date(devolucaoData);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    const valorTotal = (diaria * diffDays) + taxaUso + taxaLimpeza;
    valorTotalElement.textContent = 'R$ ' + valorTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Adicionar eventos de teclado para fechar o modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeVeiculoDetalhesModal();
    }
});

// Fechar modal quando clicar fora do conteúdo
document.addEventListener('DOMContentLoaded', function() {
    console.log("Inicializando handlers do modal");
    const modal = document.getElementById('veiculoDetalhesModal');
    if (!modal) {
        console.error("Modal não encontrado durante inicialização!");
        return;
    }
    
    modal.addEventListener('click', function(e) {
        // Se o clique foi diretamente no elemento modal (overlay) e não em seus filhos
        if (e.target === modal) {
            closeVeiculoDetalhesModal();
        }
    });
});

// Função para processar a reserva via AJAX
function enviarReserva(event, veiculoId) {
    event.preventDefault();
    console.log("Enviando reserva para veículo ID:", veiculoId);
    
    // Verificar se usuário pode reservar
    const formReserva = document.getElementById('formReserva');
    if (!formReserva) {
        console.error("Formulário de reserva não encontrado!");
        return;
    }
    
    const podeReservar = formReserva.getAttribute('data-pode-reservar');
    if (podeReservar === 'false') {
        window.location.href = '../perfil.php?completar=1';
        return;
    }
    
    // Obter elementos do formulário
    const reservaData = document.getElementById('reserva_data').value;
    const devolucaoData = document.getElementById('devolucao_data').value;
    const observacoes = document.getElementById('observacoes').value;
    const btnSolicitarReserva = document.getElementById('btnSolicitarReserva');
    const reservaErro = document.getElementById('reservaErro');
    const reservaSucesso = document.getElementById('reservaSucesso');
    
    // Validações básicas
    if (!reservaData || !devolucaoData) {
        reservaErro.textContent = 'Por favor, preencha as datas de reserva e devolução.';
        reservaErro.classList.remove('hidden');
        return;
    }
    
    if (new Date(devolucaoData) <= new Date(reservaData)) {
        reservaErro.textContent = 'A data de devolução deve ser posterior à data de reserva.';
        reservaErro.classList.remove('hidden');
        return;
    }
    
    // Esconder mensagens anteriores
    if (reservaErro) reservaErro.classList.add('hidden');
    if (reservaSucesso) reservaSucesso.classList.add('hidden');
    
    // Mostrar estado de carregamento no botão
    if (btnSolicitarReserva) {
        console.log("Desativando botão e mostrando estado de carregamento");
        btnSolicitarReserva.disabled = true;
        btnSolicitarReserva.classList.add('opacity-70', 'cursor-not-allowed');
        
        // Usar textContent diretamente se não houver um span dentro do botão
        const btnTextSpan = btnSolicitarReserva.querySelector('span');
        if (btnTextSpan) {
            btnTextSpan.textContent = 'Processando...';
        } else {
            btnSolicitarReserva.textContent = 'Processando...';
        }
    } else {
        console.error("Botão de solicitar reserva não encontrado!");
    }
    
    // Preparar dados do formulário
    const formData = new FormData();
    formData.append('veiculo_id', veiculoId);
    formData.append('reserva_data', reservaData);
    formData.append('devolucao_data', devolucaoData);
    formData.append('observacoes', observacoes);
    
    // Enviar requisição AJAX
    fetch('../reserva/processar_reserva.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na resposta da rede: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log("Resposta recebida da API:", data);
        
        // Restaurar botão ao estado original se necessário
        if (btnSolicitarReserva) {
            btnSolicitarReserva.disabled = false;
            btnSolicitarReserva.classList.remove('opacity-70', 'cursor-not-allowed');
            
            const btnTextSpan = btnSolicitarReserva.querySelector('span');
            if (btnTextSpan) {
                btnTextSpan.textContent = 'Solicitar Reserva';
            } else {
                btnSolicitarReserva.textContent = 'Solicitar Reserva';
            }
        }
        
        if (data.status === 'success') {
            // Substituir todo o conteúdo do modal para uma mensagem de sucesso
            const modalContent = document.querySelector('#veiculoDetalhesModal #modalContent');
            if (modalContent) {
                console.log("Atualizando modal com mensagem de sucesso");
                modalContent.innerHTML = `
                    <div class="p-6 text-center">
                        <div class="bg-green-500/20 border border-green-400/30 text-white p-6 rounded-xl mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16 mx-auto mb-4 text-green-400">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <h2 class="text-2xl font-bold mb-4">Reserva Realizada com Sucesso!</h2>
                            <p class="mb-2">${data.message}</p>
                            <p class="text-xl font-semibold mt-4">Valor total: R$ ${data.valor_total}</p>
                        </div>
                        <p class="text-white/70 mb-6">Você será redirecionado para suas reservas em instantes...</p>
                        <button type="button" onclick="closeVeiculoDetalhesModal()" class="bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl px-6 py-3 font-medium transition-colors shadow-lg">
                            Fechar
                        </button>
                    </div>
                `;
            } else {
                console.error("Elemento modalContent não encontrado para atualizar mensagem de sucesso!");
                // Plano B: pelo menos esconder o formulário e mostrar mensagem de sucesso
                if (formReserva) formReserva.classList.add('hidden');
                if (reservaSucesso) {
                    reservaSucesso.innerHTML = `
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>${data.message}</span>
                        </div>
                        <p class="mt-2">Valor total: R$ ${data.valor_total}</p>
                        <div class="mt-4 text-center">
                            <button type="button" onclick="closeVeiculoDetalhesModal()" class="bg-green-500 hover:bg-green-600 text-white rounded-xl px-4 py-2 font-medium">Voltar ao Dashboard</button>
                        </div>
                    `;
                    reservaSucesso.classList.remove('hidden');
                }
            }
            
            // Notificar sucesso (se existir função na página principal)
            try {
                notifySuccess(data.message);
            } catch (e) {
                console.error("Função notifySuccess não está disponível:", e);
            }
              // Adicionar redirecionamento após alguns segundos
            setTimeout(() => {
                closeVeiculoDetalhesModal(); // Fechar o modal antes de redirecionar
                window.location.href = data.redirect;
            }, 3000);
        } else {
            console.error("Erro retornado pela API:", data.message);
            // Mostrar mensagem de erro
            if (reservaErro) {
                reservaErro.textContent = data.message;
                reservaErro.classList.remove('hidden');
            }
            
            // Notificar erro (se existir função na página principal)
            try {
                notifyError(data.message);
            } catch (e) {
                console.error("Função notifyError não está disponível:", e);
            }
        }
    })
    .catch(error => {
        console.error('Erro ao processar reserva:', error);
        
        // Restaurar botão ao estado original
        if (btnSolicitarReserva) {
            btnSolicitarReserva.disabled = false;
            btnSolicitarReserva.classList.remove('opacity-70', 'cursor-not-allowed');
            
            const btnTextSpan = btnSolicitarReserva.querySelector('span');
            if (btnTextSpan) {
                btnTextSpan.textContent = 'Solicitar Reserva';
            } else {
                btnSolicitarReserva.textContent = 'Solicitar Reserva';
            }
        }
        
        // Mostrar erro genérico
        if (reservaErro) {
            reservaErro.textContent = 'Erro ao processar a reserva. Tente novamente mais tarde.';
            reservaErro.classList.remove('hidden');
        }
        
        // Notificar erro
        try {
            notifyError('Erro de comunicação com o servidor. Tente novamente.');
        } catch (e) {
            console.error("Função notifyError não está disponível:", e);
        }
    });
}