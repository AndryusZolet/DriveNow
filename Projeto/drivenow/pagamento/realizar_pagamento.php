<?php
require_once '../includes/auth.php';

// Verificar autenticação
if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();
global $pdo;

// Verificar se o ID da reserva foi fornecido
if (!isset($_GET['reserva']) || !is_numeric($_GET['reserva'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não especificada.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

$reservaId = (int)$_GET['reserva'];

// Verificar se a reserva existe e pertence ao usuário
$stmt = $pdo->prepare("
    SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano, 
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario,
           u.id AS proprietario_id
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN dono d ON v.dono_id = d.id
    INNER JOIN conta_usuario u ON d.conta_usuario_id = u.id
    WHERE r.id = ? AND r.conta_usuario_id = ?
");
$stmt->execute([$reservaId, $usuario['id']]);
$reserva = $stmt->fetch();

if (!$reserva) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não encontrada ou você não tem permissão para acessá-la.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

// Verificar se já existe um pagamento para esta reserva
$stmt = $pdo->prepare("SELECT * FROM pagamento WHERE reserva_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$reservaId]);
$pagamento = $stmt->fetch();

// Processar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo = $_POST['metodo_pagamento'] ?? '';
    $status = 'pendente';
    
    // Verificar se o método é válido
    if (!in_array($metodo, ['cartao', 'pix', 'boleto', 'simulacao'])) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Método de pagamento inválido.'
        ];
    } else {
        try {
            // Simulação de processamento de pagamento
            // Em um ambiente real, aqui seria a integração com a API de pagamento
            
            // Opção de simulação para desenvolvimento
            if ($metodo === 'simulacao') {
                $status = $_POST['simulacao_status'] ?? 'aprovado';
                $dataPagamento = 'NOW()';
                $codigoTransacao = 'SIM_' . strtoupper(substr(md5(uniqid()), 0, 10));
                $detalhes = json_encode([
                    'simulacao' => true,
                    'observacao' => 'Pagamento simulado para fins de teste/desenvolvimento'
                ]);
            }
            // Para simulação, vamos aprovar automaticamente pagamentos com cartão
            else if ($metodo === 'cartao') {
                $status = 'aprovado';
                $dataPagamento = 'NOW()';
                $codigoTransacao = 'CARD_' . strtoupper(substr(md5(uniqid()), 0, 10));
                $detalhes = json_encode([
                    'titular' => $_POST['titular_cartao'] ?? 'N/D',
                    'ultimos_digitos' => substr($_POST['numero_cartao'] ?? '0000', -4),
                    'bandeira' => 'Visa',
                    'parcelas' => $_POST['parcelas'] ?? 1
                ]);
            } 
            // PIX fica pendente até confirmação
            else if ($metodo === 'pix') {
                $status = 'pendente';
                $dataPagamento = 'NULL';
                $codigoTransacao = 'PIX_' . strtoupper(substr(md5(uniqid()), 0, 10));
                $detalhes = json_encode([
                    'chave_pix' => 'drivenow@email.com',
                    'qr_code' => 'BASE64_SIMULADO_QR_CODE',
                    'expiracao' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
                ]);
            } 
            // Boleto fica pendente até confirmação
            else if ($metodo === 'boleto') {
                $status = 'pendente';
                $dataPagamento = 'NULL';
                $codigoTransacao = 'BOLETO_' . strtoupper(substr(md5(uniqid()), 0, 10));
                $detalhes = json_encode([
                    'codigo_barras' => '12345.67890 12345.678901 12345.678901 1 12345678901234',
                    'data_vencimento' => date('Y-m-d', strtotime('+3 days')),
                    'url_boleto' => 'https://exemplo.com/boleto/' . $codigoTransacao
                ]);
            }
            
            // Inserir o pagamento no banco de dados
            $stmt = $pdo->prepare("
                INSERT INTO pagamento (reserva_id, valor, metodo_pagamento, status, data_pagamento, codigo_transacao, detalhes)
                VALUES (?, ?, ?, ?, " . $dataPagamento . ", ?, ?)
            ");
            $stmt->execute([
                $reservaId,
                $reserva['valor_total'],
                $metodo,
                $status,
                $codigoTransacao,
                $detalhes
            ]);
            
            $pagamentoId = $pdo->lastInsertId();
            
            // Registrar no histórico
            $stmt = $pdo->prepare("
                INSERT INTO historico_pagamento (pagamento_id, status_anterior, novo_status, observacao, usuario_id)
                VALUES (?, NULL, ?, 'Pagamento iniciado', ?)
            ");
            $stmt->execute([$pagamentoId, $status, $usuario['id']]);
            
            
            // Se o pagamento foi aprovado, atualizar o status da reserva
            if ($status === 'aprovado') {
                // Modificado: Agora o status fica como 'pago' em vez de 'confirmada'
                // para que o proprietário possa confirmar posteriormente
                $stmt = $pdo->prepare("UPDATE reserva SET status = 'pago' WHERE id = ?");
                $stmt->execute([$reservaId]);
                
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Pagamento aprovado com sucesso! Aguardando confirmação do proprietário.'
                ];
            } else {
                $_SESSION['notification'] = [
                    'type' => 'info',
                    'message' => 'Pagamento iniciado. Aguardando confirmação.'
                ];
            }
            
            // Redirecionar para a página de detalhes do pagamento
            header("Location: detalhe_pagamento.php?id={$pagamentoId}");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ];
        }
    }
}

// Formatar valores
$valorTotal = number_format($reserva['valor_total'], 2, ',', '.');
$diariaValor = number_format($reserva['diaria_valor'], 2, ',', '.');
$taxaUso = number_format($reserva['taxas_de_uso'], 2, ',', '.');
$taxaLimpeza = number_format($reserva['taxas_de_limpeza'], 2, ',', '.');

// Calcular número de dias
$dataInicio = new DateTime($reserva['reserva_data']);
$dataFim = new DateTime($reserva['devolucao_data']);
$dias = $dataInicio->diff($dataFim)->days;

// Formatar datas
$dataInicioFormatada = $dataInicio->format('d/m/Y');
$dataFimFormatada = $dataFim->format('d/m/Y');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento de Reserva - DriveNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: sans-serif;
        }
        .animate-pulse-15s { animation-duration: 15s; }
        .animate-pulse-20s { animation-duration: 20s; }
        .animate-pulse-25s { animation-duration: 25s; }

        .subtle-border {
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Estilo para o cartão de crédito */
        .credit-card {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            height: 200px;
            border-radius: 16px;
            padding: 20px;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .card-chip {
            width: 50px;
            height: 40px;
            background: linear-gradient(135deg, #ffdd00, #fbb034);
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        /* Estilo para o PIX */
        .pix-container {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
        }
        
        .pix-qrcode {
            width: 200px;
            height: 200px;
            background-color: rgba(255, 255, 255, 0.2);
            margin: 0 auto;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Estilo para o boleto */
        .boleto-container {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
        }
        
        .boleto-code {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            font-family: monospace;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white p-4 md:p-8 overflow-x-hidden">

    <div class="fixed top-0 right-0 w-96 h-96 rounded-full bg-indigo-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-15s"></div>
    <div class="fixed bottom-0 left-0 w-80 h-80 rounded-full bg-purple-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-20s"></div>
    <div class="fixed top-1/3 left-1/4 w-64 h-64 rounded-full bg-slate-700 opacity-5 blur-3xl -z-10 animate-pulse animate-pulse-25s"></div>

    <header class="backdrop-blur-md bg-white/5 border subtle-border rounded-2xl mb-8 shadow-lg overflow-hidden">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="../index.php" class="text-xl font-bold text-white mr-8">DriveNow</a>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
                        <a href="../vboard.php" class="text-white/80 hover:text-white transition-colors">Dashboard</a>
                        <a href="../reserva/minhas_reservas.php" class="text-white/80 hover:text-white transition-colors">Reservas</a>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-white hidden md:inline"><?= htmlspecialchars($usuario['primeiro_nome']) ?></span>
                    <div class="relative h-8 w-8 transition-transform hover:scale-110 rounded-full flex items-center justify-center bg-indigo-500 text-white overflow-hidden">
                        <?php if (isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                        <?php else: ?>
                            <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($usuario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Pagamento de Reserva</h1>
            <a href="../reserva/minhas_reservas.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Voltar
            </a>
        </div>
        
        <?php if ($pagamento && $pagamento['status'] === 'aprovado'): ?>
            <!-- Pagamento já aprovado -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg mb-8">
                <div class="text-center py-8">
                    <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Pagamento Aprovado</h2>
                    <p class="text-white/70 mb-6">Este aluguel já foi pago e está confirmado.</p>
                    
                    <div class="flex justify-center gap-4">
                        <a href="../reserva/minhas_reservas.php" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-6 py-2 shadow-md hover:shadow-lg">
                            Minhas Reservas
                        </a>
                        <a href="../contrato/gerar_contrato.php?reserva=<?= $reservaId ?>" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-6 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg">
                            Ver Contrato
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($pagamento && in_array($pagamento['status'], ['pendente', 'processando'])): ?>
            <!-- Pagamento pendente -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg mb-8">
                <div class="text-center py-8">
                    <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Pagamento Pendente</h2>
                    <p class="text-white/70 mb-6">Seu pagamento está sendo processado. Por favor, aguarde a confirmação.</p>
                    
                    <div class="flex justify-center gap-4">
                        <a href="detalhe_pagamento.php?id=<?= $pagamento['id'] ?>" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-6 py-2 shadow-md hover:shadow-lg">
                            Ver Detalhes
                        </a>
                        <a href="../reserva/minhas_reservas.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-6 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg">
                            Minhas Reservas
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Novo pagamento -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Resumo da reserva -->
                <div class="lg:col-span-1 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold mb-4">Resumo da Reserva</h2>
                    
                    <div class="mb-4">
                        <h3 class="font-semibold text-white/90 mb-2">Veículo</h3>
                        <p class="text-white/70"><?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?> (<?= htmlspecialchars($reserva['veiculo_ano']) ?>)</p>
                    </div>
                    
                    <div class="mb-4">
                        <h3 class="font-semibold text-white/90 mb-2">Período</h3>
                        <p class="text-white/70"><?= $dataInicioFormatada ?> a <?= $dataFimFormatada ?></p>
                        <p class="text-white/70"><?= $dias ?> dias</p>
                    </div>
                    
                    <div class="mb-6">
                        <h3 class="font-semibold text-white/90 mb-2">Proprietário</h3>
                        <p class="text-white/70"><?= htmlspecialchars($reserva['nome_proprietario']) ?></p>
                    </div>
                    
                    <div class="border-t subtle-border pt-4 mb-4">
                        <h3 class="font-semibold text-white/90 mb-2">Detalhamento de Valores</h3>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-white/70">Diária (R$ <?= $diariaValor ?> x <?= $dias ?> dias)</span>
                            <span class="text-white">R$ <?= number_format($reserva['diaria_valor'] * $dias, 2, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-white/70">Taxa de Uso</span>
                            <span class="text-white">R$ <?= $taxaUso ?></span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-white/70">Taxa de Limpeza</span>
                            <span class="text-white">R$ <?= $taxaLimpeza ?></span>
                        </div>
                    </div>
                    
                    <div class="border-t subtle-border pt-4">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-white">Valor Total</span>
                            <span class="font-bold text-xl text-white">R$ <?= $valorTotal ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Opções de pagamento -->
                <div class="lg:col-span-2 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold mb-4">Forma de Pagamento</h2>
                    
                    <!-- Tabs de métodos de pagamento -->
                    <div class="border-b subtle-border mb-6">
                        <div class="flex">
                            <button type="button" class="payment-tab active pb-3 px-4 font-medium border-b-2 border-indigo-500" data-target="cartao">
                                Cartão de Crédito
                            </button>
                            <button type="button" class="payment-tab pb-3 px-4 font-medium border-b-2 border-transparent text-white/70 hover:text-white transition-colors" data-target="pix">
                                PIX
                            </button>
                            <button type="button" class="payment-tab pb-3 px-4 font-medium border-b-2 border-transparent text-white/70 hover:text-white transition-colors" data-target="boleto">
                                Boleto
                            </button>
                        </div>
                    </div>
                    
                    <!-- Conteúdo das tabs -->
                    <div class="payment-content" id="cartao-content">
                        <form action="" method="post" class="space-y-6">
                            <input type="hidden" name="metodo_pagamento" value="cartao">
                            
                            <!-- Visualização do cartão -->
                            <div class="credit-card mb-8">
                                <div class="card-chip"></div>
                                <div class="card-number text-xl font-mono tracking-wider mb-4 text-white">
                                    <span id="card-display">•••• •••• •••• ••••</span>
                                </div>
                                <div class="flex justify-between">
                                    <div>
                                        <div class="text-white/70 text-xs mb-1">Titular</div>
                                        <div id="card-holder-display" class="text-white font-mono">NOME DO TITULAR</div>
                                    </div>
                                    <div>
                                        <div class="text-white/70 text-xs mb-1">Validade</div>
                                        <div id="card-expiry-display" class="text-white font-mono">MM/AA</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Campos do formulário -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2 md:col-span-2">
                                    <label for="numero_cartao" class="block text-white/90 font-medium">Número do Cartão</label>
                                    <input type="text" id="numero_cartao" name="numero_cartao" 
                                           placeholder="1234 5678 9012 3456"
                                           class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                                </div>
                                
                                <div class="space-y-2 md:col-span-2">
                                    <label for="titular_cartao" class="block text-white/90 font-medium">Nome do Titular</label>
                                    <input type="text" id="titular_cartao" name="titular_cartao" 
                                           placeholder="Como está no cartão"
                                           class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="validade" class="block text-white/90 font-medium">Validade</label>
                                    <input type="text" id="validade" name="validade" 
                                           placeholder="MM/AA"
                                           class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="cvv" class="block text-white/90 font-medium">Código de Segurança (CVV)</label>
                                    <input type="text" id="cvv" name="cvv"
                                           placeholder="123"
                                           class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                                </div>
                                
                                <div class="space-y-2 md:col-span-2">
                                    <label for="parcelas" class="block text-white/90 font-medium">Parcelas</label>
                                    <select id="parcelas" name="parcelas" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                                        <option value="1">À vista - R$ <?= $valorTotal ?></option>
                                        <option value="2">2x - R$ <?= number_format($reserva['valor_total'] / 2, 2, ',', '.') ?>/mês</option>
                                        <option value="3">3x - R$ <?= number_format($reserva['valor_total'] / 3, 2, ',', '.') ?>/mês</option>
                                        <?php if ($reserva['valor_total'] >= 500): ?>
                                            <option value="6">6x - R$ <?= number_format($reserva['valor_total'] / 6, 2, ',', '.') ?>/mês</option>
                                        <?php endif; ?>
                                        <?php if ($reserva['valor_total'] >= 1000): ?>
                                            <option value="12">12x - R$ <?= number_format($reserva['valor_total'] / 12, 2, ',', '.') ?>/mês</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors border border-emerald-400/30 px-4 py-3 shadow-md hover:shadow-lg">
                                Pagar R$ <?= $valorTotal ?>
                            </button>
                            
                            <p class="text-white/60 text-sm text-center mt-4">
                                Seus dados estão protegidos com criptografia de ponta a ponta.
                            </p>
                        </form>
                    </div>
                    
                    <div class="payment-content hidden" id="pix-content">
                        <form action="" method="post" class="space-y-6">
                            <input type="hidden" name="metodo_pagamento" value="pix">
                            
                            <div class="pix-container p-8 text-center">
                                <h3 class="text-lg font-medium mb-4">Pague com PIX</h3>
                                <p class="text-white/70 mb-6">Escaneie o QR Code abaixo ou copie a chave PIX para pagar:</p>
                                
                                <div class="pix-qrcode mb-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 text-white/50">
                                        <rect width="6" height="6" x="4" y="4" rx="1"/>
                                        <rect width="6" height="6" x="14" y="4" rx="1"/>
                                        <rect width="6" height="6" x="4" y="14" rx="1"/>
                                        <path d="M14 14h6v6h-6z"/>
                                    </svg>
                                </div>
                                
                                <div class="mb-6">
                                    <p class="text-white/70 mb-2">Chave PIX:</p>
                                    <div class="flex items-center justify-center gap-2">
                                        <code class="bg-white/10 px-3 py-2 rounded-lg">drivenow@email.com</code>
                                        <button type="button" class="p-2 rounded-full hover:bg-white/10 transition-colors" title="Copiar chave">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                                <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                                                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <p class="text-white/70 mb-2">Valor:</p>
                                    <p class="text-xl font-bold">R$ <?= $valorTotal ?></p>
                                </div>
                                
                                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors border border-emerald-400/30 px-4 py-3 shadow-md hover:shadow-lg">
                                    Confirmar Pagamento PIX
                                </button>
                                
                                <p class="text-white/60 text-sm text-center mt-4">
                                    O pagamento será confirmado automaticamente após a transferência.
                                </p>
                            </div>
                        </form>
                    </div>
                    
                    <div class="payment-content hidden" id="boleto-content">
                        <form action="" method="post" class="space-y-6">
                            <input type="hidden" name="metodo_pagamento" value="boleto">
                            
                            <div class="boleto-container p-8 text-center">
                                <h3 class="text-lg font-medium mb-4">Pague com Boleto</h3>
                                <p class="text-white/70 mb-6">Gere um boleto para pagamento em qualquer banco ou lotérica:</p>
                                
                                <div class="boleto-code">
                                    <p class="text-xs mb-2">Linha Digitável:</p>
                                    <p>12345.67890 12345.678901 12345.678901 1 12345678901234</p>
                                </div>
                                
                                <div class="mb-6">
                                    <p class="text-white/70 mb-2">Valor:</p>
                                    <p class="text-xl font-bold">R$ <?= $valorTotal ?></p>
                                </div>
                                
                                <div class="mb-6">
                                    <p class="text-white/70 mb-2">Vencimento:</p>
                                    <p class="font-medium"><?= date('d/m/Y', strtotime('+3 days')) ?></p>
                                </div>
                                
                                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors border border-emerald-400/30 px-4 py-3 shadow-md hover:shadow-lg mb-4">
                                    Gerar Boleto
                                </button>
                                
                                <button type="button" class="w-full border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-3 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg">
                                    Baixar PDF do Boleto
                                </button>
                                
                                <p class="text-white/60 text-sm text-center mt-4">
                                    O boleto pode levar até 3 dias úteis para ser compensado após o pagamento.
                                </p>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Botão para demonstração - Simulação de Pagamento -->
                    <div class="mt-8 border-t subtle-border pt-6">
                        <h3 class="text-lg font-medium mb-4">Opção para Teste/Desenvolvimento</h3>
                        <form action="" method="post">
                            <input type="hidden" name="metodo_pagamento" value="simulacao">
                            <input type="hidden" name="simulacao_status" value="aprovado">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium rounded-xl transition-colors border border-green-500/30 px-4 py-3 shadow-md hover:shadow-lg">
                                Simular Pagamento Aprovado (Apenas para Testes)
                            </button>
                            <p class="text-xs text-white/50 mt-2 text-center">Esta opção existe apenas para fins de teste e desenvolvimento.</p>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="mt-12 mb-6 px-4 text-center text-white/50 text-sm">
        <p>&copy; <?= date('Y') ?> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <!-- Sistema de notificações -->
    <script src="../assets/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeNotifications();
            
            <?php if (isset($_SESSION['notification'])): ?>
                notify({
                    type: '<?= $_SESSION['notification']['type'] ?>',
                    message: '<?= $_SESSION['notification']['message'] ?>'
                });
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
            
            // Comportamento das tabs de pagamento
            const tabs = document.querySelectorAll('.payment-tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const target = tab.dataset.target;
                    
                    // Desativar todas as tabs e conteúdos
                    document.querySelectorAll('.payment-tab').forEach(t => {
                        t.classList.remove('active', 'border-indigo-500');
                        t.classList.add('border-transparent', 'text-white/70');
                    });
                    document.querySelectorAll('.payment-content').forEach(c => {
                        c.classList.add('hidden');
                    });
                    
                    // Ativar a tab selecionada e seu conteúdo
                    tab.classList.add('active', 'border-indigo-500');
                    tab.classList.remove('border-transparent', 'text-white/70');
                    document.getElementById(`${target}-content`).classList.remove('hidden');
                });
            });
            
            // Visualização cartão de crédito
            const cardNumberInput = document.getElementById('numero_cartao');
            const cardHolderInput = document.getElementById('titular_cartao');
            const cardExpiryInput = document.getElementById('validade');
            
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 16) value = value.substr(0, 16);
                    
                    // Formatação para exibição
                    let formattedValue = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) formattedValue += ' ';
                        formattedValue += value[i];
                    }
                    e.target.value = formattedValue;
                    
                    // Atualizar o display do cartão
                    let displayValue = value ? formattedValue : '•••• •••• •••• ••••';
                    document.getElementById('card-display').textContent = displayValue;
                });
            }
            
            if (cardHolderInput) {
                cardHolderInput.addEventListener('input', function(e) {
                    let value = e.target.value.toUpperCase();
                    e.target.value = value;
                    
                    // Atualizar o display do cartão
                    document.getElementById('card-holder-display').textContent = value || 'NOME DO TITULAR';
                });
            }
            
            if (cardExpiryInput) {
                cardExpiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 4) value = value.substr(0, 4);
                    
                    // Formatação para exibição
                    let formattedValue = '';
                    if (value.length > 0) {
                        formattedValue = value.substr(0, 2);
                        if (value.length > 2) {
                            formattedValue += '/' + value.substr(2);
                        }
                    }
                    e.target.value = formattedValue;
                    
                    // Atualizar o display do cartão
                    document.getElementById('card-expiry-display').textContent = formattedValue || 'MM/AA';
                });
            }
        });
    </script>
</body>
</html>
