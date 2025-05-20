<?php
require_once '../includes/auth.php';

verificarAutenticacao();

// Define a variável global $usuario para uso nas páginas
$usuario = getUsuario();

// Verificar se o usuário é um dono e obter estatísticas relevantes
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

$totalVeiculos = 0;
$totalReservas = 0;
$reservasAtivas = 0;

if ($dono) {
    // Consulta otimizada: obtém múltiplas estatísticas em uma única query
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM veiculo WHERE dono_id = :dono_id) AS total_veiculos,
            (SELECT COUNT(*) FROM reserva r 
             JOIN veiculo v ON r.veiculo_id = v.id 
             WHERE v.dono_id = :dono_id) AS total_reservas
    ");
    $stmt->bindParam(':dono_id', $dono['id']);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $totalVeiculos = $result['total_veiculos'];
    $totalReservas = $result['total_reservas'];

    // Buscar veículos do dono com informações de categoria e local
    $stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local, 
                          CASE WHEN v.disponivel IS NULL THEN 1 ELSE v.disponivel END as disponivel 
                          FROM veiculo v
                          LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                          LEFT JOIN local l ON v.local_id = l.id
                          WHERE v.dono_id = ? 
                          ORDER BY v.id DESC");
    
    $stmt->execute([$dono['id']]);
    $veiculos = $stmt->fetchAll();
} else {
    $veiculos = [];
}

// Consulta otimizada para reservas do usuário
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_minhas_reservas,
        SUM(CASE WHEN reserva_data >= CURDATE() THEN 1 ELSE 0 END) AS reservas_ativas
    FROM reserva 
    WHERE conta_usuario_id = :usuario_id
");
$stmt->bindParam(':usuario_id', $usuario['id']);
$stmt->execute();
$resultReservas = $stmt->fetch();

$minhasReservas = $resultReservas['total_minhas_reservas'];
$reservasAtivas = $resultReservas['reservas_ativas'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Veiculos - DriveNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: sans-serif; /* Defina uma fonte base se necessário */
        }
        /* Para garantir que os orbes de vidro funcionem com a animação de pulso do Tailwind e duração customizada */
        .animate-pulse-15s { animation-duration: 15s; }
        .animate-pulse-20s { animation-duration: 20s; }
        .animate-pulse-25s { animation-duration: 25s; }

        /* Para o efeito de borda sutil nos cards e header quando o fundo é muito escuro */
        .subtle-border {
            border-color: rgba(255, 255, 255, 0.1); /* Ajuste a opacidade conforme necessário */
        }

        option {
            background-color: #172554; /* Azul-marinho profundo */
            color: white;
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
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h2 class="text-2xl md:text-3xl font-bold text-white mb-4 md:mb-0">Meus Veículos</h2>
                <?php if ($dono): ?>
                    <div class="flex gap-3">
                        <a href="../vboard.php" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 font-medium shadow-md hover:shadow-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                                <path d="m12 19-7-7 7-7"></path>
                                <path d="M19 12H5"></path>
                            </svg>
                            <span>Voltar</span>
                        </a>
                        <button onclick="openVeiculoModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-colors border border-emerald-400/30 px-4 py-2 font-medium shadow-md hover:shadow-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Adicionar Veículo
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($veiculos)): ?>
                <div class="backdrop-blur-lg bg-indigo-500/20 border border-indigo-400/30 text-white px-6 py-4 rounded-xl mb-6">
                    <?php if ($dono): ?>
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            Você ainda não possui veículos cadastrados.
                        </p>
                    <?php else: ?>
                        <p class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            Você não é registrado como proprietário de veículos.
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg">
                    <table class="w-full min-w-max">
                        <thead>
                            <tr class="bg-indigo-900/40 text-white text-sm uppercase">
                                <th class="px-4 py-3 text-left">Marca</th>
                                <th class="px-4 py-3 text-left">Modelo</th>
                                <th class="px-4 py-3 text-center">Placa</th>
                                <th class="px-4 py-3 text-center">Ano</th>
                                <th class="px-4 py-3 text-center">KM</th>
                                <th class="px-4 py-3 text-center">Câmbio</th>
                                <th class="px-4 py-3 text-center">Combustível</th>
                                <th class="px-4 py-3 text-center">Tração</th>
                                <th class="px-4 py-3 text-center">Categoria</th>
                                <th class="px-4 py-3 text-center">Localização</th>
                                <th class="px-4 py-3 text-center">Diário</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php foreach ($veiculos as $veiculo): 
                                // Definir disponivel como 1 (disponível) por padrão se não existir
                                $disponivel = $veiculo['disponivel'] ?? 1;
                            ?>
                                <tr class="hover:bg-white/5 transition-colors text-white">
                                    <td class="px-4 py-3 text-left"><?= htmlspecialchars($veiculo['veiculo_marca']) ?></td>
                                    <td class="px-4 py-3 text-left"><?= htmlspecialchars($veiculo['veiculo_modelo']) ?></td>
                                    <td class="px-4 py-3 text-center font-mono"><?= htmlspecialchars($veiculo['veiculo_placa']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($veiculo['veiculo_ano']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= $veiculo['veiculo_km'] ? number_format($veiculo['veiculo_km'], 0, ',', '.') . ' km' : '-' ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($veiculo['veiculo_cambio']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($veiculo['veiculo_combustivel']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($veiculo['veiculo_tracao']) ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($veiculo['categoria'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-center"><?= htmlspecialchars($veiculo['nome_local'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-center font-medium">R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($disponivel == 1): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-500/30 text-emerald-100 border border-emerald-400/30">Disponível</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-500/30 text-gray-100 border border-gray-400/30">Indisponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex gap-2 justify-center">
                                            <a href="./editar.php?id=<?= $veiculo['id'] ?>" class="p-1.5 rounded-lg bg-amber-500/20 text-amber-100 border border-amber-400/30 hover:bg-amber-500/40 transition-colors" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <a href="./ativar.php?id=<?= $veiculo['id'] ?>&status=<?= $disponivel == 1 ? 0 : 1 ?>" 
                                            class="p-1.5 rounded-lg <?= $disponivel == 1 ? 'bg-gray-500/20 text-gray-100 border border-gray-400/30 hover:bg-gray-500/40' : 'bg-cyan-500/20 text-cyan-100 border border-cyan-400/30 hover:bg-cyan-500/40' ?> transition-colors" 
                                            title="<?= $disponivel == 1 ? 'Desativar' : 'Ativar' ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                                                    <line x1="12" y1="2" x2="12" y2="12"></line>
                                                </svg>
                                            </a>
                                            <a href="./excluir.php?id=<?= $veiculo['id'] ?>" 
                                            class="p-1.5 rounded-lg bg-red-500/20 text-red-100 border border-red-400/30 hover:bg-red-500/40 transition-colors" 
                                            title="Excluir"
                                            onclick="return confirm('Tem certeza que deseja excluir este veículo?')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Incluir o componente do modal de veículo -->
    <?php include_once '../components/modal_veiculo.php'; ?>
    
    <script>
        // Verificar se há um parâmetro na URL para abrir o modal
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('openModal') === 'veiculos') {
                openVeiculoModal();
                
                // Limpar o parâmetro da URL sem recarregar a página
                const newUrl = window.location.pathname;
                window.history.pushState({}, '', newUrl);
            }
            
            // Adicionar listener para tecla ESC para fechar o modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && typeof closeVeiculoModal === 'function') {
                    closeVeiculoModal();
                }
            });
        });
    </script>
    
    <!-- Script para notificações -->
    <script src="../assets/notifications.js"></script>
    
    <!-- Importar o JavaScript do modal de veículo -->
    <script src="../components/modal_veiculo.js"></script>
</body>
</html>