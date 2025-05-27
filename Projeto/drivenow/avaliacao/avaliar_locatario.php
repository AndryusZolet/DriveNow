<?php
require_once '../includes/auth.php';

// Verificar autenticação
if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();
global $pdo;

// Verificar se o ID da reserva foi fornecido
if (!isset($_GET['reserva']) || !is_numeric($_GET['reserva'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não especificada.'
    ];
    header('Location: ../reserva/reservas_recebidas.php');
    exit;
}

$reservaId = (int)$_GET['reserva'];

// Verificar se o usuário é proprietário
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Apenas proprietários podem avaliar locatários.'
    ];
    header('Location: ../vboard.php');
    exit;
}

// Verificar se a reserva existe, está finalizada e é de um veículo do proprietário
$stmt = $pdo->prepare("
    SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano, v.veiculo_placa,
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_locatario,
           u.id AS locatario_id
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN conta_usuario u ON r.conta_usuario_id = u.id
    WHERE r.id = ? AND v.dono_id = ? AND r.status = 'finalizada'
");
$stmt->execute([$reservaId, $dono['id']]);
$reserva = $stmt->fetch();

if (!$reserva) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não encontrada, não finalizada ou você não tem permissão para acessá-la.'
    ];
    header('Location: ../reserva/reservas_recebidas.php');
    exit;
}

// Verificar se esta reserva já foi avaliada
$stmt = $pdo->prepare("SELECT id FROM avaliacao_locatario WHERE reserva_id = ?");
$stmt->execute([$reservaId]);
$avaliacaoExistente = $stmt->fetch();

if ($avaliacaoExistente) {
    $_SESSION['notification'] = [
        'type' => 'info',
        'message' => 'Este locatário já foi avaliado para esta reserva.'
    ];
    header('Location: ../reserva/reservas_recebidas.php');
    exit;
}

// Processar o formulário de avaliação
$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notaLocatario = isset($_POST['nota_locatario']) ? (int)$_POST['nota_locatario'] : 0;
    $comentarioLocatario = isset($_POST['comentario_locatario']) ? trim($_POST['comentario_locatario']) : '';
    
    // Validar notas
    if ($notaLocatario < 1 || $notaLocatario > 5) {
        $erro = 'A nota do locatário deve estar entre 1 e 5.';
    } else {
        try {
            // Iniciar transação
            $pdo->beginTransaction();
            
            // 1. Inserir avaliação do locatário
            $stmt = $pdo->prepare("
                INSERT INTO avaliacao_locatario (reserva_id, proprietario_id, locatario_id, nota, comentario, data_avaliacao)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $reservaId,
                $usuario['id'],
                $reserva['locatario_id'],
                $notaLocatario,
                $comentarioLocatario
            ]);
            
            // 2. Atualizar a média de avaliação do locatário
            $stmt = $pdo->prepare("
                UPDATE conta_usuario
                SET media_avaliacao_locatario = (
                    SELECT AVG(nota) FROM avaliacao_locatario WHERE locatario_id = ?
                ),
                total_avaliacoes_locatario = (
                    SELECT COUNT(*) FROM avaliacao_locatario WHERE locatario_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$reserva['locatario_id'], $reserva['locatario_id'], $reserva['locatario_id']]);
            
            // Confirmar transação
            $pdo->commit();
            
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Avaliação enviada com sucesso. Obrigado pelo seu feedback!'
            ];
            header('Location: ../reserva/reservas_recebidas.php');
            exit;
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            $pdo->rollBack();
            $erro = 'Erro ao processar avaliação: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliar Locatário - DriveNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
        .rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .rating input {
            display: none;
        }
        .rating label {
            cursor: pointer;
            width: 2.5rem;
            height: 2.5rem;
            margin-right: 0.5rem;
            position: relative;
            color: rgba(255, 255, 255, 0.2);
        }
        .rating label::before {
            content: '★';
            position: absolute;
            font-size: 2.5rem;
            opacity: 1;
        }
        .rating input:checked ~ label::before,
        .rating label:hover::before,
        .rating label:hover ~ label::before {
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

    <main class="container mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white">Avaliar Locatário</h2>
                <p class="text-white/70 mt-2">Sua opinião sobre o locatário é importante para nossa comunidade</p>
            </div>
            <a href="../reserva/reservas_recebidas.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <span>Voltar</span>
            </a>
        </div>

        <?php if ($erro): ?>
            <div class="mb-6 bg-red-500/20 border border-red-400/30 text-white p-4 rounded-xl">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensagem): ?>
            <div class="mb-6 bg-green-500/20 border border-green-400/30 text-white p-4 rounded-xl">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Detalhes da reserva -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Detalhes da Reserva</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Info do veículo -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30 flex items-center justify-center mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M19 17h2V7l-8-5-8 5v10h2"></path>
                                    <path d="M2 7l10 4 10-4"></path>
                                    <path d="M12 11v.01"></path>
                                    <path d="M10 17v4h4v-4"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium">Veículo</h4>
                                <p class="text-white/70"><?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?>, <?= htmlspecialchars($reserva['veiculo_ano']) ?></p>
                                <p class="text-white/50 text-sm">Placa: <?= htmlspecialchars($reserva['veiculo_placa']) ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-purple-500/20 text-purple-300 border border-purple-400/30 flex items-center justify-center mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium">Locatário</h4>
                                <p class="text-white/70"><?= htmlspecialchars($reserva['nome_locatario']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Info da reserva -->
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 flex items-center justify-center mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium">Período</h4>
                                <p class="text-white/70"><?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?> a <?= date('d/m/Y', strtotime($reserva['devolucao_data'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-400/30 flex items-center justify-center mr-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M2 17h2v-4h16v4h2"></path>
                                    <path d="M6 7v4"></path>
                                    <path d="M18 7v4"></path>
                                    <rect x="8" y="3" width="8" height="4" rx="1" ry="1"></rect>
                                    <path d="M22 17v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-2"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium">Valor Total</h4>
                                <p class="text-white/70">R$ <?= number_format($reserva['valor_total'], 2, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulário de avaliação -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Avaliar Locatário</h3>
                
                <form method="POST" class="space-y-6">
                    <!-- Avaliação do locatário -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-white/70 mb-2">Nota</label>
                            <div class="rating">
                                <input type="radio" id="star5l" name="nota_locatario" value="5" <?= isset($_POST['nota_locatario']) && $_POST['nota_locatario'] == '5' ? 'checked' : '' ?> />
                                <label for="star5l"></label>
                                <input type="radio" id="star4l" name="nota_locatario" value="4" <?= isset($_POST['nota_locatario']) && $_POST['nota_locatario'] == '4' ? 'checked' : '' ?> />
                                <label for="star4l"></label>
                                <input type="radio" id="star3l" name="nota_locatario" value="3" <?= isset($_POST['nota_locatario']) && $_POST['nota_locatario'] == '3' ? 'checked' : '' ?> />
                                <label for="star3l"></label>
                                <input type="radio" id="star2l" name="nota_locatario" value="2" <?= isset($_POST['nota_locatario']) && $_POST['nota_locatario'] == '2' ? 'checked' : '' ?> />
                                <label for="star2l"></label>
                                <input type="radio" id="star1l" name="nota_locatario" value="1" <?= isset($_POST['nota_locatario']) && $_POST['nota_locatario'] == '1' ? 'checked' : '' ?> />
                                <label for="star1l"></label>
                            </div>
                            <p class="text-xs text-white/50 mt-1">Avalie o locatário de 1 a 5 estrelas</p>
                        </div>
                        
                        <div>
                            <label for="comentario_locatario" class="block text-white/70 mb-2">Comentário sobre o locatário</label>
                            <textarea id="comentario_locatario" name="comentario_locatario" rows="4" class="w-full bg-white/5 border subtle-border rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white"><?= htmlspecialchars($_POST['comentario_locatario'] ?? '') ?></textarea>
                            <p class="text-xs text-white/50 mt-1">Descreva sua experiência com o locatário: cuidado com o veículo, comunicação, pontualidade, etc.</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <p class="text-white/70">Ao avaliar o locatário, você contribui para melhorar a experiência de todos os proprietários da DriveNow.</p>
                        
                        <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white rounded-xl transition-colors border border-green-400/30 px-4 py-3 font-medium shadow-md hover:shadow-lg flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                                <path d="M11 11h-7a2 2 0 0 0-2 2v-4a2 2 0 0 1 2-2h4"></path>
                                <path d="M22 20v-9a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2Z"></path>
                                <path d="m16 14 2 2 4-4"></path>
                            </svg>
                            <span>Enviar Avaliação</span>
                        </button>
                    </div>
                </form>
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
