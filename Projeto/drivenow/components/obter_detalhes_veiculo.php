<?php
// Este arquivo retorna apenas o conteúdo HTML para ser exibido no modal
// Sem a estrutura completa da página

if (!isset($_GET['id'])) {
    echo '<div class="text-center p-6 text-white">
            <p class="text-xl">Erro ao carregar detalhes do veículo.</p>
            <p class="mt-2 text-white/70">ID do veículo não especificado.</p>
          </div>';
    exit;
}

$veiculoId = $_GET['id'];

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
    echo '<div class="text-center p-6 text-white">
            <p class="text-xl">Veículo não encontrado.</p>
            <p class="mt-2 text-white/70">O veículo solicitado não está disponível ou foi removido.</p>
          </div>';
    exit;
}

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
?>

<!-- Conteúdo do modal sem a estrutura HTML completa -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-6">
    <div class="md:col-span-8">
        <div class="bg-gradient-to-r from-indigo-500/20 to-purple-500/20 p-4 rounded-t-2xl flex items-center justify-between">
            <h4 class="text-xl md:text-2xl font-bold text-white">
                <?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?>
            </h4>
            <div class="text-white text-lg font-bold">
                R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?><span class="text-sm font-normal text-white/70">/dia</span>
            </div>
        </div>

        <div class="border border-white/10 rounded-b-2xl bg-white/5 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 p-6">
                <!-- Imagem do Veículo -->
                <div class="md:col-span-4">
                    <div class="bg-gradient-to-br from-indigo-500/30 to-purple-500/30 rounded-xl text-white text-center py-12 mb-3 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-20 w-20">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                            <path d="M7 17h10"/>
                            <circle cx="7" cy="17" r="2"/>
                            <path d="M17 17h2"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                    </div>
                </div>

                <!-- Detalhes do Veículo -->
                <div class="md:col-span-8">
                    <!-- Seção de Descrição do Veículo -->
                    <div class="mb-4">
                        <h5 class="text-lg font-semibold text-white mb-2">Descrição do Veículo</h5>
                        <div class="border border-white/10 p-3 rounded-xl bg-white/5">
                            <?php if (!empty($veiculo['descricao'])): ?>
                                <p class="text-white"><?= nl2br(htmlspecialchars($veiculo['descricao'])) ?></p>
                            <?php else: ?>
                                <p class="text-white/60">O proprietário não forneceu uma descrição para este veículo.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Características do Veículo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6 pt-0">
                <div class="space-y-3">
                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Proprietário:</span>
                        <span><?= htmlspecialchars($veiculo['nome_proprietario']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <path d="M2 10h20"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Ano:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_ano']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                                <circle cx="9" cy="7" r="3"></circle>
                                <path d="M9 10v1"></path>
                                <path d="M15 7h.01"></path>
                                <path d="M15 4h.01"></path>
                                <path d="M18 7h.01"></path>
                                <path d="M18 4h.01"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Placa:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_placa']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M12 12m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0"></path>
                                <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Quilometragem:</span>
                        <span><?= number_format($veiculo['veiculo_km'], 0, ',', '.') ?> km</span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <circle cx="6" cy="12" r="3"></circle>
                                <path d="M6 9v6"></path>
                                <circle cx="18" cy="12" r="3"></circle>
                                <path d="M18 9v6"></path>
                                <path d="M3 12h3"></path>
                                <path d="M15 12h3"></path>
                                <path d="M9 6v12"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Câmbio:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_cambio']) ?></span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M4 20V10a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path>
                                <path d="M12 12v8"></path>
                                <path d="M12 12L8 8"></path>
                                <path d="M12 12l4-4"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Combustível:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_combustivel']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M3 21V3h8v18z"></path>
                                <path d="M11 3h10v18H11z"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Portas:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_portas']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M6 6m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                <path d="M17.657 6.343a3 3 0 1 0 4.243 4.243a3 3 0 0 0 -4.243 -4.243"></path>
                                <path d="M12 19m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                <path d="M17 6.5l-7 4"></path>
                                <path d="M17 17.5l-7 -4"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Assentos:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_acentos']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <circle cx="7" cy="17" r="3"></circle>
                                <circle cx="17" cy="17" r="3"></circle>
                                <path d="M10 17h4"></path>
                                <path d="M15 9l-3-3-3 3"></path>
                                <path d="M12 6v8"></path>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Tração:</span>
                        <span><?= htmlspecialchars($veiculo['veiculo_tracao']) ?></span>
                    </div>

                    <div class="flex items-center text-white">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <span class="text-white/60 mr-2">Localização:</span>
                        <span><?= htmlspecialchars($veiculo['nome_local'] ?? 'Não informada') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="md:col-span-4">
        <div class="border border-white/10 rounded-2xl bg-white/5 h-full">
            <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 p-4 rounded-t-2xl">
                <h4 class="text-xl font-bold text-white">Solicitar Reserva</h4>
            </div>
            <div class="p-6">
                <?php if ($erro): ?>
                    <div class="mb-4 bg-red-500/20 border border-red-400/30 text-white p-3 rounded-xl"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                
                <?php if ($sucesso): ?>
                    <div class="mb-4 bg-green-500/20 border border-green-400/30 text-white p-3 rounded-xl">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span><?= htmlspecialchars($sucesso) ?></span>
                        </div>
                    </div>
                    <button type="button" onclick="closeVeiculoDetalhesModal()" class="w-full bg-green-500 hover:bg-green-600 text-white rounded-xl transition-colors border border-green-400/30 px-4 py-3 font-medium shadow-md hover:shadow-lg flex items-center justify-center">
                        <span>Voltar</span>
                    </button>
                <?php else: ?>
                    <?php if (estaLogado()): ?>
                        <?php if (usuarioPodeReservar()): ?>
                            <form method="POST" id="formReserva" onsubmit="enviarReserva(event, <?= $veiculoId ?>)">
                                <div class="mb-4">
                                    <label for="reserva_data" class="block text-white mb-2">Data de Reserva</label>
                                    <input type="date" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-400" id="reserva_data" name="reserva_data" required>
                                </div>
                                <div class="mb-4">
                                    <label for="devolucao_data" class="block text-white mb-2">Data de Devolução</label>
                                    <input type="date" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-400" id="devolucao_data" name="devolucao_data" required>
                                </div>
                                <div class="mb-4">
                                    <label for="observacoes" class="block text-white mb-2">Observações</label>
                                    <textarea class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-400" id="observacoes" name="observacoes" rows="3"></textarea>
                                </div>
                                
                                <div class="bg-white/10 border border-white/20 rounded-xl p-4 mb-4">
                                    <h5 class="text-white font-medium mb-2">Resumo de Custos</h5>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-white/60">Diária:</span>
                                        <span class="text-white" id="diaria_valor" data-valor="<?= $diariaValor ?>">R$ <?= number_format($diariaValor, 2, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-white/60">Taxa de uso:</span>
                                        <span class="text-white" id="taxa_uso" data-valor="<?= $taxaUso ?>">R$ <?= number_format($taxaUso, 2, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-white/60">Taxa de limpeza:</span>
                                        <span class="text-white" id="taxa_limpeza" data-valor="<?= $taxaLimpeza ?>">R$ <?= number_format($taxaLimpeza, 2, ',', '.') ?></span>
                                    </div>
                                    <div class="border-t border-white/20 mt-2 pt-2 flex justify-between items-center font-medium">
                                        <span class="text-white">Total estimado:</span>
                                        <span class="text-white" id="valorTotal">-</span>
                                    </div>
                                    <p class="text-white/50 text-xs mt-2">* Valor total calculado com base no número de dias selecionados</p>
                                </div>
                                
                                <div id="reservaErro" class="hidden mb-4 bg-red-500/20 border border-red-400/30 text-white p-3 rounded-xl"></div>
                                <div id="reservaSucesso" class="hidden mb-4 bg-green-500/20 border border-green-400/30 text-white p-3 rounded-xl"></div>
                                
                                <button type="submit" id="btnSolicitarReserva" class="w-full bg-green-500 hover:bg-green-600 text-white rounded-xl transition-colors border border-green-400/30 px-4 py-3 font-medium shadow-md hover:shadow-lg flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                        <path d="M8 14h.01"></path>
                                        <path d="M12 14h.01"></path>
                                        <path d="M16 14h.01"></path>
                                        <path d="M8 18h.01"></path>
                                        <path d="M12 18h.01"></path>
                                        <path d="M16 18h.01"></path>
                                    </svg>
                                    <span>Solicitar Reserva</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Você não pode fazer reservas neste veículo. 
                                <a href="../vboard.php" class="alert-link">Voltar ao Dashboard</a>.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-amber-500/20 border border-amber-400/30 text-white p-4 rounded-xl text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 mx-auto mb-3">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
                            </svg>
                            <p class="mb-3">Você precisa estar logado para fazer uma reserva.</p>
                            <div class="flex gap-2 justify-center">
                                <a href="../login.php" class="bg-white/20 hover:bg-white/30 text-white rounded-xl px-4 py-2 text-sm font-medium">Fazer login</a>
                                <a href="../cadastro.php" class="bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl px-4 py-2 text-sm font-medium">Cadastre-se</a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>