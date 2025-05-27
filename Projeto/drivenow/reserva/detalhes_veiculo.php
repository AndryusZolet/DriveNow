<!-- Seção de conteúdo do arquivo PHP inicial -->
<?php
require_once '../includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: listagem_veiculos.php');
    exit;
}

$veiculoId = $_GET['id'];

// Buscar detalhes do veículo
global $pdo;
$stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local, cid.cidade_nome, e.sigla,
                      CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario,
                      u.id AS proprietario_id, u.media_avaliacao_proprietario, u.total_avaliacoes_proprietario,
                      v.media_avaliacao, v.total_avaliacoes
                      FROM veiculo v
                      LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                      LEFT JOIN local l ON v.local_id = l.id
                      LEFT JOIN cidade cid ON l.cidade_id = cid.id
                      LEFT JOIN estado e ON cid.estado_id = e.id
                      LEFT JOIN dono d ON v.dono_id = d.id
                      LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
                      WHERE v.id = ?");
$stmt->execute([$veiculoId]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: listagem_veiculos.php');
    exit;
}

// Usar o preço diário do veículo
$diariaValor = $veiculo['preco_diaria'];
$taxaUso = 20.00;
$taxaLimpeza = 30.00;

$erro = '';
$sucesso = '';

// Processar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && estaLogado()) {
    $reservaData = $_POST['reserva_data'];
    $devolucaoData = $_POST['devolucao_data'];
    $observacoes = trim($_POST['observacoes']);
    
    // Validações
    if (empty($reservaData) || empty($devolucaoData)) {
        $erro = 'Datas de reserva e devolução são obrigatórias.';
    } elseif (strtotime($devolucaoData) <= strtotime($reservaData)) {
        $erro = 'A data de devolução deve ser posterior à data de reserva.';
    } else {
        try {
            $usuario = getUsuario();
            
            // Calcular valor total
            $dias = (strtotime($devolucaoData) - strtotime($reservaData)) / (60 * 60 * 24);
            $valorTotal = ($diariaValor * $dias) + $taxaUso + $taxaLimpeza;
            
            $stmt = $pdo->prepare("INSERT INTO reserva 
                                  (veiculo_id, conta_usuario_id, reserva_data, devolucao_data, 
                                   diaria_valor, taxas_de_uso, taxas_de_limpeza, valor_total, observacoes)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $veiculoId,
                $usuario['id'],
                $reservaData,
                $devolucaoData,
                $diariaValor,
                $taxaUso,
                $taxaLimpeza,
                $valorTotal,
                $observacoes
            ]);
            
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Reserva realizada com sucesso! Prossiga para o pagamento.'
            ];
            
            // Redirecionar para a página de pagamento
            header("Location: ../pagamento/realizar_pagamento.php?reserva=" . $pdo->lastInsertId());
            exit;
            
        } catch (PDOException $e) {
            $erro = 'Erro ao processar reserva: ' . $e->getMessage();
        }
    }
}

// Buscar imagens do veículo
$stmt = $pdo->prepare("SELECT * FROM imagem WHERE veiculo_id = ? ORDER BY imagem_ordem");
$stmt->execute([$veiculoId]);
$imagens = $stmt->fetchAll();

$usuario = getUsuario();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?> - DriveNow</title>
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
        
        /* Estrelas de avaliação */
        .text-yellow-300 {
            color: #fcd34d;
            font-size: 14px;
            letter-spacing: -1px;
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
                        <a href="listagem_veiculos.php" class="text-white/80 hover:text-white transition-colors">Veículos</a>
                        <a href="pesquisa_avancada.php" class="text-white/80 hover:text-white transition-colors">Pesquisa Avançada</a>
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
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?></h1>
            <a href="listagem_veiculos.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Voltar
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Coluna 1: Fotos e descrição -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Carrossel de imagens -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl overflow-hidden shadow-lg">
                    <div class="relative h-80 md:h-96 w-full bg-gray-200">
                        <?php if (!empty($imagens)): ?>
                            <img src="<?= htmlspecialchars($imagens[0]['imagem_url']) ?>" alt="<?= htmlspecialchars($veiculo['veiculo_modelo']) ?>" class="w-full h-full object-cover" id="main-image">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-indigo-900/50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-16 w-16 text-white">
                                    <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                    <path d="M7 17h10"/>
                                    <circle cx="7" cy="17" r="2"/>
                                    <path d="M17 17h2"/>
                                    <circle cx="17" cy="17" r="2"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <div class="absolute bottom-5 right-5 bg-indigo-500 text-white px-4 py-2 rounded-xl font-bold text-lg border border-indigo-400/30">
                            R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?>/dia
                        </div>
                    </div>

                    <!-- Miniaturas das imagens -->
                    <?php if (count($imagens) > 1): ?>
                    <div class="flex p-4 gap-2 overflow-x-auto">
                        <?php foreach($imagens as $index => $imagem): ?>
                            <img src="<?= htmlspecialchars($imagem['imagem_url']) ?>" 
                                alt="<?= htmlspecialchars($veiculo['veiculo_modelo']) ?>" 
                                class="h-20 w-20 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity <?= $index === 0 ? 'ring-2 ring-indigo-500' : '' ?>" 
                                onclick="document.getElementById('main-image').src='<?= htmlspecialchars($imagem['imagem_url']) ?>';
                                document.querySelectorAll('.thumbnail').forEach(el => el.classList.remove('ring-2', 'ring-indigo-500'));
                                this.classList.add('ring-2', 'ring-indigo-500');"
                                data-index="<?= $index ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Informações do veículo -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold mb-4">Informações do Veículo</h2>
                    
                    <!-- Avaliações do veículo e proprietário -->
                    <div class="flex gap-6 mb-4 pb-4 border-b subtle-border">
                        <div>
                            <div class="text-white/70 text-sm mb-1">Avaliação do Veículo</div>
                            <div class="flex items-center">
                                <div class="text-yellow-300">
                                    <?php
                                    $mediaVeiculo = $veiculo['media_avaliacao'] ?? 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $mediaVeiculo) {
                                            echo '★';
                                        } else if ($i - $mediaVeiculo < 1 && $i - $mediaVeiculo > 0) {
                                            echo '★'; // Idealmente seria uma estrela meio preenchida
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-sm text-white/70 ml-1">(<?= $veiculo['total_avaliacoes'] ?? 0 ?> avaliações)</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-white/70 text-sm mb-1">Avaliação do Proprietário</div>
                            <div class="flex items-center">
                                <div class="text-yellow-300">
                                    <?php
                                    $mediaProprietario = $veiculo['media_avaliacao_proprietario'] ?? 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $mediaProprietario) {
                                            echo '★';
                                        } else if ($i - $mediaProprietario < 1 && $i - $mediaProprietario > 0) {
                                            echo '★'; // Idealmente seria uma estrela meio preenchida
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="text-sm text-white/70 ml-1">(<?= $veiculo['total_avaliacoes_proprietario'] ?? 0 ?> avaliações)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Descrição -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-white/90 mb-2">Descrição</h3>
                        <div class="bg-white/5 rounded-xl p-4 border subtle-border">
                            <?php if (!empty($veiculo['descricao'])): ?>
                                <?= nl2br(htmlspecialchars($veiculo['descricao'])) ?>
                            <?php else: ?>
                                <p class="text-white/50 italic">O proprietário não forneceu uma descrição para este veículo.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Características do veículo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-white/70">Marca:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_marca']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Modelo:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_modelo']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Ano:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_ano']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Placa:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_placa']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Quilometragem:</span>
                                <span class="text-white font-medium"><?= number_format($veiculo['veiculo_km'], 0, ',', '.') ?> km</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Categoria:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['categoria'] ?? 'Não informada') ?></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-white/70">Câmbio:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_cambio']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Combustível:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_combustivel']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Portas:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_portas']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Assentos:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_acentos']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Tração:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($veiculo['veiculo_tracao'] ?? 'Não informada') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-white/70">Localização:</span>
                                <span class="text-white font-medium">
                                    <?= htmlspecialchars($veiculo['nome_local'] ?? 'Não informada') ?>
                                    <?php if (isset($veiculo['cidade_nome'])): ?>
                                        (<?= htmlspecialchars($veiculo['cidade_nome']) ?>-<?= htmlspecialchars($veiculo['sigla']) ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Proprietário -->
                    <div class="mt-6 pt-4 border-t subtle-border">
                        <h3 class="font-semibold text-white/90 mb-2">Proprietário</h3>
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center mr-3 overflow-hidden">
                                <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($veiculo['nome_proprietario']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Proprietário" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <div class="font-medium"><?= htmlspecialchars($veiculo['nome_proprietario']) ?></div>
                                <a href="../avaliacao/avaliacoes_proprietario.php?id=<?= $veiculo['proprietario_id'] ?>" class="text-sm text-indigo-300 hover:text-indigo-200 transition-colors">Ver perfil e avaliações</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna 2: Formulário de reserva -->
            <div class="space-y-6">
                <!-- Reserva -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold mb-4">Solicitar Reserva</h2>

                    <?php if ($erro): ?>
                        <div class="bg-red-500/20 border border-red-400/30 text-white p-4 rounded-xl mb-4">
                            <?= htmlspecialchars($erro) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                        <div class="bg-green-500/20 border border-green-400/30 text-white p-4 rounded-xl mb-4">
                            <?= htmlspecialchars($sucesso) ?>
                        </div>
                        <a href="../vboard.php" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                            Voltar ao Dashboard
                        </a>
                    <?php else: ?>
                        <?php if (estaLogado()): ?>
                            <?php if (usuarioPodeReservar()): ?>
                                <form method="POST" class="space-y-4">
                                    <div>
                                        <label for="reserva_data" class="block text-white/90 font-medium mb-1">Data de Reserva</label>
                                        <input type="date" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white" id="reserva_data" name="reserva_data" required>
                                    </div>
                                    <div>
                                        <label for="devolucao_data" class="block text-white/90 font-medium mb-1">Data de Devolução</label>
                                        <input type="date" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white" id="devolucao_data" name="devolucao_data" required>
                                    </div>
                                    <div>
                                        <label for="observacoes" class="block text-white/90 font-medium mb-1">Observações</label>
                                        <textarea class="w-full bg-white/5 border subtle-border rounded-xl px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white resize-none" id="observacoes" name="observacoes" rows="3"></textarea>
                                    </div>
                                    
                                    <!-- Cálculo de preço -->
                                    <div id="price-calculation" class="bg-indigo-500/10 border border-indigo-400/20 rounded-xl p-4 mt-4 hidden">
                                        <div class="text-lg font-medium mb-2">Estimativa de Custo</div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-white/70">Diárias (<span id="days-count">0</span> dias):</span>
                                            <span class="text-white" id="daily-cost">R$ 0,00</span>
                                        </div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-white/70">Taxa de uso:</span>
                                            <span class="text-white">R$ <?= number_format($taxaUso, 2, ',', '.') ?></span>
                                        </div>
                                        <div class="flex justify-between items-center mb-1 pb-2 border-b border-indigo-400/20">
                                            <span class="text-white/70">Taxa de limpeza:</span>
                                            <span class="text-white">R$ <?= number_format($taxaLimpeza, 2, ',', '.') ?></span>
                                        </div>
                                        <div class="flex justify-between items-center mt-2 font-bold">
                                            <span>Total estimado:</span>
                                            <span id="total-cost">R$ 0,00</span>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-3 shadow-md hover:shadow-lg">
                                        Solicitar Reserva
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="bg-amber-500/20 border border-amber-400/30 text-white p-4 rounded-xl">
                                    <p class="mb-2">Você não pode fazer reservas neste momento.</p>
                                    <p class="text-white/80 text-sm">Para reservar veículos, complete seu cadastro e aguarde a aprovação da sua CNH.</p>
                                </div>
                                <a href="../perfil/editar.php" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 mt-4 shadow-md hover:shadow-lg flex items-center justify-center">
                                    Completar Cadastro
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-amber-500/20 border border-amber-400/30 text-white p-4 rounded-xl">
                                <p class="mb-2">Você precisa estar logado para fazer uma reserva.</p>
                            </div>
                            <div class="flex gap-3 mt-4">
                                <a href="../login.php" class="flex-1 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                                    Fazer Login
                                </a>
                                <a href="../cadastro.php" class="flex-1 border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                                    Cadastrar
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Informações adicionais -->
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold mb-4">Informações Adicionais</h2>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-indigo-300">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                            <div>
                                <div class="font-medium">Seguro Incluso</div>
                                <div class="text-sm text-white/70">Todos os aluguéis incluem seguro básico</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-indigo-300">
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <path d="M12 18v-6"/>
                                <path d="M8 15h8"/>
                            </svg>
                            <div>
                                <div class="font-medium">Contrato Digital</div>
                                <div class="text-sm text-white/70">Todas as reservas incluem contrato digital</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-indigo-300">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                            <div>
                                <div class="font-medium">Suporte 24/7</div>
                                <div class="text-sm text-white/70">Assistência durante toda a reserva</div>
                            </div>
                        </div>
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
            // Inicializar o sistema de notificações
            try {
                initializeNotifications();
                
                <?php if (isset($_SESSION['notification'])): ?>
                    notify({
                        type: '<?= $_SESSION['notification']['type'] ?>',
                        message: '<?= addslashes($_SESSION['notification']['message']) ?>'
                    });
                    <?php unset($_SESSION['notification']); ?>
                <?php endif; ?>
            } catch (e) {
                console.error("Erro ao inicializar notificações:", e);
            }
            
            // Configurar datas mínimas no formulário
            const today = new Date().toISOString().split('T')[0];
            const reservaDataEl = document.getElementById('reserva_data');
            const devolucaoDataEl = document.getElementById('devolucao_data');
            
            if (reservaDataEl) {
                reservaDataEl.min = today;
                
                reservaDataEl.addEventListener('change', function() {
                    if (devolucaoDataEl) {
                        devolucaoDataEl.min = this.value;
                    }
                    updatePriceCalculation();
                });
            }
            
            if (devolucaoDataEl) {
                devolucaoDataEl.addEventListener('change', updatePriceCalculation);
            }
            
            // Função para calcular e atualizar o preço
            function updatePriceCalculation() {
                if (!reservaDataEl || !devolucaoDataEl || !reservaDataEl.value || !devolucaoDataEl.value) return;
                
                const start = new Date(reservaDataEl.value);
                const end = new Date(devolucaoDataEl.value);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays <= 0) return;
                
                const dailyPrice = <?= $diariaValor ?>;
                const useRate = <?= $taxaUso ?>;
                const cleaningRate = <?= $taxaLimpeza ?>;
                
                const dailyCost = dailyPrice * diffDays;
                const totalCost = dailyCost + useRate + cleaningRate;
                
                document.getElementById('days-count').textContent = diffDays;
                document.getElementById('daily-cost').textContent = 'R$ ' + dailyCost.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('total-cost').textContent = 'R$ ' + totalCost.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                document.getElementById('price-calculation').classList.remove('hidden');
            }
        });
    </script>
</body>
</html>