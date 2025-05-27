<?php
require_once '../includes/auth.php';

// Verificar se o ID do proprietário foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Proprietário não especificado.'
    ];
    header('Location: ../index.php');
    exit;
}

$proprietarioId = (int)$_GET['id'];
global $pdo;

// Buscar detalhes do proprietário
$stmt = $pdo->prepare("
    SELECT u.*, d.id as dono_id
    FROM conta_usuario u
    INNER JOIN dono d ON u.id = d.conta_usuario_id
    WHERE u.id = ?
");
$stmt->execute([$proprietarioId]);
$proprietario = $stmt->fetch();

if (!$proprietario) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Proprietário não encontrado.'
    ];
    header('Location: ../index.php');
    exit;
}

// Buscar avaliações do proprietário
$stmt = $pdo->prepare("
    SELECT ap.*, r.reserva_data, r.devolucao_data, r.veiculo_id,
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_usuario,
           v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano
    FROM avaliacao_proprietario ap
    INNER JOIN reserva r ON ap.reserva_id = r.id
    INNER JOIN conta_usuario u ON ap.usuario_id = u.id
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    WHERE ap.proprietario_id = ?
    ORDER BY ap.data_avaliacao DESC
");
$stmt->execute([$proprietarioId]);
$avaliacoes = $stmt->fetchAll();

// Buscar os veículos do proprietário
$stmt = $pdo->prepare("
    SELECT v.id, v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano, v.media_avaliacao, v.total_avaliacoes
    FROM veiculo v
    WHERE v.dono_id = ?
    AND v.disponivel = 1
    ORDER BY v.media_avaliacao DESC
    LIMIT 5
");
$stmt->execute([$proprietario['dono_id']]);
$veiculos = $stmt->fetchAll();

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
    <title>Avaliações de <?= htmlspecialchars($proprietario['primeiro_nome']) ?> <?= htmlspecialchars($proprietario['segundo_nome']) ?> - DriveNow</title>
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
                <h2 class="text-3xl font-bold text-white">Perfil de Proprietário</h2>
                <p class="text-white/70 mt-2"><?= htmlspecialchars($proprietario['primeiro_nome']) ?> <?= htmlspecialchars($proprietario['segundo_nome']) ?></p>
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
            <!-- Coluna 1: Informações do proprietário -->
            <div class="lg:col-span-1">
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6 card-shine space-y-6">
                    <!-- Detalhes do proprietário -->
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-indigo-400/30 mx-auto mb-4">
                            <?php if (isset($proprietario['foto_perfil']) && !empty($proprietario['foto_perfil'])): ?>
                                <img src="<?= htmlspecialchars($proprietario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                            <?php else: ?>
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($proprietario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                            <?php endif; ?>
                        </div>
                        <h3 class="text-2xl font-bold"><?= htmlspecialchars($proprietario['primeiro_nome']) ?> <?= htmlspecialchars($proprietario['segundo_nome']) ?></h3>
                        <p class="text-white/70">Proprietário DriveNow</p>
                        <p class="text-white/50 text-sm">Membro desde: <?= isset($proprietario['data_de_entrada']) && $proprietario['data_de_entrada'] ? date('d/m/Y', strtotime($proprietario['data_de_entrada'])) : 'Data não disponível' ?></p>
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
                    
                    <!-- Veículos do proprietário -->
                    <div class="border-t border-white/10 pt-6">
                        <h4 class="text-lg font-semibold mb-3">Veículos Disponíveis</h4>
                        
                        <?php if (empty($veiculos)): ?>
                            <p class="text-white/50 text-center py-4">Nenhum veículo disponível no momento</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($veiculos as $veiculo): ?>
                                    <div class="bg-white/5 border subtle-border rounded-xl p-3 flex justify-between items-center hover:bg-white/10 transition-colors">
                                        <div>
                                            <p class="font-medium"><?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?></p>
                                            <p class="text-sm text-white/50"><?= htmlspecialchars($veiculo['veiculo_ano']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <?php if (isset($veiculo['media_avaliacao'])): ?>
                                                <div class="stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?= $i <= round($veiculo['media_avaliacao']) ? 'filled' : '' ?>" style="font-size: 0.875rem;">★</span>
                                                    <?php endfor; ?>
                                                </div>
                                                <div class="text-xs text-white/50">(<?= $veiculo['total_avaliacoes'] ?? 0 ?>)</div>
                                            <?php else: ?>
                                                <p class="text-xs text-white/50">Sem avaliações</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <a href="../reserva/listagem_veiculos.php" class="text-indigo-300 hover:text-indigo-200 transition-colors inline-flex items-center">
                                    Ver todos os veículos
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1 h-4 w-4">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Coluna 2-3: Avaliações -->
            <div class="lg:col-span-2">
                <!-- Avaliações do proprietário -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6">
                    <h3 class="text-xl font-semibold mb-4">Avaliações do Proprietário</h3>
                    
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
                            <p class="text-white/70 mb-6">Este proprietário ainda não recebeu avaliações.</p>
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
                                    
                                    <div class="mt-3 text-sm">
                                        <a href="avaliacoes_veiculo.php?veiculo=<?= $avaliacao['veiculo_id'] ?>" class="text-indigo-300 hover:text-indigo-200 transition-colors">
                                            <?= htmlspecialchars($avaliacao['veiculo_marca']) ?> <?= htmlspecialchars($avaliacao['veiculo_modelo']) ?> <?= htmlspecialchars($avaliacao['veiculo_ano']) ?>
                                        </a>
                                        <span class="text-xs text-white/50 ml-2">
                                            <?= date('d/m/Y', strtotime($avaliacao['reserva_data'])) ?> a <?= date('d/m/Y', strtotime($avaliacao['devolucao_data'])) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
