<?php
// Este bloco só será executado se modal_detalhes_veiculo.php for chamado diretamente com um id
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $veiculoId = $_GET['id'];
    
    // Ajustar o caminho de inclusão para o arquivo auth.php
    require_once '../includes/auth.php';
    
    // Buscar detalhes do veículo
    global $pdo;
    $stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local, 
                          CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario
                          FROM veiculo v
                          LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                          LEFT JOIN local l ON v.local_id = l.id
                          LEFT JOIN dono d ON v.dono_id = d.id
                          LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
                          WHERE v.id = ?");
    $stmt->execute([$veiculoId]);
    $veiculo = $stmt->fetch();
    
    if (!$veiculo) {
        $conteudoModal = '<div class="text-center p-6 text-white">
                <p class="text-xl">Veículo não encontrado.</p>
                <p class="mt-2 text-white/70">O veículo solicitado não está disponível ou foi removido.</p>
              </div>';
    } else {
        // Usar o preço diário do veículo
        $diariaValor = $veiculo['preco_diaria'];
        $taxaUso = 20.00;
        $taxaLimpeza = 30.00;
        
        $erro = '';
        $sucesso = '';
        
        // Processar reserva
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && estaLogado()) {
            $reservaData = $_POST['reserva_data'];
            $devolucaoData = $_POST['devolucao_data'];
            $observacoes = trim($_POST['observacoes']);
            
            // Validações
            if (empty($reservaData) || empty($devolucaoData)) {
                $erro = 'Datas de reserva e devolução são obrigatórias.';
            } elseif (strtotime($devolucaoData) <= strtotime($reservaData)) {
                $erro = 'A data de devolução deve ser posterior à data de reserva.';
            } else {
                try {
                    $usuario = getUsuario();
                    
                    // Calcular valor total
                    $dias = (strtotime($devolucaoData) - strtotime($reservaData)) / (60 * 60 * 24);
                    $valorTotal = ($diariaValor * $dias) + $taxaUso + $taxaLimpeza;
                    
                    $stmt = $pdo->prepare("INSERT INTO reserva 
                                          (veiculo_id, conta_usuario_id, reserva_data, devolucao_data, 
                                           diaria_valor, taxas_de_uso, taxas_de_limpeza, valor_total, observacoes)
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $veiculoId,
                        $usuario['id'],
                        $reservaData,
                        $devolucaoData,
                        $diariaValor,
                        $taxaUso,
                        $taxaLimpeza,
                        $valorTotal,
                        $observacoes
                    ]);
                    
                    $sucesso = 'Reserva realizada com sucesso! Valor total: R$ ' . number_format($valorTotal, 2, ',', '.');
                } catch (PDOException $e) {
                    $erro = 'Erro ao processar reserva: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<!-- Modal de Detalhes do Veículo -->
<div id="veiculoDetalhesModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-50 flex items-center justify-center hidden overflow-y-auto">
    <div class="relative w-full max-w-5xl backdrop-blur-lg bg-white/10 border subtle-border rounded-3xl shadow-xl transform transition-all my-8 max-h-[90vh] overflow-y-auto">
        <!-- Este é o elemento onde todo o conteúdo será carregado via AJAX -->
        <div id="modalContent" class="p-6">
            <!-- O conteúdo do modal será carregado via AJAX -->
            <div class="flex justify-center items-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-white"></div>
            </div>
        </div>
        
        <button type="button" onclick="closeVeiculoDetalhesModal()" class="absolute top-4 right-4 text-white/70 hover:text-white p-2 rounded-full bg-black/20 hover:bg-black/40 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
</div>

<script>
    // Configurar datas mínimas no formulário
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const reservaDataInput = document.getElementById('reserva_data');
        const devolucaoDataInput = document.getElementById('devolucao_data');
        
        if (reservaDataInput) {
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
        }
        
        // Trigger inicial para definir datas mínimas
        if (reservaDataInput && devolucaoDataInput) {
            if (reservaDataInput.value) {
                devolucaoDataInput.min = reservaDataInput.value;
            } else {
                devolucaoDataInput.min = today;
            }
        }
    });

    function calcularValorTotal() {
        const reservaData = document.getElementById('reserva_data').value;
        const devolucaoData = document.getElementById('devolucao_data').value;
        const diaria = <?= $diariaValor ?? 0 ?>;
        const taxaUso = <?= $taxaUso ?? 0 ?>;
        const taxaLimpeza = <?= $taxaLimpeza ?? 0 ?>;
        
        if (reservaData && devolucaoData) {
            const start = new Date(reservaData);
            const end = new Date(devolucaoData);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            const valorTotal = (diaria * diffDays) + taxaUso + taxaLimpeza;
            document.getElementById('valorTotal').textContent = 'R$ ' + valorTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        } else {
            document.getElementById('valorTotal').textContent = '-';
        }
    }

    // Função para fechar o modal quando chamado através de iframe
    function closeVeiculoDetalhesModal() {
        // Tentar fechar o modal usando a função do pai se estiver em um iframe
        try {
            if (window.parent && window.parent !== window) {
                window.parent.closeVeiculoDetalhesModal();
            } else {
                const modal = document.getElementById('veiculoDetalhesModal');
                if (modal) modal.classList.add('hidden');
                document.body.style.overflow = ''; // Restaurar rolagem do body
            }
        } catch (e) {
            console.error("Erro ao tentar fechar o modal:", e);
        }
    }
</script>