<?php
require_once '../includes/auth.php';

// Verificar autenticação
if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();
global $pdo;

// Obter reservas do usuário (tanto como locatário quanto como proprietário)
$stmt = $pdo->prepare("
    SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano, v.veiculo_placa,
           loc.nome_local, c.cidade_nome, e.sigla,
           proprio.primeiro_nome AS dono_nome, proprio.segundo_nome AS dono_sobrenome,
           locat.primeiro_nome AS locatario_nome, locat.segundo_nome AS locatario_sobrenome,
           (SELECT COUNT(m.id) FROM mensagem m WHERE m.reserva_id = r.id) AS total_mensagens,
           (SELECT COUNT(m.id) FROM mensagem m WHERE m.reserva_id = r.id AND m.remetente_id != ? AND m.lida = 0) AS nao_lidas
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN dono d ON v.dono_id = d.id
    INNER JOIN conta_usuario proprio ON d.conta_usuario_id = proprio.id
    INNER JOIN conta_usuario locat ON r.conta_usuario_id = locat.id
    LEFT JOIN local loc ON v.local_id = loc.id
    LEFT JOIN cidade c ON loc.cidade_id = c.id
    LEFT JOIN estado e ON c.estado_id = e.id
    WHERE r.conta_usuario_id = ? OR proprio.id = ?
    ORDER BY nao_lidas DESC, r.reserva_data DESC
");
$stmt->execute([$usuario['id'], $usuario['id'], $usuario['id']]);
$reservas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Mensagens - DriveNow</title>
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

        .message-container {
            height: calc(100vh - 300px);
            min-height: 300px;
        }

        .message-item {
            max-width: 80%;
        }

        .message-item.sent {
            background-color: rgba(79, 70, 229, 0.2);
            border-color: rgba(79, 70, 229, 0.3);
            margin-left: auto;
            border-radius: 16px 16px 4px 16px;
        }

        .message-item.received {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            margin-right: auto;
            border-radius: 16px 16px 16px 4px;
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-4">Minhas Mensagens</h1>
            <p class="text-white/70">Gerencie suas conversas com locadores e locatários.</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Lista de conversas -->
            <div class="col-span-1 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Conversas</h2>
                
                <?php if (empty($reservas)): ?>
                    <div class="py-8 text-center">
                        <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium mb-2">Nenhuma conversa</h3>
                        <p class="text-white/60 text-sm">Suas conversas sobre reservas aparecerão aqui.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 max-h-[70vh] overflow-y-auto pr-2">
                        <?php foreach ($reservas as $reserva): ?>
                            <?php 
                                // Determinar se o usuário é o proprietário ou o locatário
                                $ehProprietario = $reserva['dono_nome'] . ' ' . $reserva['dono_sobrenome'] === $usuario['primeiro_nome'] . ' ' . $usuario['segundo_nome'];
                                
                                // Nome da outra pessoa na conversa
                                $outraPessoa = $ehProprietario 
                                    ? $reserva['locatario_nome'] . ' ' . $reserva['locatario_sobrenome'] 
                                    : $reserva['dono_nome'] . ' ' . $reserva['dono_sobrenome'];
                                
                                // Sobre o que é a conversa
                                $sobreReserva = $reserva['veiculo_marca'] . ' ' . $reserva['veiculo_modelo'] . ' (' . $reserva['veiculo_ano'] . ')';
                                
                                // Formatação das datas
                                $dataInicio = date('d/m/Y', strtotime($reserva['reserva_data']));
                                $dataFim = date('d/m/Y', strtotime($reserva['devolucao_data']));
                            ?>
                            <a href="mensagens_conversa.php?reserva=<?= $reserva['id'] ?>" class="block backdrop-blur-md bg-white/5 hover:bg-white/10 border subtle-border rounded-2xl p-4 transition-all">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center overflow-hidden">
                                        <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($outraPessoa) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="<?= htmlspecialchars($outraPessoa) ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <h3 class="font-medium text-white truncate"><?= htmlspecialchars($outraPessoa) ?></h3>
                                            <?php if ($reserva['nao_lidas'] > 0): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-indigo-500 text-white ml-2"><?= $reserva['nao_lidas'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-white/60 text-sm truncate"><?= htmlspecialchars($sobreReserva) ?></p>
                                        <div class="flex justify-between items-center mt-1">
                                            <span class="text-white/50 text-xs"><?= $dataInicio ?> - <?= $dataFim ?></span>
                                            <span class="text-white/50 text-xs"><?= $reserva['total_mensagens'] ?> msg</span>
                                        </div>
                                        <div class="mt-1">
                                            <?php
                                                $statusClasses = [
                                                    'pendente' => 'bg-yellow-500/20 text-yellow-300 border-yellow-400/30',
                                                    'confirmada' => 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30',
                                                    'em_andamento' => 'bg-blue-500/20 text-blue-300 border-blue-400/30',
                                                    'finalizada' => 'bg-gray-500/20 text-gray-300 border-gray-400/30',
                                                    'cancelada' => 'bg-gray-500/20 text-gray-300 border-gray-400/30',
                                                    'rejeitada' => 'bg-red-500/20 text-red-300 border-red-400/30'
                                                ];
                                                $statusLabels = [
                                                    'pendente' => 'Pendente',
                                                    'confirmada' => 'Confirmada',
                                                    'em_andamento' => 'Em Andamento',
                                                    'finalizada' => 'Finalizada',
                                                    'cancelada' => 'Cancelada',
                                                    'rejeitada' => 'Rejeitada'
                                                ];
                                                $statusClass = $statusClasses[$reserva['status']] ?? 'bg-gray-500/20 text-gray-300 border-gray-400/30';
                                                $statusLabel = $statusLabels[$reserva['status']] ?? 'Desconhecido';
                                            ?>
                                            <span class="px-2 py-0.5 text-xs rounded-full <?= $statusClass ?> border inline-block">
                                                <?= $statusLabel ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Área de conversa (exibida quando nenhuma conversa está selecionada) -->
            <div class="col-span-1 lg:col-span-2 backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg flex flex-col">
                <div class="flex-1 flex flex-col items-center justify-center py-12">
                    <div class="p-6 rounded-full bg-indigo-500/20 text-indigo-300 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-10 w-10">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold mb-2">Suas mensagens</h2>
                    <p class="text-white/70 text-center max-w-md mb-6">Selecione uma conversa na lista ao lado para visualizar e responder às mensagens.</p>
                    
                    <div class="text-center space-y-4">
                        <p class="text-white/50 text-sm">Precisa de ajuda?</p>
                        <a href="#" class="inline-block bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-6 py-2 shadow-md hover:shadow-lg">
                            Contatar Suporte
                        </a>
                    </div>
                </div>
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
