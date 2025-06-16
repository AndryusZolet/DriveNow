<?php
require_once '../includes/auth.php';

verificarAutenticacao();

$usuario = getUsuario();

// Executar atualização automática de status de reservas
$atualizarAutomaticamente = true;
if ($atualizarAutomaticamente) {
    // Incluir apenas, não é necessário o resultado
    include_once '../api/atualizar_status_reservas.php';
}

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    header('Location: ../vboard.php?erro=' . urlencode('Acesso restrito a proprietários'));
    exit;
}

// Processar confirmação de reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_id']) && isset($_POST['acao'])) {
    $reservaId = $_POST['reserva_id'];
    $acao = $_POST['acao'];
    
    // Verificar se a reserva pertence a um veículo do dono
    $stmt = $pdo->prepare("SELECT r.id FROM reserva r JOIN veiculo v ON r.veiculo_id = v.id WHERE r.id = ? AND v.dono_id = ?");
    $stmt->execute([$reservaId, $dono['id']]);
    $reservaValida = $stmt->fetch();
    
    if ($reservaValida) {
        if ($acao === 'confirmar') {
            $status = 'confirmada';
        } elseif ($acao === 'rejeitar') {
            $status = 'rejeitada';
        } elseif ($acao === 'finalizar') {
            $status = 'finalizada';
        }
        
        $stmt = $pdo->prepare("UPDATE reserva SET status = ? WHERE id = ?");
        $stmt->execute([$status, $reservaId]);
        
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => "Reserva {$status} com sucesso!"
        ];
        
        header('Location: reservas_recebidas.php?aba=' . (isset($_GET['aba']) ? $_GET['aba'] : 'pendentes'));
        exit;
    }
}

// Aba ativa
$aba = isset($_GET['aba']) ? $_GET['aba'] : 'pendentes';

// Buscar reservas dos veículos do dono de acordo com a aba selecionada
$query = "SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_placa,
          CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_locatario,
          u.telefone AS telefone_locatario
          FROM reserva r
          JOIN veiculo v ON r.veiculo_id = v.id
          JOIN conta_usuario u ON r.conta_usuario_id = u.id
          WHERE v.dono_id = ?";

switch ($aba) {
    case 'pendentes':
        // Apenas mostrar reservas pendentes cujas datas ainda não passaram
        $query .= " AND (r.status = 'pendente' OR r.status = 'pago') AND r.reserva_data >= CURRENT_DATE()";
        break;
    case 'confirmadas':
        $query .= " AND r.status = 'confirmada' AND r.devolucao_data >= CURRENT_DATE()";
        break;
    case 'andamento':
        $query .= " AND r.status = 'confirmada' AND r.reserva_data <= CURRENT_DATE() AND r.devolucao_data >= CURRENT_DATE()";
        break;
    case 'finalizadas':
        $query .= " AND (r.status = 'finalizada' OR (r.status = 'confirmada' AND r.devolucao_data < CURRENT_DATE()) OR ((r.status IS NULL OR r.status = 'pendente') AND r.reserva_data <= CURRENT_DATE()))";
        break;
    case 'rejeitadas':
        $query .= " AND r.status = 'rejeitada'";
        break;
    case 'todas':
    default:
        // Não filtra por status
        break;
}

$query .= " ORDER BY r.reserva_data DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$dono['id']]);
$reservas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas Recebidas - DriveNow</title>
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

        option {
            background-color: #1e293b !important;
            color: white !important;
        }
        
        .tab-active {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(139, 92, 246, 0.5);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            border-radius: 1rem;
            max-width: 90%;
            width: 450px;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white p-4 md:p-8 overflow-x-hidden">

    <!-- Modais de Confirmação -->
    <div id="confirmModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Confirmar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja confirmar esta reserva?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelConfirm" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Cancelar</button>
                <form id="confirmForm" method="post">
                    <input type="hidden" name="reserva_id" id="confirmReservaId">
                    <input type="hidden" name="acao" value="confirmar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors">Confirmar</button>
                </form>
            </div>
        </div>
    </div>

    <div id="rejectModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Rejeitar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja rejeitar esta reserva?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelReject" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Cancelar</button>
                <form id="rejectForm" method="post">
                    <input type="hidden" name="reserva_id" id="rejectReservaId">
                    <input type="hidden" name="acao" value="rejeitar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors">Rejeitar</button>
                </form>
            </div>
        </div>
    </div>

    <div id="finishModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Finalizar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja finalizar esta reserva antecipadamente?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelFinish" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Cancelar</button>
                <form id="finishForm" method="post">
                    <input type="hidden" name="reserva_id" id="finishReservaId">
                    <input type="hidden" name="acao" value="finalizar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white transition-colors">Finalizar</button>
                </form>
            </div>
        </div>
    </div>

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
                    Reservas Recebidas
                </h2>
                <p class="text-white/70 mt-2">Gerencie as reservas dos seus veículos</p>
            </div>
            <a href="../vboard.php" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 font-medium shadow-md hover:shadow-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <span>Voltar</span>
            </a>
        </div>

        <!-- Abas de navegação -->
        <div class="overflow-x-auto mb-6 px-4">
            <div class="flex space-x-2 border-b border-white/10 pb-1">
                <a href="?aba=pendentes" class="px-4 py-2 rounded-t-lg text-center whitespace-nowrap transition-colors <?= $aba === 'pendentes' ? 'tab-active' : 'text-white/70 hover:bg-white/5' ?>">
                    Pendentes
                </a>
                <a href="?aba=confirmadas" class="px-4 py-2 rounded-t-lg text-center whitespace-nowrap transition-colors <?= $aba === 'confirmadas' ? 'tab-active' : 'text-white/70 hover:bg-white/5' ?>">
                    Confirmadas
                </a>
                <a href="?aba=andamento" class="px-4 py-2 rounded-t-lg text-center whitespace-nowrap transition-colors <?= $aba === 'andamento' ? 'tab-active' : 'text-white/70 hover:bg-white/5' ?>">
                    Em Andamento
                </a>
                <a href="?aba=finalizadas" class="px-4 py-2 rounded-t-lg text-center whitespace-nowrap transition-colors <?= $aba === 'finalizadas' ? 'tab-active' : 'text-white/70 hover:bg-white/5' ?>">
                    Finalizadas
                </a>
                <a href="?aba=rejeitadas" class="px-4 py-2 rounded-t-lg text-center whitespace-nowrap transition-colors <?= $aba === 'rejeitadas' ? 'tab-active' : 'text-white/70 hover:bg-white/5' ?>">
                    Rejeitadas
                </a>
                <a href="?aba=todas" class="px-4 py-2 rounded-t-lg text-center whitespace-nowrap transition-colors <?= $aba === 'todas' ? 'tab-active' : 'text-white/70 hover:bg-white/5' ?>">
                    Todas
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['notification'])): ?>
            <div class="mx-4 mb-6 <?= $_SESSION['notification']['type'] === 'success' ? 'bg-green-500/20 border border-green-400/30' : 'bg-red-500/20 border border-red-400/30' ?> text-white px-4 py-3 rounded-xl">
                <?= htmlspecialchars($_SESSION['notification']['message']) ?>
            </div>
            <?php unset($_SESSION['notification']); ?>
        <?php endif; ?>
        
        <?php if (empty($reservas)): ?>
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-8 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4">
                <div class="text-center py-8">
                    <div class="p-6 rounded-full bg-indigo-500/20 text-white border border-indigo-400/30 inline-flex mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-10 w-10">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Nenhuma reserva encontrada</h3>
                    <p class="text-white/70 mb-6">Não há reservas na categoria "<?= ucfirst($aba) ?>" no momento.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4 overflow-x-auto">
                <table class="w-full min-w-full">
                    <thead class="border-b border-white/10 text-left">
                        <tr>
                            <th class="px-4 py-3 text-white/70 font-medium">Veículo</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Locatário</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Contato</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Período</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Valor Total</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Status</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($reservas as $reserva): ?>
                            <?php
                            $now = time();
                            $inicio = strtotime($reserva['reserva_data']);
                            $fim = strtotime($reserva['devolucao_data']);
                            
                            // Definir status e classe com base no status do banco ou datas
                            if (!empty($reserva['status'])) {
                                // Adicionar a identificação do status 'pago'
                                if ($reserva['status'] === 'pago') {
                                    $status = 'Pago';
                                    $statusClass = 'bg-purple-500/20 text-purple-300 border border-purple-400/30';
                                } else if ($reserva['status'] === 'confirmada') {
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
                                    $status = 'Rejeitada';
                                    $statusClass = 'bg-red-500/20 text-red-300 border border-red-400/30';
                                } elseif ($reserva['status'] === 'finalizada') {
                                    $status = 'Finalizada';
                                    $statusClass = 'bg-gray-500/20 text-gray-300 border border-gray-400/30';
                                } else {
                                    $status = 'Pendente';
                                    $statusClass = 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
                                }
                            } else {
                                $status = 'Pendente';
                                $statusClass = 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
                            }
                            ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-4">
                                    <div class="flex flex-gap-2">
                                        <span class="font-medium text-white">
                                            <?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?>
                                        </span>
                                        <span class="text-sm text-white/60">
                                            <?= htmlspecialchars($reserva['veiculo_placa']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-white">
                                    <?= htmlspecialchars($reserva['nome_locatario']) ?>
                                </td>
                                <td class="px-4 py-4 text-white">
                                    <?= htmlspecialchars($reserva['telefone_locatario']) ?>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-white">
                                            <?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?>
                                        </span>
                                        <span class="text-white">
                                            a <?= date('d/m/Y', strtotime($reserva['devolucao_data'])) ?>
                                        </span>
                                        <span class="text-xs text-white/60">
                                            <?= date('H:i', strtotime($reserva['reserva_data'])) ?> às <?= date('H:i', strtotime($reserva['devolucao_data'])) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-medium text-white">
                                    R$ <?= number_format($reserva['valor_total'], 2, ',', '.') ?>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4">                                    <div class="flex gap-2">
                                        <?php 
                                        // Verificar se a data de início da reserva já passou
                                        $dataReservaPassou = strtotime($reserva['reserva_data']) < time();
                                        
                                        if ((empty($reserva['status']) || $reserva['status'] === 'pendente') && !$dataReservaPassou): ?>
                                            <button data-id="<?= $reserva['id'] ?>" class="bg-green-500/20 hover:bg-green-500/30 text-green-300 border border-green-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors confirm-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </button>
                                            <button data-id="<?= $reserva['id'] ?>" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors reject-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                            </form>
                                            <form method="post">
                                                <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                                <input type="hidden" name="acao" value="rejeitar">
                                                <button type="submit" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors"
                                                        onclick="return confirm('Rejeitar esta reserva?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                                    </svg>
                                                </button>
                                            </form>
                                        <?php elseif ($reserva['status'] === 'confirmada' && $now >= $inicio && $now <= $fim): ?>
                                            <button data-id="<?= $reserva['id'] ?>" class="bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors finish-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1 inline-block">
                                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                                    <line x1="4" y1="22" x2="4" y2="15"></line>
                                                </svg>
                                                Finalizar
                                            </button>
                                        <?php endif; ?>
                                        <a href="detalhes_reserva.php?id=<?= $reserva['id'] ?>" class="bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>

                                        <?php if ($reserva['status'] === 'pago'): ?>
                                            <button data-id="<?= $reserva['id'] ?>" class="bg-green-500/20 hover:bg-green-500/30 text-green-300 border border-green-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors confirm-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </button>
                                            <button data-id="<?= $reserva['id'] ?>" class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors reject-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <footer class="container mx-auto mt-16 px-4 pb-8 text-center text-white/60 text-sm">
        <p>© <script>document.write(new Date().getFullYear())</script> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <script src="../assets/notifications.js"></script>

    <script>
        //Modal de confirmação
        // Funções para manipulação dos modais
        const confirmModal = document.getElementById('confirmModal');
        const rejectModal = document.getElementById('rejectModal');
        const finishModal = document.getElementById('finishModal');
        const confirmForm = document.getElementById('confirmForm');
        const rejectForm = document.getElementById('rejectForm');
        const finishForm = document.getElementById('finishForm');
        
        // Adicionar eventos aos botões de confirmação
        document.querySelectorAll('.confirm-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('confirmReservaId').value = this.getAttribute('data-id');
                confirmModal.style.display = 'flex';
            });
        });
        
        // Adicionar eventos aos botões de rejeição
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('rejectReservaId').value = this.getAttribute('data-id');
                rejectModal.style.display = 'flex';
            });
        });
        
        // Adicionar eventos aos botões de finalização
        document.querySelectorAll('.finish-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('finishReservaId').value = this.getAttribute('data-id');
                finishModal.style.display = 'flex';
            });
        });
        
        // Botões para fechar os modais
        document.getElementById('cancelConfirm').addEventListener('click', () => {
            confirmModal.style.display = 'none';
        });
        
        document.getElementById('cancelReject').addEventListener('click', () => {
            rejectModal.style.display = 'none';
        });
        
        document.getElementById('cancelFinish').addEventListener('click', () => {
            finishModal.style.display = 'none';
        });
        
        // Fechar modal ao clicar fora da área do conteúdo
        window.addEventListener('click', function(event) {
            if (event.target === confirmModal) {
                confirmModal.style.display = 'none';
            }
            if (event.target === rejectModal) {
                rejectModal.style.display = 'none';
            }
            if (event.target === finishModal) {
                finishModal.style.display = 'none';
            }
        });
        
        // Fechar modal com a tecla ESC
        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                confirmModal.style.display = 'none';
                rejectModal.style.display = 'none';
                finishModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>