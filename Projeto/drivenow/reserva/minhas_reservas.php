<?php
require_once '../includes/auth.php';

verificarAutenticacao();

$usuario = getUsuario();

// Buscar reservas do usuário
global $pdo;
$stmt = $pdo->prepare("SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_placa,
                      CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario
                      FROM reserva r
                      JOIN veiculo v ON r.veiculo_id = v.id
                      JOIN dono d ON v.dono_id = d.id
                      JOIN conta_usuario u ON d.conta_usuario_id = u.id
                      WHERE r.conta_usuario_id = ?
                      ORDER BY r.reserva_data DESC");
$stmt->execute([$usuario['id']]);
$reservas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas - DriveNow</title>
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
        <div class="flex justify-between items-center mb-8 px-4">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-white">
                    Minhas Reservas
                </h2>
                <p class="text-white/70 mt-2">Gerencie suas reservas de veículos</p>
            </div>
            <div class="flex gap-4">
                <a href="../vboard.php" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 font-medium shadow-md hover:shadow-lg flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    <span>Voltar</span>
                </a>
                <a href="listagem_veiculos.php" class="bg-purple-500 hover:bg-purple-600 text-white font-medium rounded-xl transition-colors border border-purple-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                        <path d="M5 12h14"></path>
                        <path d="M12 5v14"></path>
                    </svg>
                    <span>Nova Reserva</span>
                </a>
            </div>
        </div>

        <?php if (empty($reservas)): ?>
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-8 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4">
                <div class="text-center py-8">
                    <div class="p-6 rounded-full bg-purple-500/20 text-white border border-purple-400/30 inline-flex mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-10 w-10">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Você ainda não possui reservas</h3>
                    <p class="text-white/70 mb-6">Explore nossa frota e encontre o veículo perfeito para sua próxima viagem.</p>
                    <a href="listagem_veiculos.php" class="bg-purple-500 hover:bg-purple-600 text-white font-medium rounded-xl transition-colors border border-purple-400/30 px-6 py-3 shadow-md hover:shadow-lg inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                            <path d="M7 17h10"/>
                            <circle cx="7" cy="17" r="2"/>
                            <path d="M17 17h2"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                        Ver Veículos Disponíveis
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4 overflow-x-auto">
                <table class="w-full min-w-full">
                    <thead class="border-b border-white/10 text-left">
                        <tr>
                            <th class="px-4 py-3 text-white/70 font-medium">Veículo</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Proprietário</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Período</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Valor Total</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Status</th>
                            <th class="px-4 py-3 text-white/70 font-medium">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($reservas as $reserva): ?>
                            <?php
                                // Determinar o status e a classe de cor baseado no status da reserva e nas datas
                                $now = time();
                                $inicio = strtotime($reserva['reserva_data']);
                                $fim = strtotime($reserva['devolucao_data']);

                                // Verificar primeiro o status da reserva no banco de dados
                                if (isset($reserva['status'])) {
                                    switch($reserva['status']) {
                                        case 'rejeitada':
                                            $status = 'Rejeitada';
                                            $statusClass = 'bg-red-500/20 text-red-300 border border-red-400/30';
                                            break;
                                        case 'cancelada':
                                            $status = 'Cancelada';
                                            $statusClass = 'bg-yellow-500/20 text-yellow-300 border border-yellow-400/30';
                                            break;
                                        case 'confirmada':
                                            if ($now < $inicio) {
                                                $status = 'Confirmada';
                                                $statusClass = 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
                                            } elseif ($now >= $inicio && $now <= $fim) {
                                                $status = 'Em andamento';
                                                $statusClass = 'bg-green-500/20 text-green-300 border border-green-400/30';
                                            } else {
                                                $status = 'Concluída';
                                                $statusClass = 'bg-gray-500/20 text-gray-300 border border-gray-400/30';
                                            }
                                            break;
                                        case 'finalizada':
                                            $status = 'Finalizada';
                                            $statusClass = 'bg-indigo-500/20 text-indigo-300 border border-indigo-400/30';
                                            break;
                                        default:
                                            // Para status 'pendente' ou qualquer outro status não especificado
                                            if ($now < $inicio) {
                                                $status = 'Pendente';
                                                $statusClass = 'bg-amber-500/20 text-amber-300 border border-amber-400/30';
                                            } elseif ($now >= $inicio && $now <= $fim) {
                                                $status = 'Em andamento';
                                                $statusClass = 'bg-green-500/20 text-green-300 border border-green-400/30';
                                            } else {
                                                $status = 'Concluída';
                                                $statusClass = 'bg-gray-500/20 text-gray-300 border border-gray-400/30';
                                            }
                                            break;
                                    }
                                } else {
                                    // Fallback para quando a coluna 'status' não existe (compatibilidade)
                                    if ($now < $inicio) {
                                        $status = 'Agendada';
                                        $statusClass = 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
                                    } elseif ($now >= $inicio && $now <= $fim) {
                                        $status = 'Em andamento';
                                        $statusClass = 'bg-green-500/20 text-green-300 border border-green-400/30';
                                    } else {
                                        $status = 'Concluída';
                                        $statusClass = 'bg-gray-500/20 text-gray-300 border border-gray-400/30';
                                    }
                                }
                            ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-white">
                                            <?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?>
                                        </span>
                                        <span class="text-sm text-white/60">
                                            <?= htmlspecialchars($reserva['veiculo_placa']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-white">
                                    <?= htmlspecialchars($reserva['nome_proprietario']) ?>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-white">
                                            <?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?>
                                        </span>
                                        <span class="text-white">
                                            a <?= date('d/m/Y', strtotime($reserva['devolucao_data'])) ?>
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
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <a href="detalhes_reserva.php?id=<?= $reserva['id'] ?>" class="bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors">
                                            Detalhes
                                        </a>
                                        <?php if ((!isset($reserva['status']) || $reserva['status'] == 'pendente' || $reserva['status'] === null) && $now < $inicio): ?>
                                            <a href="cancelar_reserva.php?id=<?= $reserva['id'] ?>" 
                                            class="bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-lg px-3 py-1 text-sm font-medium transition-colors"
                                            onclick="return confirm('Tem certeza que deseja cancelar esta reserva?')">
                                                Cancelar
                                            </a>
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
        <?php
        if (isset($_SESSION['notification'])) {
            $type = $_SESSION['notification']['type'];
            $message = $_SESSION['notification']['message'];
            
            if ($type === 'success') {
                echo "notifySuccess('" . addslashes($message) . "');";
            } elseif ($type === 'error') {
                echo "notifyError('" . addslashes($message) . "', 12000);";
            } elseif ($type === 'warning') {
                echo "notifyWarning('" . addslashes($message) . "');";
            } else {
                echo "notifyInfo('" . addslashes($message) . "');";
            }
            
            // Limpar a notificação da sessão após exibi-la
            unset($_SESSION['notification']);
        }
        ?>
    </script>
</body>
</html>