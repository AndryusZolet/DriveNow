<?php
require_once 'includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
    exit();
}

// Define a variável global $usuario para uso nas páginas
$usuario = getUsuario();

// Verificar se o usuário é um dono e contar veículos
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

$totalVeiculos = 0;
$totalReservas = 0;
$reservasAtivas = 0;

if ($dono) {
    // Contar veículos do dono
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM veiculo WHERE dono_id = ?");
    $stmt->execute([$dono['id']]);
    $totalVeiculos = $stmt->fetchColumn();
    
    // Contar reservas dos veículos do dono
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva r 
                          JOIN veiculo v ON r.veiculo_id = v.id 
                          WHERE v.dono_id = ?");
    $stmt->execute([$dono['id']]);
    $totalReservas = $stmt->fetchColumn();
}

// Contar reservas do usuário (tanto locatário quanto proprietário)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$minhasReservas = $stmt->fetchColumn();

// Contar reservas ativas do usuário (com data futura)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva 
                      WHERE conta_usuario_id = ? AND reserva_data >= CURDATE() AND status = 'confirmada'");
$stmt->execute([$usuario['id']]);
$reservasAtivas = $stmt->fetchColumn();
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DriveNow</title>
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
                        <a href="index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
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
        <div class="mb-8 text-center md:text-left px-4">
            <h2 class="text-3xl md:text-4xl font-bold text-white">
                Bem-vindo, <?= htmlspecialchars($usuario['primeiro_nome'] . ' ' . $usuario['segundo_nome']) ?>!
            </h2>
            <p class="text-white/70 mt-2">Gerencie suas reservas e veículos com facilidade</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4">
            <div class="col-span-1 lg:col-span-3 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4 md:mb-0">
                        <div class="relative h-20 w-20 ring-4 ring-white/10 rounded-full flex items-center justify-center bg-indigo-500 text-white overflow-hidden">
                            <?php if (isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil'])): ?>
                                <img src="<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                            <?php else: ?>
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($usuario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=30" alt="Usuário" class="h-full w-full object-cover">
                            <?php endif; ?>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-xl font-bold text-white">Seus Dados</h3>
                            <div class="grid gap-1">
                                <p class="text-white/90"><span class="text-white/60">Nome:</span> <?= htmlspecialchars($usuario['primeiro_nome'] . ' ' . $usuario['segundo_nome']) ?></p>
                                <p class="text-white/90"><span class="text-white/60">E-mail:</span> <?= htmlspecialchars($usuario['e_mail']) ?></p>
                                <p class="text-white/90"><span class="text-white/60">Membro desde:</span> <?= isset($usuario['data_de_entrada']) && $usuario['data_de_entrada'] ? date('d/m/Y', strtotime($usuario['data_de_entrada'])) : 'Data não disponível' ?></p>
                                <p class="text-white/90"><span class="text-white/60">Suas reservas ativas:</span> <?= $reservasAtivas ?></p>
                                <p class="text-white/90"><span class="text-white/60">Documentos:</span>
                                    <?php if ($usuario['status_docs'] === 'aprovado'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                            Aprovado
                                        </span>
                                    <?php elseif ($usuario['status_docs'] === 'rejeitado'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-300 border border-red-400/30">
                                            Rejeitado
                                        </span>
                                    <?php elseif ($usuario['status_docs'] === 'pendente'): ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                            Pendente
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                            Não enviado
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-4 md:mt-0">
                        <?php if (isAdmin()): ?>
                            <a href="admin/dadmin.php" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg flex items-center">
                                Painel do Admin
                            </a>
                        <?php endif; ?>
                        <a href="perfil/editar.php" class="bg-blue-500 hover:bg-blue-600 text-white rounded-xl transition-colors border border-blue-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg">
                            Editar Perfil
                        </a>
                        <a href="logout.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 text-sm font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg">
                            Sair
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($dono): ?>
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-2xl bg-indigo-500/30 text-white border border-indigo-400/30">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><path d="M7 17h10"/><circle cx="7" cy="17" r="2"/><path d="M17 17h2"/><circle cx="17" cy="17" r="2"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">Veículos</h3>
                    </div>
                    <p class="text-white/80 mb-6">Você tem <?= $totalVeiculos ?> veiculo(s) no total.</p>
                    <div class="grid items-center gap-4">
                        <button id="btnAdicionarVeiculo" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center" onclick="openVeiculoModal()">
                            Adicionar Veículo
                        </button>
                        <a href="./veiculo/veiculos.php" class="w-full border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                            Gerenciar Veículos
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 rounded-2xl bg-indigo-500/30 text-white border border-indigo-400/30">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><path d="M7 17h10"/><circle cx="7" cy="17" r="2"/><path d="M17 17h2"/><circle cx="17" cy="17" r="2"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">Veículos</h3>
                    </div>
                    <p class="text-white/80 mb-6">Cadastre-se como proprietário para gerenciar veículos.</p>
                    <button class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg" onclick="openModalProprietario()">
                        Torne-se um Proprietário
                    </button>
                </div>
            <?php endif; ?>

            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-3 rounded-2xl bg-purple-500/30 text-white border border-purple-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Suas Reservas</h3>
                </div>
                <p class="text-white/80 mb-6">Você tem <?= $minhasReservas ?> reserva(s) no total, sendo <?= $reservasAtivas ?> ativa(s).</p>
                <div class="grid items-center gap-4">
                    <a href="reserva/minhas_reservas.php" class="w-full bg-purple-500 hover:bg-purple-600 text-white font-medium rounded-xl transition-colors border border-purple-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                        Ver Reservas
                    </a>
                    <?php if ($dono): ?>
                        <a href="reserva/reservas_recebidas.php" class="w-full border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                        Reservas Recebidas
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
                <div class="flex items-center gap-4 mb-6">
                    <div class="p-3 rounded-2xl bg-pink-500/30 text-white border border-pink-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Alugar Veículo</h3>
                </div>
                <p class="text-white/80 mb-6">Encontre o veículo perfeito para sua próxima viagem.</p>
                <a href="reserva/listagem_veiculos.php" class="w-full bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-xl transition-colors border border-pink-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                    Buscar Veículos
                </a>
            </div>
        </div>

        <div class="mt-8 mx-4 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-2xl bg-cyan-500/30 text-white border border-cyan-400/30">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white">Histórico</h3>
                </div>
                <a href="reserva/minhas_reservas.php" class="text-white hover:bg-white/20 px-3 py-2 rounded-md flex items-center backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-sm hover:shadow-md">
                    Ver todos
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1 h-4 w-4"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
            
            <?php
            // Buscar as duas últimas reservas do usuário
            $stmt = $pdo->prepare("SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_placa,
                                CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario
                                FROM reserva r
                                JOIN veiculo v ON r.veiculo_id = v.id
                                JOIN dono d ON v.dono_id = d.id
                                JOIN conta_usuario u ON d.conta_usuario_id = u.id
                                WHERE r.conta_usuario_id = ?
                                ORDER BY r.reserva_data DESC
                                LIMIT 2");
            $stmt->execute([$usuario['id']]);
            $ultimasReservas = $stmt->fetchAll();
            
            if (empty($ultimasReservas)): 
            ?>
                <div class="text-center py-8">
                    <p class="text-white/70">Você ainda não possui histórico de reservas.</p>
                    <a href="reserva/listagem_veiculos.php" class="mt-4 inline-block bg-cyan-500 hover:bg-cyan-600 text-white font-medium rounded-xl transition-colors border border-cyan-400/30 px-4 py-2 shadow-md hover:shadow-lg">
                        Fazer Primeira Reserva
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full">
                        <thead class="border-b border-white/10 text-left">
                            <tr>
                                <th class="px-4 py-3 text-white/70 font-medium">Veículo</th>
                                <th class="px-4 py-3 text-white/70 font-medium">Proprietário</th>
                                <th class="px-4 py-3 text-white/70 font-medium">Período</th>
                                <th class="px-4 py-3 text-white/70 font-medium">Valor</th>
                                <th class="px-4 py-3 text-white/70 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($ultimasReservas as $reserva): 
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- <div class="mt-4 text-center">
                        <a href="reserva/minhas_reservas.php" class="inline-block bg-cyan-500 hover:bg-cyan-600 text-white font-medium rounded-xl transition-colors border border-cyan-400/30 px-4 py-2 shadow-md hover:shadow-lg">
                            Ver Histórico Completo
                        </a>
                    </div> -->
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="container mx-auto mt-16 px-4 pb-8 text-center text-white/60 text-sm">
        <p>© <script>document.write(new Date().getFullYear())</script> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <!-- MODAL DE TORNAR-SE proprietario -->
    <div id="proprietarioModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-50 flex items-center justify-center hidden">
        <div class="w-full max-w-lg backdrop-blur-lg bg-white/10 border subtle-border rounded-3xl p-6 shadow-xl transform transition-all">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 rounded-2xl bg-indigo-500/30 text-white border border-indigo-400/30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                        <path d="M7 17h10"/>
                        <circle cx="7" cy="17" r="2"/>
                        <path d="M17 17h2"/>
                        <circle cx="17" cy="17" r="2"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white">Registrar-se como Proprietário</h3>
                <button type="button" onclick="closeModalProprietario()" class="ml-auto text-white/70 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div id="modalError" class="mb-6 bg-red-500/20 border border-red-400/30 text-white px-4 py-3 rounded-xl hidden"></div>
            
            <div class="text-white/80 mb-6 space-y-3">
                <p>Ao se cadastrar como proprietário na DriveNow, você terá acesso exclusivo às ferramentas de gestão de frota que permitem cadastrar, monitorar e administrar seus veículos na plataforma.</p>
                
                <p>Como proprietário certificado, você poderá:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Cadastrar múltiplos veículos em sua conta</li>
                    <li>Definir disponibilidade e tarifas personalizadas</li>
                    <li>Acessar relatórios detalhados de utilização</li>
                    <li>Receber pagamentos diretamente em sua conta bancária</li>
                </ul>
                
                <p class="mt-4 text-sm border-t border-white/10 pt-3">
                    Ao clicar em "Confirmar Registro", você reconhece que leu, compreendeu e concorda com os <a href="termos_proprietario.html" class="text-indigo-300 hover:text-indigo-200 underline" target="_blank">Termos para Proprietários</a> e <a href="politicas.html" class="text-indigo-300 hover:text-indigo-200 underline" target="_blank">Política de Uso</a> da DriveNow.
                </p>
            </div>
            
            <div class="flex gap-3">
                <button type="button" id="btnConfirmarRegistro" class="flex-1 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span>Confirmar Registro</span>
                </button>
                <button type="button" onclick="closeModalProprietario()" class="flex-1 border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    <span>Voltar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Incluir o componente do modal de veículo -->
    <?php include_once 'components/modal_veiculo.php'; ?>

    <script src="./assets/notifications.js"></script>
    <script>
        // Verificar se há um parâmetro na URL para abrir o modal
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openModal') === 'veiculos') {
                openVeiculoModal();

                // Então limpa o parâmetro da URL sem recarregar a página
                const newUrl = window.location.pathname;
                window.history.pushState({}, '', newUrl);
            }
        });

        // Funções para o modal de proprietário
        function openModalProprietario() {
            const modal = document.getElementById('proprietarioModal');
            modal.classList.remove('hidden');
            
            // Resetar mensagem de erro
            document.getElementById('modalError').classList.add('hidden');
            document.getElementById('modalError').textContent = '';
        }

        function closeModalProprietario() {
            const modal = document.getElementById('proprietarioModal');
            modal.classList.add('hidden');
        }

        // Processamento AJAX para registro de proprietário
        document.getElementById('btnConfirmarRegistro').addEventListener('click', function() {
            // Mostrar estado de loading no botão
            const btnConfirmar = this;
            const btnText = btnConfirmar.querySelector('span');
            const originalText = btnText.textContent;
            
            btnConfirmar.disabled = true;
            btnText.textContent = 'Processando...';
            
            // Fazer a requisição AJAX
            fetch('./perfil/registrar_proprietario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Restaurar botão
                btnConfirmar.disabled = false;
                btnText.textContent = originalText;
                
                if (data.status === 'success') {
                    // Fechar o modal
                    closeModalProprietario();
                    
                    // Mostrar notificação de sucesso
                    notifySuccess(data.message);
                    
                    // Atualizar a UI para refletir que agora é proprietário
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    
                } else {
                    // Mostrar erro no modal
                    const modalError = document.getElementById('modalError');
                    modalError.textContent = data.message;
                    modalError.classList.remove('hidden');
                    
                    // Também mostrar como notificação
                    notifyError(data.message);
                }
            })
            .catch(error => {
                // Restaurar botão
                btnConfirmar.disabled = false;
                btnText.textContent = originalText;
                
                // Mostrar erro
                notifyError('Erro de comunicação com o servidor. Tente novamente.');
                console.error('Erro:', error);
            });
        });

        // Adicionar listener ESC para o modal de proprietário
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModalProprietario();
            }
        });

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
    
    <!-- Importar o JavaScript do modal de veículo -->
    <script src="./components/modal_veiculo.js"></script>
</body>
</html>