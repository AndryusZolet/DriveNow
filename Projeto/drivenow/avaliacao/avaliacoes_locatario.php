<?php
require_once '../includes/auth.php';

// Verificar se o ID do locatário foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Locatário não especificado.'
    ];
    header('Location: ../index.php');
    exit;
}

$locatarioId = (int)$_GET['id'];
global $pdo;

// Buscar detalhes do locatário
$stmt = $pdo->prepare("
    SELECT u.*
    FROM conta_usuario u
    WHERE u.id = ?
");
$stmt->execute([$locatarioId]);
$locatario = $stmt->fetch();

if (!$locatario) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Locatário não encontrado.'
    ];
    header('Location: ../index.php');
    exit;
}

// Buscar avaliações do locatário
$stmt = $pdo->prepare("
    SELECT al.*, r.reserva_data, r.devolucao_data, r.veiculo_id,
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario,
           v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano
    FROM avaliacao_locatario al
    INNER JOIN reserva r ON al.reserva_id = r.id
    INNER JOIN conta_usuario u ON al.proprietario_id = u.id
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    WHERE al.locatario_id = ?
    ORDER BY al.data_avaliacao DESC
");
$stmt->execute([$locatarioId]);
$avaliacoes = $stmt->fetchAll();

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

// Verificar se o usuário logado é o próprio locatário ou um administrador
$perfilPessoal = $estaLogado && ($usuario['id'] == $locatarioId || $usuario['is_admin'] == 1);

// Verificar se o usuário logado pode ver as avaliações do locatário
// (deve ser admin, o próprio locatário, ou um proprietário que já avaliou o locatário)
$podeVerAvaliacoes = false;
if ($estaLogado) {
    if ($usuario['id'] == $locatarioId || $usuario['is_admin'] == 1) {
        $podeVerAvaliacoes = true;
    } else {
        // Verificar se é um proprietário que já avaliou este locatário
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM avaliacao_locatario 
            WHERE proprietario_id = ? AND locatario_id = ?
        ");
        $stmt->execute([$usuario['id'], $locatarioId]);
        $podeVerAvaliacoes = ($stmt->fetchColumn() > 0);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?= htmlspecialchars($locatario['primeiro_nome']) ?> <?= htmlspecialchars($locatario['segundo_nome']) ?> - DriveNow</title>
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
                <h2 class="text-3xl font-bold text-white">Perfil de Locatário</h2>
                <p class="text-white/70 mt-2"><?= htmlspecialchars($locatario['primeiro_nome']) ?> <?= htmlspecialchars($locatario['segundo_nome']) ?></p>
            </div>
            <a href="javascript:history.back()" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <span>Voltar</span>
            </a>
        </div>

        <?php if (!$podeVerAvaliacoes && $totalAvaliacoes > 0): ?>
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 mx-auto mb-4 text-yellow-300">
                    <path d="M11 17h2"></path>
                    <rect x="11" y="7" width="2" height="6"></rect>
                    <path d="M22 12c0 5.5-4.5 10-10 10S2 17.5 2 12 6.5 2 12 2s10 4.5 10 10z"></path>
                </svg>
                <h3 class="text-xl font-bold mb-3">Acesso restrito</h3>
                <p class="text-white/70 mb-6">As informações de avaliação deste locatário só estão disponíveis para proprietários que já interagiram com este usuário, para o próprio usuário ou para administradores.</p>
                
                <?php if (!$estaLogado): ?>
                    <a href="../login.php" class="inline-block bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 font-medium shadow-md hover:shadow-lg">Fazer Login</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Coluna 1: Informações do locatário -->
                <div class="lg:col-span-1">
                    <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6 card-shine space-y-6">
                        <!-- Detalhes do usuário -->
                        <div class="text-center mb-6">
                            <div class="w-24 h-24 rounded-full overflow-hidden border-2 border-indigo-400/30 mx-auto mb-4">
                                <?php if (isset($locatario['foto_perfil']) && !empty($locatario['foto_perfil'])): ?>
                                    <img src="<?= htmlspecialchars($locatario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($locatario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                                <?php endif; ?>
                            </div>
                            <h3 class="text-2xl font-bold"><?= htmlspecialchars($locatario['primeiro_nome']) ?> <?= htmlspecialchars($locatario['segundo_nome']) ?></h3>
                            <p class="text-white/70">Locatário DriveNow</p>
                            <p class="text-white/50 text-sm">Membro desde: <?= isset($locatario['data_de_entrada']) && $locatario['data_de_entrada'] ? date('d/m/Y', strtotime($locatario['data_de_entrada'])) : 'Data não disponível' ?></p>
                        </div>
                        
                        <?php if ($totalAvaliacoes > 0): ?>
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
                        <?php else: ?>
                            <div class="text-center py-6">
                                <div class="p-4 rounded-full bg-white/10 text-white inline-flex mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                        <path d="M17.5 12h.5c.28 0 .5.22.5.5v2a3.5 3.5 0 0 1-7 0v-2c0-.28.22-.5.5-.5h6.5Z"></path>
                                        <path d="M16.5 6a9 9 0 0 0-9 9"></path>
                                        <path d="M14 5a12 12 0 0 0-12 12"></path>
                                        <path d="M18.5 6a9 9 0 0 1 9 9"></path>
                                        <path d="M21 5a12 12 0 0 1 12 12"></path>
                                    </svg>
                                </div>
                                <p class="text-white/70">Sem avaliações disponíveis</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Informações adicionais -->
                        <?php if ($perfilPessoal): ?>
                            <div class="border-t border-white/10 pt-6">
                                <h4 class="text-lg font-semibold mb-3">Informações de Contato</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                                <polyline points="3 7 12 13 21 7"></polyline>
                                            </svg>
                                        </div>
                                        <div class="text-white/70">
                                            <?= htmlspecialchars($locatario['e_mail']) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($locatario['telefone'])): ?>
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-white/70">
                                            <?= htmlspecialchars($locatario['telefone']) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Status da documentação -->
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                                <path d="M8 2v4"></path>
                                                <path d="M16 2v4"></path>
                                                <path d="M2 10h20"></path>
                                            </svg>
                                        </div>
                                        <div class="text-white/70">
                                            <span>Documentos: </span>
                                            <?php if ($locatario['status_docs'] === 'aprovado'): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                                    Aprovado
                                                </span>
                                            <?php elseif ($locatario['status_docs'] === 'rejeitado'): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-300 border border-red-400/30">
                                                    Rejeitado
                                                </span>
                                            <?php elseif ($locatario['status_docs'] === 'verificando'): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-500/20 text-yellow-300 border border-yellow-400/30">
                                                    Em Análise
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-500/20 text-blue-300 border border-blue-400/30">
                                                    Pendente
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Coluna 2-3: Avaliações -->
                <div class="lg:col-span-2">
                    <!-- Avaliações do locatário -->
                    <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6">
                        <h3 class="text-xl font-semibold mb-4">Avaliações do Locatário</h3>
                        
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
                                <p class="text-white/70 mb-6">Este locatário ainda não recebeu avaliações.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6">
                                <?php foreach ($avaliacoes as $avaliacao): ?>
                                    <div class="bg-white/5 border subtle-border rounded-xl p-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full overflow-hidden border border-white/20 mr-3">
                                                    <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($avaliacao['nome_proprietario']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Proprietário" class="h-full w-full object-cover">
                                                </div>
                                                <div>
                                                    <p class="font-medium"><?= htmlspecialchars($avaliacao['nome_proprietario']) ?></p>
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
        <?php endif; ?>
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
