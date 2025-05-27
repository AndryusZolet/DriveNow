<?php
// Ativar exibição de erros para debugar
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';

verificarAutenticacao();

$usuario = getUsuario();

// Verificar se o ID da reserva foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: reservas_recebidas.php?erro=' . urlencode('ID de reserva não fornecido'));
    exit;
}

$reservaId = (int)$_GET['id'];

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

// Buscar detalhes da reserva
$stmt = $pdo->prepare("
    SELECT r.*, 
           v.veiculo_marca, v.veiculo_modelo, v.veiculo_placa, v.veiculo_ano, v.veiculo_km, 
           v.veiculo_cambio, v.veiculo_combustivel, v.veiculo_portas, v.veiculo_acentos, 
           v.veiculo_tracao, v.preco_diaria, v.descricao,
           CONCAT(u_loc.primeiro_nome, ' ', u_loc.segundo_nome) AS nome_locatario,
           u_loc.telefone AS telefone_locatario, u_loc.e_mail AS email_locatario,
           CONCAT(u_prop.primeiro_nome, ' ', u_prop.segundo_nome) AS nome_proprietario,
           u_prop.telefone AS telefone_proprietario, u_prop.e_mail AS email_proprietario,
           l.nome_local, l.endereco, l.complemento, l.cep,
           c.cidade_nome, e.estado_nome, e.sigla
    FROM reserva r
    JOIN veiculo v ON r.veiculo_id = v.id
    JOIN conta_usuario u_loc ON r.conta_usuario_id = u_loc.id
    JOIN dono d ON v.dono_id = d.id
    JOIN conta_usuario u_prop ON d.conta_usuario_id = u_prop.id
    JOIN local l ON v.local_id = l.id
    JOIN cidade c ON l.cidade_id = c.id
    JOIN estado e ON c.estado_id = e.id
    WHERE r.id = ?
");
$stmt->execute([$reservaId]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header('Location: ' . ($dono ? 'reservas_recebidas.php' : '../vboard.php') . '?erro=' . urlencode('Reserva não encontrada'));
    exit;
}

// Verificar permissões
if (!$dono) {
    // Se não é dono, verificar se é o locatário
    if ($reserva['conta_usuario_id'] != $usuario['id']) {
        header('Location: ../vboard.php?erro=' . urlencode('Acesso não autorizado'));
        exit;
    }
} else {
    // Se é dono, verificar se a reserva é de um veículo desse dono
    $stmt = $pdo->prepare("
        SELECT r.id FROM reserva r 
        JOIN veiculo v ON r.veiculo_id = v.id 
        WHERE r.id = ? AND v.dono_id = ?
    ");
    $stmt->execute([$reservaId, $dono['id']]);
    $reservaDoDono = $stmt->fetch();
    
    if (!$reservaDoDono) {
        header('Location: reservas_recebidas.php?erro=' . urlencode('Acesso não autorizado'));
        exit;
    }
}

// Definir status da reserva
$now = time();
$inicio = strtotime($reserva['reserva_data']);
$fim = strtotime($reserva['devolucao_data']);

if (!empty($reserva['status'])) {
    $status = ucfirst($reserva['status']);
    if ($reserva['status'] === 'confirmada') {
        if ($now >= $inicio && $now <= $fim) {
            $status = 'Em Andamento';
            $statusClass = 'bg-yellow-500/20 text-yellow-300 border border-yellow-400/30';
        } else if ($now > $fim) {
            $status = 'Finalizada';
            $statusClass = 'bg-gray-500/20 text-gray-300 border border-gray-400/30';
        } else {
            $status = 'Confirmada';
            $statusClass = 'bg-green-500/20 text-green-300 border border-green-400/30';
        }
    } elseif ($reserva['status'] === 'rejeitada') {
        $statusClass = 'bg-red-500/20 text-red-300 border border-red-400/30';
    } elseif ($reserva['status'] === 'finalizada') {
        $statusClass = 'bg-gray-500/20 text-gray-300 border border-gray-400/30';
    } else {
        $statusClass = 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
    }
} else {
    $status = 'Pendente';
    $statusClass = 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
}

// Calcular duração em dias
$duracao = ceil(($fim - $inicio) / (60 * 60 * 24));

// Calcular valor total novamente (para confirmação)
$valorDiarias = $reserva['preco_diaria'] * $duracao;
$valorTotal = $valorDiarias + $reserva['taxas_de_uso'] + $reserva['taxas_de_limpeza'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Reserva - DriveNow</title>
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
                    <h1 class="text-xl font-bold text-white mr-8">DriveNow</h1>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
                        <a href="../vboard.php" class="text-white/80 hover:text-white transition-colors">Dashboard</a>
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

    <main class="container mx-auto">
        <div class="flex justify-between items-center mb-6 px-4">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-white">
                    Detalhes da Reserva #<?= $reserva['id'] ?>
                </h2>
                <div class="flex items-center mt-2">
                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                        <?= $status ?>
                    </span>
                    <span class="text-white/70 ml-4">
                        Criada em <?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?>
                    </span>
                </div>
            </div>
            <a href="<?= $dono ? 'reservas_recebidas.php' : '../vboard.php' ?>" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 font-medium shadow-md hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <span>Voltar</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 px-4">
            <!-- Informações da Reserva -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2 text-indigo-400">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                        <line x1="16" x2="16" y1="2" y2="6"/>
                        <line x1="8" x2="8" y1="2" y2="6"/>
                        <line x1="3" x2="21" y1="10" y2="10"/>
                    </svg>
                    Período da Reserva
                </h3>
                <div class="space-y-3 text-white">
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Data de Retirada:</span>
                        <span class="font-medium"><?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Data de Devolução:</span>
                        <span class="font-medium"><?= date('d/m/Y', strtotime($reserva['devolucao_data'])) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Duração:</span>
                        <span class="font-medium"><?= $duracao ?> dias</span>
                    </div>
                    <div class="flex flex-col pt-3 border-t border-white/10 mt-3">
                        <span class="text-white/60 text-sm">Valor da Diária:</span>
                        <span class="font-medium">R$ <?= number_format($reserva['preco_diaria'], 2, ',', '.') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Taxa de Uso:</span>
                        <span class="font-medium">R$ <?= number_format($reserva['taxas_de_uso'], 2, ',', '.') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Taxa de Limpeza:</span>
                        <span class="font-medium">R$ <?= number_format($reserva['taxas_de_limpeza'], 2, ',', '.') ?></span>
                    </div>
                    <div class="flex flex-col pt-3 border-t border-white/10 mt-3">
                        <span class="text-white/60 text-sm">Valor Total:</span>
                        <span class="font-medium text-xl text-green-400">R$ <?= number_format($reserva['valor_total'], 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Informações do Veículo -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2 text-indigo-400">
                        <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                        <circle cx="7" cy="17" r="2"/>
                        <path d="M9 17h6"/>
                        <circle cx="17" cy="17" r="2"/>
                    </svg>
                    Informações do Veículo
                </h3>
                <div class="space-y-3 text-white">
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Veículo:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?> (<?= $reserva['veiculo_ano'] ?>)</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Placa:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['veiculo_placa']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Quilometragem:</span>
                        <span class="font-medium"><?= number_format($reserva['veiculo_km'], 0, ',', '.') ?> km</span>
                    </div>
                    <div class="flex flex-col pt-3 border-t border-white/10 mt-3">
                        <span class="text-white/60 text-sm">Câmbio:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['veiculo_cambio']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Combustível:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['veiculo_combustivel']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Portas / Assentos:</span>
                        <span class="font-medium"><?= $reserva['veiculo_portas'] ?> portas / <?= $reserva['veiculo_acentos'] ?> assentos</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Tração:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['veiculo_tracao']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Informações de Contato -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2 text-indigo-400">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Informações de Contato
                </h3>
                
                <?php if ($dono): ?>
                <!-- Proprietário vê informações do locatário -->
                <div class="space-y-3 text-white">
                    <h4 class="font-medium text-indigo-300">Locatário</h4>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Nome:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['nome_locatario']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Telefone:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['telefone_locatario']) ?></span>
                    </div>
                    <div class="flex flex-col pb-3 border-b border-white/10 mb-3">
                        <span class="text-white/60 text-sm">E-mail:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['email_locatario']) ?></span>
                    </div>
                </div>
                <?php else: ?>
                <!-- Locatário vê informações do proprietário -->
                <div class="space-y-3 text-white">
                    <h4 class="font-medium text-indigo-300">Proprietário</h4>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Nome:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['nome_proprietario']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Telefone:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['telefone_proprietario']) ?></span>
                    </div>
                    <div class="flex flex-col pb-3 border-b border-white/10 mb-3">
                        <span class="text-white/60 text-sm">E-mail:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['email_proprietario']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="space-y-3 text-white">
                    <h4 class="font-medium text-indigo-300">Local de Retirada/Devolução</h4>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Local:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['nome_local']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Endereço:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['endereco']) ?></span>
                        <?php if (!empty($reserva['complemento'])): ?>
                            <span class="text-sm"><?= htmlspecialchars($reserva['complemento']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">Cidade/Estado:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['cidade_nome']) ?> - <?= htmlspecialchars($reserva['sigla']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-white/60 text-sm">CEP:</span>
                        <span class="font-medium"><?= htmlspecialchars($reserva['cep']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Observações -->
        <?php if (!empty($reserva['observacoes'])): ?>
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4 mb-6">
            <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2 text-indigo-400">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Observações
            </h3>
            <div class="text-white bg-white/5 p-4 rounded-xl border subtle-border">
                <?= nl2br(htmlspecialchars($reserva['observacoes'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Ações disponíveis para o proprietário -->
        <?php if ($dono): ?>
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4 mb-6">
            <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2 text-indigo-400">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Ações
            </h3>
            <div class="flex flex-wrap gap-3">
                <?php 
                // Verificar se a data de início da reserva já passou
                $dataReservaPassou = strtotime($reserva['reserva_data']) < time();
                
                if ((empty($reserva['status']) || $reserva['status'] === 'pendente') && !$dataReservaPassou): ?>
                    <form method="post" action="reservas_recebidas.php">
                        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                        <input type="hidden" name="acao" value="confirmar">
                        <button type="submit" class="bg-green-500/20 hover:bg-green-500/30 text-green-300 border border-green-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                                onclick="return confirm('Confirmar esta reserva?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Confirmar Reserva
                        </button>
                    </form>
                    <form method="post" action="reservas_recebidas.php">
                        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                        <input type="hidden" name="acao" value="rejeitar">
                        <button type="submit" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                                onclick="return confirm('Rejeitar esta reserva?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Rejeitar Reserva
                        </button>
                    </form>
                <?php elseif ($reserva['status'] === 'confirmada' && $now >= $inicio && $now <= $fim): ?>
                    <form method="post" action="reservas_recebidas.php">
                        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                        <input type="hidden" name="acao" value="finalizar">
                        <button type="submit" class="bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                                onclick="return confirm('Finalizar esta reserva antecipadamente?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                <line x1="4" y1="22" x2="4" y2="15"></line>
                            </svg>
                            Finalizar Reserva
                        </button>
                    </form>
                <?php endif; ?>
                
                <a href="../mensagens/mensagens_conversa.php?reserva=<?= $reserva['id'] ?>" class="bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 border border-blue-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Mensagens
                </a>
                
                <?php if ($reserva['status'] === 'finalizada' || ($reserva['status'] === 'confirmada' && $now > $fim)): ?>
                <a href="../avaliacao/avaliar_locatario.php?reserva_id=<?= $reserva['id'] ?>" class="bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 border border-yellow-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    Avaliar Locatário
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Ações disponíveis para o locatário -->
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4 mb-6">
            <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2 text-indigo-400">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Ações
            </h3>
            <div class="flex flex-wrap gap-3">
                <a href="../mensagens/mensagens_conversa.php?reserva=<?= $reserva['id'] ?>" class="bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 border border-blue-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Mensagens
                </a>
                
                <?php if (empty($reserva['status']) || $reserva['status'] === 'pendente'): ?>
                <a href="cancelar_reserva.php?id=<?= $reserva['id'] ?>" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors" 
                   onclick="return confirm('Tem certeza que deseja cancelar esta reserva?')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    Cancelar Reserva
                </a>
                <?php endif; ?>
                
                <?php if ($reserva['status'] === 'confirmada' && $now <= $inicio): ?>
                <a href="../pagamento/realizar_pagamento.php?reserva_id=<?= $reserva['id'] ?>" class="bg-green-500/20 hover:bg-green-500/30 text-green-300 border border-green-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    Realizar Pagamento
                </a>
                <?php endif; ?>
                
                <?php if ($reserva['status'] === 'finalizada' || ($reserva['status'] === 'confirmada' && $now > $fim)): ?>
                <a href="../avaliacao/avaliar_veiculo.php?reserva_id=<?= $reserva['id'] ?>" class="bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 border border-yellow-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    Avaliar Veículo
                </a>
                
                <a href="../avaliacao/avaliar_proprietario.php?reserva_id=<?= $reserva['id'] ?>" class="bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-400/30 rounded-lg px-4 py-2 text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    Avaliar Proprietário
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </main>

    <footer class="container mx-auto mt-16 px-4 pb-8 text-center text-white/60 text-sm">
        <p>© <script>document.write(new Date().getFullYear())</script> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <script src="../assets/notifications.js"></script>
</body>
</html>