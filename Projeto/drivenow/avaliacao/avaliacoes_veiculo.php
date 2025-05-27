<?php
require_once '../includes/auth.php';

// Verificar se o ID do veículo foi fornecido
if (!isset($_GET['veiculo']) || !is_numeric($_GET['veiculo'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Veículo não especificado.'
    ];
    header('Location: ../reserva/listagem_veiculos.php');
    exit;
}

$veiculoId = (int)$_GET['veiculo'];
global $pdo;

// Buscar detalhes do veículo
$stmt = $pdo->prepare("
    SELECT v.*, cv.categoria, 
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario,
           u.id AS proprietario_id,
           l.nome_local, c.cidade_nome, e.sigla
    FROM veiculo v
    LEFT JOIN categoria_veiculo cv ON v.categoria_veiculo_id = cv.id
    LEFT JOIN dono d ON v.dono_id = d.id
    LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
    LEFT JOIN local l ON v.local_id = l.id
    LEFT JOIN cidade c ON l.cidade_id = c.id
    LEFT JOIN estado e ON c.estado_id = e.id
    WHERE v.id = ?
");
$stmt->execute([$veiculoId]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Veículo não encontrado.'
    ];
    header('Location: ../reserva/listagem_veiculos.php');
    exit;
}

// Buscar avaliações do veículo
$stmt = $pdo->prepare("
    SELECT av.*, r.reserva_data, r.devolucao_data, 
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_usuario
    FROM avaliacao_veiculo av
    INNER JOIN reserva r ON av.reserva_id = r.id
    INNER JOIN conta_usuario u ON av.usuario_id = u.id
    WHERE av.veiculo_id = ?
    ORDER BY av.data_avaliacao DESC
");
$stmt->execute([$veiculoId]);
$avaliacoes = $stmt->fetchAll();

// Buscar avaliações do proprietário
$stmt = $pdo->prepare("
    SELECT ap.*, r.reserva_data, r.devolucao_data, 
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_usuario
    FROM avaliacao_proprietario ap
    INNER JOIN reserva r ON ap.reserva_id = r.id
    INNER JOIN conta_usuario u ON ap.usuario_id = u.id
    WHERE ap.proprietario_id = ?
    ORDER BY ap.data_avaliacao DESC
    LIMIT 5
");
$stmt->execute([$veiculo['proprietario_id']]);
$avaliacoesProprietario = $stmt->fetchAll();

// Calcular estatísticas
$totalAvaliacoes = count($avaliacoes);
$mediaNota = 0;
$contagem = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

if ($totalAvaliacoes > 0) {
    $somaNotas = 0;
    foreach ($avaliacoes as $avaliacao) {
        $somaNotas += $avaliacao['nota'];
        $contagem[$avaliacao['nota']]++;
    }
    $mediaNota = $somaNotas / $totalAvaliacoes;
}

// Verificar se o usuário está logado (opcional)
$estaLogado = estaLogado();
$usuario = $estaLogado ? getUsuario() : null;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações - <?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?> - DriveNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #1e1b4b, #312e81);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .subtle-border {
            border-color: rgba(255, 255, 255, 0.1);
        }
        .card-shine {
            position: relative;
            overflow: hidden;
        }
        .card-shine::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 40%);
            transform: translateY(-100%) translateX(-100%);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.8s ease;
        }
        .card-shine:hover::before {
            transform: translateY(0) translateX(0);
            opacity: 1;
        }
        
        /* Estilo para as estrelas de avaliação */
        .stars {
            display: inline-flex;
            align-items: center;
        }
        .stars .star {
            color: rgba(255, 255, 255, 0.2);
        }
        .stars .star.filled {
            color: #f0b429;  /* cor da estrela ativa */
        }
    </style>
</head>
<body class="text-white">
    <header class="bg-white/5 backdrop-blur-lg border-b subtle-border py-4">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white mr-8">DriveNow</h1>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
                        <a href="../vboard.php" class="text-white/80 hover:text-white transition-colors">Dashboard</a>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <?php if ($estaLogado): ?>
                        <span class="text-white hidden md:inline"><?= htmlspecialchars($usuario['primeiro_nome']) ?></span>
                        <div class="relative h-8 w-8 transition-transform hover:scale-110 rounded-full flex items-center justify-center bg-indigo-500 text-white overflow-hidden">
                            <?php if (isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil'])): ?>
                                <img src="<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                            <?php else: ?>
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($usuario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <a href="../login.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-3 py-1 text-sm font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg">Login</a>
                        <a href="../cadastro.php" class="border border-indigo-400/30 bg-indigo-500 hover:bg-indigo-600 rounded-xl px-3 py-1 text-sm font-medium shadow-md hover:shadow-lg">Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white">Avaliações e Comentários</h2>
                <p class="text-white/70 mt-2"><?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?>, <?= htmlspecialchars($veiculo['veiculo_ano']) ?></p>
            </div>
            <a href="javascript:history.back()" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <span>Voltar</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Coluna 1: Informações do veículo -->
            <div class="lg:col-span-1">
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6 card-shine space-y-6">
                    <!-- Detalhes do veículo -->
                    <div class="text-center mb-6">
                        <div class="bg-gradient-to-r from-indigo-500/30 to-purple-500/30 p-8 flex items-center justify-center rounded-xl mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16 text-white">
                                <path d="M5 17h14v4H5z"></path>
                                <path d="M8 17v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v4"></path>
                                <path d="M5 11a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v6H5z"></path>
                                <path d="M5.5 11v-3"></path>
                                <path d="M18.5 11v-3"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold"><?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?></h3>
                        <p class="text-white/70 text-lg"><?= htmlspecialchars($veiculo['veiculo_ano']) ?></p>
                        <div class="mt-2 flex justify-center">
                            <div class="px-3 py-1 rounded-full text-sm font-medium bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                <?= htmlspecialchars($veiculo['categoria'] ?? 'N/D') ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resumo de avaliações -->
                    <div>
                        <h4 class="text-lg font-semibold mb-3">Resumo das Avaliações</h4>
                        <div class="flex items-center mb-4">
                            <div class="text-3xl font-bold mr-3"><?= number_format($mediaNota, 1, ',', '.') ?></div>
                            <div>
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= round($mediaNota) ? 'filled' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-white/50 text-sm"><?= $totalAvaliacoes ?> avaliações</div>
                            </div>
                        </div>
                        
                        <!-- Distribuição de notas -->
                        <div class="space-y-2">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <div class="flex items-center">
                                    <div class="w-8 text-white/70"><?= $i ?> ★</div>
                                    <div class="flex-grow mx-2">
                                        <div class="h-2 rounded-full bg-white/10 overflow-hidden">
                                            <?php 
                                            $porcentagem = $totalAvaliacoes > 0 ? ($contagem[$i] / $totalAvaliacoes) * 100 : 0;
                                            ?>
                                            <div class="h-full bg-indigo-500" style="width: <?= $porcentagem ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="text-white/70 text-sm"><?= $contagem[$i] ?></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Informações do proprietário -->
                    <div class="border-t border-white/10 pt-6">
                        <h4 class="text-lg font-semibold mb-3">Proprietário</h4>
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full overflow-hidden border border-white/20 mr-3">
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($veiculo['nome_proprietario']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Proprietário" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <p class="font-medium"><?= htmlspecialchars($veiculo['nome_proprietario']) ?></p>
                                <?php if (isset($veiculo['media_avaliacao_proprietario']) && $veiculo['media_avaliacao_proprietario'] > 0): ?>
                                    <div class="flex items-center text-sm text-white/70">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?= $i <= round($veiculo['media_avaliacao_proprietario']) ? 'filled' : '' ?>" style="font-size: 0.875rem;">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="ml-1">(<?= $veiculo['total_avaliacoes_proprietario'] ?? 0 ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <p class="text-sm text-white/50">Sem avaliações</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Local -->
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </div>
                            <div class="text-white/70">
                                <?= htmlspecialchars($veiculo['nome_local'] ?? 'N/D') ?>
                                <?php if (isset($veiculo['cidade_nome'])): ?>
                                    <span class="text-white/50 text-xs">(<?= htmlspecialchars($veiculo['cidade_nome']) ?>-<?= htmlspecialchars($veiculo['sigla']) ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preço -->
                    <div class="border-t border-white/10 pt-6">
                        <div class="flex items-center justify-between">
                            <span class="text-white/70">Preço Diário:</span>
                            <span class="text-xl font-bold">R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?></span>
                        </div>
                        
                        <a href="../reserva/listagem_veiculos.php?veiculo=<?= $veiculoId ?>" class="mt-4 w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white rounded-xl transition-colors border border-indigo-400/30 px-4 py-3 font-medium shadow-md hover:shadow-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            <span>Reservar</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Coluna 2-3: Avaliações -->
            <div class="lg:col-span-2">
                <!-- Avaliações do veículo -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-semibold mb-4">Avaliações do Veículo</h3>
                    
                    <?php if (empty($avaliacoes)): ?>
                        <div class="text-center py-10">
                            <div class="p-6 rounded-full bg-white/10 text-white inline-flex mb-6">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-10 w-10">
                                    <path d="M17.5 12h.5c.28 0 .5.22.5.5v2a3.5 3.5 0 0 1-7 0v-2c0-.28.22-.5.5-.5h6.5Z"></path>
                                    <path d="M16.5 6a9 9 0 0 0-9 9"></path>
                                    <path d="M14 5a12 12 0 0 0-12 12"></path>
                                    <path d="M18.5 6a9 9 0 0 1 9 9"></path>
                                    <path d="M21 5a12 12 0 0 1 12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-4">Nenhuma avaliação ainda</h3>
                            <p class="text-white/70 mb-6">Este veículo ainda não recebeu avaliações.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                                <div class="bg-white/5 border subtle-border rounded-xl p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full overflow-hidden border border-white/20 mr-3">
                                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($avaliacao['nome_usuario']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                                            </div>
                                            <div>
                                                <p class="font-medium"><?= htmlspecialchars($avaliacao['nome_usuario']) ?></p>
                                                <p class="text-xs text-white/50">
                                                    <?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?= $i <= $avaliacao['nota'] ? 'filled' : '' ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-white/90">
                                        <?php if (!empty($avaliacao['comentario'])): ?>
                                            <?= nl2br(htmlspecialchars($avaliacao['comentario'])) ?>
                                        <?php else: ?>
                                            <p class="text-white/50 italic">Sem comentários</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-3 text-xs text-white/50">
                                        Período de reserva: <?= date('d/m/Y', strtotime($avaliacao['reserva_data'])) ?> a <?= date('d/m/Y', strtotime($avaliacao['devolucao_data'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Avaliações do proprietário -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4">Avaliações do Proprietário</h3>
                    
                    <?php if (empty($avaliacoesProprietario)): ?>
                        <div class="text-center py-6">
                            <p class="text-white/70">O proprietário ainda não recebeu avaliações.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($avaliacoesProprietario as $avaliacao): ?>
                                <div class="bg-white/5 border subtle-border rounded-xl p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full overflow-hidden border border-white/20 mr-3">
                                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($avaliacao['nome_usuario']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                                            </div>
                                            <div>
                                                <p class="font-medium"><?= htmlspecialchars($avaliacao['nome_usuario']) ?></p>
                                                <p class="text-xs text-white/50">
                                                    <?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="stars">
                                            <?php for ($i = if (isset($veiculo['id'])): ?>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?= $i <= $avaliacao['nota'] ? 'filled' : '' ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-white/90">
                                        <?php if (!empty($avaliacao['comentario'])): ?>
                                            <?= nl2br(htmlspecialchars($avaliacao['comentario'])) ?>
                                        <?php else: ?>
                                            <p class="text-white/50 italic">Sem comentários</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($avaliacoesProprietario) > 0): ?>
                        <div class="mt-4 text-center">
                            <a href="avaliacoes_proprietario.php?id=<?= $veiculo['proprietario_id'] ?>" class="text-indigo-300 hover:text-indigo-200 transition-colors inline-flex items-center">
                                Ver todas as avaliações do proprietário
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1 h-4 w-4">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white/5 backdrop-blur-sm border-t subtle-border py-6 mt-12">
        <div class="container mx-auto px-4">
            <div class="text-center text-white/50 text-sm">
                &copy; <?= date('Y') ?> DriveNow. Todos os direitos reservados.
            </div>
        </div>
    </footer>
</body>
</html>
