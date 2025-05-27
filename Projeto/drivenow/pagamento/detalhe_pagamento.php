<?php
require_once '../includes/auth.php';

// Verificar autenticação
if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();
global $pdo;

// Verificar se o ID do pagamento foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Pagamento não especificado.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

$pagamentoId = (int)$_GET['id'];

// Verificar se o pagamento existe e obter detalhes
$stmt = $pdo->prepare("
    SELECT p.*, r.conta_usuario_id AS reserva_usuario_id,
           r.veiculo_id, r.reserva_data, r.devolucao_data, r.valor_total,
           v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano
    FROM pagamento p
    INNER JOIN reserva r ON p.reserva_id = r.id
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    WHERE p.id = ?
");
$stmt->execute([$pagamentoId]);
$pagamento = $stmt->fetch();

if (!$pagamento) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Pagamento não encontrado.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

// Verificar se o usuário tem permissão para visualizar este pagamento
if ($pagamento['reserva_usuario_id'] !== $usuario['id']) {
    // Verificar se o usuário é o proprietário do veículo
    $stmt = $pdo->prepare("
        SELECT d.conta_usuario_id
        FROM veiculo v
        INNER JOIN dono d ON v.dono_id = d.id
        WHERE v.id = ?
    ");
    $stmt->execute([$pagamento['veiculo_id']]);
    $proprietario = $stmt->fetch();
    
    if (!$proprietario || $proprietario['conta_usuario_id'] !== $usuario['id']) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Você não tem permissão para acessar este pagamento.'
        ];
        header('Location: ../reserva/minhas_reservas.php');
        exit;
    }
}

// Formatar detalhes conforme o método de pagamento
$detalhes = json_decode($pagamento['detalhes'], true) ?? [];
$metodoPagamentoTexto = '';
$detalhesFormatados = '';

switch ($pagamento['metodo_pagamento']) {
    case 'cartao':
        $metodoPagamentoTexto = 'Cartão de Crédito';
        $titular = $detalhes['titular'] ?? 'N/D';
        $ultimos_digitos = $detalhes['ultimos_digitos'] ?? '****';
        $bandeira = $detalhes['bandeira'] ?? 'N/D';
        $parcelas = $detalhes['parcelas'] ?? 1;
        
        $detalhesFormatados = "
            <div class='grid grid-cols-2 gap-2 text-sm'>
                <div class='text-white/60'>Titular:</div>
                <div>{$titular}</div>
                
                <div class='text-white/60'>Cartão:</div>
                <div>**** **** **** {$ultimos_digitos}</div>
                
                <div class='text-white/60'>Bandeira:</div>
                <div>{$bandeira}</div>
                
                <div class='text-white/60'>Parcelas:</div>
                <div>{$parcelas}x de R$ " . number_format($pagamento['valor'] / $parcelas, 2, ',', '.') . "</div>
            </div>
        ";
        break;
        
    case 'pix':
        $metodoPagamentoTexto = 'PIX';
        $chave_pix = $detalhes['chave_pix'] ?? 'N/D';
        $expiracao = isset($detalhes['expiracao']) ? date('d/m/Y H:i', strtotime($detalhes['expiracao'])) : 'N/D';
        
        $detalhesFormatados = "
            <div class='grid grid-cols-2 gap-2 text-sm'>
                <div class='text-white/60'>Chave PIX:</div>
                <div>{$chave_pix}</div>
                
                <div class='text-white/60'>Expiração:</div>
                <div>{$expiracao}</div>
            </div>
            
            <div class='mt-4 p-4 bg-white/10 rounded-xl'>
                <p class='text-center mb-4'>QR Code PIX</p>
                <div class='w-40 h-40 mx-auto bg-white/20 flex items-center justify-center rounded-lg'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='h-12 w-12 text-white/50'>
                        <rect width='6' height='6' x='4' y='4' rx='1'/>
                        <rect width='6' height='6' x='14' y='4' rx='1'/>
                        <rect width='6' height='6' x='4' y='14' rx='1'/>
                        <path d='M14 14h6v6h-6z'/>
                    </svg>
                </div>
            </div>
        ";
        break;
        
    case 'boleto':
        $metodoPagamentoTexto = 'Boleto Bancário';
        $codigo_barras = $detalhes['codigo_barras'] ?? 'N/D';
        $data_vencimento = isset($detalhes['data_vencimento']) ? date('d/m/Y', strtotime($detalhes['data_vencimento'])) : 'N/D';
        $url_boleto = $detalhes['url_boleto'] ?? '#';
        
        $detalhesFormatados = "
            <div class='grid grid-cols-2 gap-2 text-sm'>
                <div class='text-white/60'>Vencimento:</div>
                <div>{$data_vencimento}</div>
            </div>
            
            <div class='mt-4 p-4 bg-white/10 rounded-xl'>
                <p class='text-sm text-white/60 mb-2'>Código de barras:</p>
                <p class='font-mono text-sm break-all'>{$codigo_barras}</p>
            </div>
            
            <div class='mt-4'>
                <a href='{$url_boleto}' target='_blank' class='w-full block bg-indigo-500 hover:bg-indigo-600 text-white text-center font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg'>
                    Visualizar Boleto
                </a>
            </div>
        ";
        break;
        
    default:
        $metodoPagamentoTexto = 'Outro';
        $detalhesFormatados = "<p>Não disponível</p>";
}

// Formatar datas e valores
$dataFormatada = $pagamento['data_pagamento'] ? date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) : 'Aguardando';
$valorFormatado = number_format($pagamento['valor'], 2, ',', '.');

// Formatar datas da reserva
$dataInicioFormatada = date('d/m/Y', strtotime($pagamento['reserva_data']));
$dataFimFormatada = date('d/m/Y', strtotime($pagamento['devolucao_data']));

// Definir classes para status
$statusClass = '';
$statusIcon = '';
switch ($pagamento['status']) {
    case 'aprovado':
        $statusClass = 'bg-emerald-500 text-white';
        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
        break;
    case 'pendente':
        $statusClass = 'bg-yellow-500 text-white';
        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
        break;
    case 'recusado':
        $statusClass = 'bg-red-500 text-white';
        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
        break;
    case 'estornado':
        $statusClass = 'bg-gray-500 text-white';
        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M3 7h18"/><path d="M3 11h18"/><path d="M3 15h18"/><path d="M3 19h18"/></svg>';
        break;
    default:
        $statusClass = 'bg-indigo-500 text-white';
        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
}

// Buscar histórico do pagamento
$stmt = $pdo->prepare("
    SELECT hp.*, cu.primeiro_nome, cu.segundo_nome
    FROM historico_pagamento hp
    LEFT JOIN conta_usuario cu ON hp.usuario_id = cu.id
    WHERE hp.pagamento_id = ?
    ORDER BY hp.data_alteracao DESC
");
$stmt->execute([$pagamentoId]);
$historico = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pagamento - DriveNow</title>
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
        
        .timeline-line {
            position: absolute;
            left: 25px;
            top: 40px;
            bottom: 0;
            width: 2px;
            background-color: rgba(255, 255, 255, 0.1);
            z-index: 0;
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
        <div class="flex flex-wrap justify-between items-center mb-8 gap-4">
            <h1 class="text-3xl font-bold">Detalhes do Pagamento</h1>
            <div class="flex flex-wrap gap-3">
                <?php if ($pagamento['status'] === 'pendente'): ?>
                    <a href="confirmar_pagamento.php?id=<?= $pagamentoId ?>" class="bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-colors border border-emerald-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        Confirmar Pagamento
                    </a>
                <?php endif; ?>
                <a href="../reserva/minhas_reservas.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Voltar às Reservas
                </a>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Detalhes principais do pagamento -->
            <div class="lg:col-span-2 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold">Pagamento #<?= $pagamentoId ?></h2>
                        <p class="text-white/70 text-sm">Transação: <?= htmlspecialchars($pagamento['codigo_transacao']) ?></p>
                    </div>
                    
                    <div class="px-3 py-1 rounded-full <?= $statusClass ?> text-sm font-medium flex items-center gap-1">
                        <?= $statusIcon ?>
                        <span><?= ucfirst($pagamento['status']) ?></span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="font-semibold text-white/90 mb-2">Informações do Pagamento</h3>
                        <div class="grid gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-white/60">Método:</span>
                                <span><?= $metodoPagamentoTexto ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/60">Valor:</span>
                                <span class="font-medium">R$ <?= $valorFormatado ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/60">Data:</span>
                                <span><?= $dataFormatada ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/60">Criação:</span>
                                <span><?= date('d/m/Y H:i', strtotime($pagamento['data_criacao'])) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-white/90 mb-2">Informações da Reserva</h3>
                        <div class="grid gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-white/60">Veículo:</span>
                                <span><?= htmlspecialchars($pagamento['veiculo_marca']) ?> <?= htmlspecialchars($pagamento['veiculo_modelo']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/60">Período:</span>
                                <span><?= $dataInicioFormatada ?> - <?= $dataFimFormatada ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/60">Código:</span>
                                <span>RES-<?= $pagamento['reserva_id'] ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/60">Total:</span>
                                <span class="font-medium">R$ <?= number_format($pagamento['valor_total'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="font-semibold text-white/90 mb-3">Detalhes do Método de Pagamento</h3>
                    <?= $detalhesFormatados ?>
                </div>
                
                <?php if ($pagamento['comprovante_url']): ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-white/90 mb-3">Comprovante de Pagamento</h3>
                    <a href="<?= htmlspecialchars($pagamento['comprovante_url']) ?>" target="_blank" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg inline-flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        Visualizar Comprovante
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Histórico do pagamento -->
            <div class="lg:col-span-1 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-6">Histórico</h2>
                
                <?php if (empty($historico)): ?>
                    <div class="text-center py-8 text-white/50">
                        <p>Nenhum evento registrado.</p>
                    </div>
                <?php else: ?>
                    <div class="relative">
                        <div class="timeline-line"></div>
                        
                        <?php foreach ($historico as $index => $evento): ?>
                            <?php
                                $statusIcon = '';
                                $statusBg = '';
                                
                                switch ($evento['novo_status']) {
                                    case 'aprovado':
                                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
                                        $statusBg = 'bg-emerald-500';
                                        break;
                                    case 'pendente':
                                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
                                        $statusBg = 'bg-yellow-500';
                                        break;
                                    case 'recusado':
                                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
                                        $statusBg = 'bg-red-500';
                                        break;
                                    case 'estornado':
                                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M3 7h18"/><path d="M3 11h18"/><path d="M3 15h18"/><path d="M3 19h18"/></svg>';
                                        $statusBg = 'bg-gray-500';
                                        break;
                                    default:
                                        $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="10"/></svg>';
                                        $statusBg = 'bg-indigo-500';
                                }
                                
                                $dataEvento = date('d/m/Y H:i', strtotime($evento['data_alteracao']));
                                $nomeUsuario = $evento['primeiro_nome'] ? "{$evento['primeiro_nome']} {$evento['segundo_nome']}" : 'Sistema';
                            ?>
                            <div class="relative pl-12 pb-8">
                                <div class="absolute left-0 top-0 w-10 h-10 rounded-full <?= $statusBg ?> flex items-center justify-center z-10">
                                    <?= $statusIcon ?>
                                </div>
                                
                                <div class="backdrop-blur-sm bg-white/5 border subtle-border rounded-2xl p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h3 class="font-medium">
                                            <?php if ($evento['status_anterior']): ?>
                                                Alteração de <span class="text-white/60"><?= ucfirst($evento['status_anterior']) ?></span> para <span class="font-semibold"><?= ucfirst($evento['novo_status']) ?></span>
                                            <?php else: ?>
                                                Status: <span class="font-semibold"><?= ucfirst($evento['novo_status']) ?></span>
                                            <?php endif; ?>
                                        </h3>
                                        <span class="text-white/60 text-xs"><?= $dataEvento ?></span>
                                    </div>
                                    
                                    <?php if ($evento['observacao']): ?>
                                        <p class="text-white/80 text-sm"><?= htmlspecialchars($evento['observacao']) ?></p>
                                    <?php endif; ?>
                                    
                                    <p class="text-white/60 text-xs mt-2">Por: <?= htmlspecialchars($nomeUsuario) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($pagamento['status'] === 'pendente'): ?>
                    <div class="mt-6">
                        <a href="enviar_comprovante.php?id=<?= $pagamentoId ?>" class="w-full block bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg text-center">
                            Enviar Comprovante
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
        });
    </script>
</body>
</html>
