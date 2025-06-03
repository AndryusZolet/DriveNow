// Lista completa de modelos por marca
const modelosPorMarca = {
    "Chevrolet": [
        "Onix", "Onix Plus", "Prisma", "Cruze", "Cruze Sport6", "S10", "Tracker",
        "Spin", "Montana", "Cobalt", "Captiva", "Astra", "Vectra", "Classic",
        "Camaro", "Equinox", "Trailblazer", "Silverado", "Bolt EV", "Blazer EV"
    ],
    "Fiat": [
        "Uno", "Palio", "Argo", "Mobi", "Strada", "Toro", "Cronos", "Siena",
        "Idea", "Punto", "Bravo", "Tempra", "Fastback", "Pulse", "Fiorino",
        "Ducato", "500e"
    ],
    "Ford": [
        "Ka", "Fiesta", "Focus", "Ranger", "EcoSport", "Fusion", "Edge",
        "Maverick", "F-150", "Territory", "Bronco", "Mustang"
    ],
    "Volkswagen": [
        "Gol", "Voyage", "Polo", "Virtus", "Fox", "T-Cross", "Nivus", "Taos",
        "Jetta", "Saveiro", "Amarok", "Tiguan Allspace", "Passat", "Golf"
    ],
    "Toyota": [
        "Corolla", "Yaris", "Etios", "Hilux", "SW4", "RAV4", "Camry",
        "Corolla Cross", "Prius", "Yaris Cross", "Supra"
    ],
    "Hyundai": [
        "HB20", "HB20S", "Creta", "Tucson", "Santa Fe", "i30", "Azera",
        "Kona", "ix35", "HR", "IONIQ 5", "Palisade"
    ],
    "Honda": [
        "Civic", "City", "Fit", "HR-V", "WR-V", "Accord", "CR-V",
        "ZR-V", "Civic Type R", "City Hatchback", "City Sedan"
    ],
    "Renault": [
        "KwID", "Sandero", "Logan", "Duster", "Captur", "Oroch", "Fluence",
        "Kardian", "Master", "Zoe", "Kwid E-Tech", "Megane E-Tech", "Kangoo"
    ],
    "Nissan": [
        "March", "Versa", "Kicks", "Frontier", "Sentra", "Altima", "Leaf",
        "X-Trail", "Skyline-R32", "Skyline-R33", "Skyline-R34", "350Z", "370Z"
    ],
    "Peugeot": [
        "208", "2008", "308", "3008", "408", "Partner", "Expert", "Boxer",
        "E-2008", "5008"
    ],
    "Citroën": [
        "C3", "C3 Aircross", "C4 Cactus", "C4 Lounge", "Aircross",
        "Xsara Picasso", "Berlingo", "Jumpy", "Jumper", "DS3", "DS4", "DS5"
    ],
    "Jeep": [
        "Renegade", "Compass", "Commander", "Cherokee", "Wrangler",
        "Gladiator", "Grand Cherokee 4xe"
    ],
    "Mitsubishi": [
        "L200 Triton", "Outlander", "ASX", "Pajero", "Eclipse Cross",
        "Pajero Sport"
    ],
    "Kia": [
        "Sportage", "Cerato", "Sorento", "Soul", "Picanto", "Bongo",
        "Stonic", "Seltos", "EV9", "EV5", "K4", "Niro", "Carnival"
    ],
    "Mercedes-Benz": [
        "A 180", "A 200", "A 250", "A 35 AMG", "A 45 AMG", "B 200", "C 180",
        "C 200", "C 250", "C 300", "C 43 AMG", "C 63 AMG", "CLA 180", "CLA 200",
        "CLA 250", "CLA 35 AMG", "CLA 45 AMG", "CLS 450", "CLS 53 AMG", "E 200",
        "E 300", "E 350", "E 400", "E 43 AMG", "E 63 AMG", "S 500", "S 580",
        "S 63 AMG", "GLA 200", "GLA 250", "GLA 35 AMG", "GLA 45 AMG", "GLB 200",
        "GLB 250", "GLB 35 AMG", "GLC 200", "GLC 250", "GLC 300", "GLC 43 AMG",
        "GLC 63 AMG", "GLE 350", "GLE 400", "GLE 450", "GLE 53 AMG", "GLE 63 AMG",
        "GLS 450", "GLS 580", "GLS 63 AMG", "EQB 250", "EQB 300", "EQB 350",
        "EQC 400", "EQS 450", "EQS 580", "EQS 53 AMG", "SL 400", "SL 500",
        "SL 63 AMG", "SLC 180", "SLC 200", "SLC 300", "SLC 43 AMG", "Sprinter 315",
        "Sprinter 415", "Sprinter 515", "Sprinter 516"
    ],
    "BMW": [
        "118i", "120i", "320i", "330i", "M3", "M4", "X1", "X2", "X3", "X4",
        "X5", "X6", "X7", "Z4", "i3", "i4", "iX", "iX3", "i7"
    ],
    "Audi": [
        "A3", "A4", "A5", "A6", "A7", "A8", "Q3", "Q5", "Q7", "Q8",
        "e-tron", "RS3", "RS4", "RS5", "RS6", "RS7", "TT", "R8"
    ],
    "BYD": [
        "Dolphin", "Seal", "Han", "Tang", "Song", "Yuan Plus", "e1",
        "e2", "e3", "e5", "T3", "T5", "T6", "T7"
    ],
    "Chery": [
        "Arrizo 5", "Arrizo 5 Plus", "Arrizo 5 GT", "Arrizo 6", "Arrizo 8",
        "Tiggo 2", "Tiggo 3x", "Tiggo 5x", "Tiggo 7", "Tiggo 8", "Omoda 5"
    ]
};

// Determinar o caminho base para as requisições AJAX
function getBasePath() {
    // Se a URL contém '/veiculo/', estamos na pasta veiculo
    if (window.location.pathname.includes('/veiculo/')) {
        return '../';
    }
    return './';
}

// Funções para abrir e fechar o modal
function openVeiculoModal() {
    const modal = document.getElementById('veiculoModal');
    modal.classList.remove('hidden');
    
    // Resetar mensagens
    document.getElementById('modalVeiculoError').classList.add('hidden');
    document.getElementById('modalVeiculoError').textContent = '';
    document.getElementById('modalVeiculoSuccess').classList.add('hidden');
    document.getElementById('modalVeiculoSuccess').textContent = '';

    // Carregar modelos caso uma marca já esteja selecionada
    setTimeout(loadModelos, 100);
}

function closeVeiculoModal() {
    const modal = document.getElementById('veiculoModal');
    modal.classList.add('hidden');
    document.getElementById("formAdicionarVeiculo").reset();
}

// Função para verificar se a placa já existe
function checkPlacaExists(placa, inputElement) {
    // Verificar se estamos em modo de edição
    const form = document.getElementById('formAdicionarVeiculo');
    const isEditing = form.querySelector('input[name="acao"]') && 
                     form.querySelector('input[name="acao"]').value === 'editar';
    
    // Se estamos editando e o campo está readonly, não precisamos verificar
    if (isEditing && inputElement.readOnly) {
        return; // Não fazer verificação se estamos editando e o campo é readonly
    }
    
    // Usamos o debounce para não fazer muitas requisições
    clearTimeout(inputElement.timer);
    inputElement.timer = setTimeout(() => {
        // Se estamos editando, precisamos passar o ID do veículo para o backend
        // para que ele possa ignorar este veículo na verificação
        let params = 'placa=' + encodeURIComponent(placa);
        
        if (isEditing) {
            const veiculoId = form.querySelector('input[name="veiculo_id"]').value;
            params += '&veiculo_id=' + encodeURIComponent(veiculoId);
        }
        
        fetch(getBasePath() + 'veiculo/verificar_placa.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(data => {
            const placaError = document.getElementById('placaError');
            
            if (data.exists) {
                // Placa já existe
                inputElement.classList.remove('border-green-500');
                inputElement.classList.add('border-red-500');
                placaError.textContent = 'Esta placa já está cadastrada no sistema.';
                placaError.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Erro ao verificar placa:', error);
        });
    }, 500); // 500ms de debounce
}

// Função auxiliar para enviar o formulário
function submitForm(form) {
    // Mostrar estado de loading no botão
    const btnSubmit = document.getElementById('btnSubmitVeiculo');
    const btnText = btnSubmit.querySelector('span');
    const originalText = btnText.textContent;
    
    btnSubmit.disabled = true;
    btnText.textContent = 'Processando...';
    
    // Coletar dados do formulário
    const formData = new FormData(form);
    
    // Fazer a requisição AJAX
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar botão
        btnSubmit.disabled = false;
        btnText.textContent = originalText;
        
        if (data.status === 'success') {
            // Mostrar mensagem de sucesso
            const successEl = document.getElementById('modalVeiculoSuccess');
            successEl.textContent = data.message;
            successEl.classList.remove('hidden');
            
            // Limpar formulário
            form.reset();
            
            // Mostrar notificação
            if (typeof notifySuccess === 'function') {
                notifySuccess(data.message);
            }
            
            // Fechar modal após 2 segundos e recarregar a página
            // setTimeout(() => {
                closeVeiculoModal();
                window.location.reload();
            // }, 2000);
            
        } else {
            // Mostrar erro
            const errorEl = document.getElementById('modalVeiculoError');
            errorEl.textContent = data.message;
            errorEl.classList.remove('hidden');
            
            // Mostrar notificação
            if (typeof notifyError === 'function') {
                notifyError(data.message);
            }
        }
    })
    .catch(error => {
        // Restaurar botão
        btnSubmit.disabled = false;
        btnText.textContent = originalText;
        
        // Mostrar erro
        const errorEl = document.getElementById('modalVeiculoError');
        errorEl.textContent = 'Erro ao processar a requisição. Tente novamente.';
        errorEl.classList.remove('hidden');
        
        // Mostrar notificação
        if (typeof notifyError === 'function') {
            notifyError('Erro de comunicação com o servidor. Tente novamente.');
        }
        console.error('Erro:', error);
    });
}

// Função para popular modelos baseado na marca selecionada
function loadModelos() {
    const marcaSelecionada = document.getElementById('veiculo_marca').value;
    const modeloSelect = document.getElementById('veiculo_modelo');

    if (marcaSelecionada && modelosPorMarca[marcaSelecionada]) {
        modeloSelect.innerHTML = "<option value=''>Selecione o modelo</option>";
        
        modelosPorMarca[marcaSelecionada].forEach(function(modelo) {
            const option = document.createElement('option');
            option.value = modelo;
            option.text = modelo;
            modeloSelect.appendChild(option);
        });
    }
}

// Quando o documento estiver carregado, adicionar os event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Event listener para verificação de placa
    const placaInput = document.getElementById('veiculo_placa');
    if (placaInput) {
        placaInput.addEventListener('input', function() {
            const placa = this.value.toUpperCase();
            const regexAntiga = /^[A-Z]{3}-[0-9]{4}$/;
            const regexMercosul = /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/;
            const placaError = document.getElementById('placaError');
            
            if (placa.length >= 3) { // Só validamos quando tiver pelo menos 3 caracteres
                if (regexAntiga.test(placa) || regexMercosul.test(placa)) {
                    // Placa em formato válido
                    this.classList.remove('border-red-500');
                    this.classList.add('border-green-500');
                    placaError.style.opacity = '0';
                    
                    // Verificar se a placa já existe (apenas se estiver em formato válido)
                    if (regexAntiga.test(placa) || regexMercosul.test(placa)) {
                        // Podemos fazer uma verificação assíncrona no servidor
                        checkPlacaExists(placa, this);
                    }
                } else {
                    // Placa em formato inválido
                    this.classList.remove('border-green-500');
                    this.classList.add('border-red-500');
                    placaError.style.opacity = '1';
                }
            } else {
                // Campo com menos de 3 caracteres, não valida ainda
                this.classList.remove('border-red-500', 'border-green-500');
                placaError.style.opacity = '0';
            }
        });
    }

    // Event listener para mudança de marca
    const marcaSelect = document.getElementById('veiculo_marca');
    if (marcaSelect) {
        marcaSelect.addEventListener('change', function() {
            loadModelos();
        });
    }

    // Event listeners para a seleção hierárquica de localização
    const estadoSelect = document.getElementById('estado_id');
    const cidadeSelect = document.getElementById('cidade_id');
    const localSelect = document.getElementById('local_id');
    
    if (estadoSelect) {
        // Event listener para mudança de estado
        estadoSelect.addEventListener('change', function() {
            const estadoId = this.value;
            
            // Resetar e desabilitar os selects dependentes
            cidadeSelect.innerHTML = '<option value="">Selecione a cidade</option>';
            cidadeSelect.disabled = true;
            
            localSelect.innerHTML = '<option value="">Selecione o local</option>';
            localSelect.disabled = true;
            
            if (estadoId) {
                // Buscar cidades do estado selecionado
                fetch(getBasePath() + 'veiculo/buscar_cidades.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'estado_id=' + encodeURIComponent(estadoId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.cidades && data.cidades.length > 0) {
                        // Popular o select de cidades
                        data.cidades.forEach(cidade => {
                            const option = document.createElement('option');
                            option.value = cidade.id;
                            option.textContent = cidade.cidade_nome;
                            cidadeSelect.appendChild(option);
                        });
                        
                        // Habilitar o select de cidades
                        cidadeSelect.disabled = false;
                    } else {
                        cidadeSelect.innerHTML = '<option value="">Nenhuma cidade encontrada</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar cidades:', error);
                    cidadeSelect.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                });
            }
        });
        
        // Event listener para mudança de cidade
        cidadeSelect.addEventListener('change', function() {
            const cidadeId = this.value;
            
            // Resetar e desabilitar o select de locais
            localSelect.innerHTML = '<option value="">Selecione o local</option>';
            localSelect.disabled = true;
            
            if (cidadeId) {
                // Buscar locais da cidade selecionada
                fetch(getBasePath() + 'veiculo/buscar_locais.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cidade_id=' + encodeURIComponent(cidadeId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.locais && data.locais.length > 0) {
                        // Popular o select de locais
                        data.locais.forEach(local => {
                            const option = document.createElement('option');
                            option.value = local.id;
                            option.textContent = local.nome_local;
                            localSelect.appendChild(option);
                        });
                        
                        // Habilitar o select de locais
                        localSelect.disabled = false;
                    } else {
                        localSelect.innerHTML = '<option value="">Nenhum local encontrado</option>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar locais:', error);
                    localSelect.innerHTML = '<option value="">Erro ao carregar locais</option>';
                });
            }
        });
    }

    // Event listener para submissão do formulário
    const formVeiculo = document.getElementById('formAdicionarVeiculo');
    if (formVeiculo) {
        formVeiculo.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verificar se estamos em modo de edição
            const isEditing = this.querySelector('input[name="acao"]') && 
                            this.querySelector('input[name="acao"]').value === 'editar';
            
            // Validar a placa antes de enviar
            const placaInput = document.getElementById('veiculo_placa');
            const placa = placaInput.value.toUpperCase();
            const regexAntiga = /^[A-Z]{3}-[0-9]{4}$/;
            const regexMercosul = /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/;
            
            if (!(regexAntiga.test(placa) || regexMercosul.test(placa))) {
                const errorEl = document.getElementById('modalVeiculoError');
                errorEl.textContent = 'Placa inválida. Use o formato ABC-1234 ou ABC1D23';
                errorEl.classList.remove('hidden');
                placaInput.classList.add('border-red-500');
                document.getElementById('placaError').style.opacity = '1';
                placaInput.focus();
                return;
            }
            
            // Se estamos editando e o campo de placa é readonly, podemos pular a verificação
            if (isEditing && placaInput.readOnly) {
                submitForm(this);
                return;
            }
            
            // Verificar se a placa já existe antes de enviar
            let params = 'placa=' + encodeURIComponent(placa);
            
            if (isEditing) {
                const veiculoId = this.querySelector('input[name="veiculo_id"]').value;
                params += '&veiculo_id=' + encodeURIComponent(veiculoId);
            }
            
            fetch(getBasePath() + 'veiculo/verificar_placa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    // Placa já existe, mostrar erro
                    const errorEl = document.getElementById('modalVeiculoError');
                    errorEl.textContent = 'Esta placa já está cadastrada no sistema.';
                    errorEl.classList.remove('hidden');
                    placaInput.classList.add('border-red-500');
                    document.getElementById('placaError').textContent = 'Esta placa já está cadastrada no sistema.';
                    document.getElementById('placaError').style.opacity = '1';
                    placaInput.focus();
                    return;
                } else {
                    // Placa não existe, continuar com o envio
                    submitForm(this);
                }
            })
            .catch(error => {
                console.error('Erro ao verificar placa:', error);
                // Em caso de erro, tentamos enviar o formulário normalmente
                submitForm(this);
            });
        });
    }

    // Event listener para tecla ESC para fechar o modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVeiculoModal();
        }
    });
});