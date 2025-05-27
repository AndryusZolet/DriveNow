<?php
require_once '../includes/auth.php';

// Verificar autenticação do usuário
verificarAutenticacao();

if (!usuarioPodeReservar()) {
    header('Location: ../perfil/editar.php');
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Complete seu cadastro e aguarde a aprovação da CNH.'
    ];
    exit();
}

$usuario = getUsuario();
global $pdo;

// Categorias de veículos para o filtro
$stmt = $pdo->query("SELECT id, categoria FROM categoria_veiculo ORDER BY categoria");
$categorias = $stmt->fetchAll();

// Marcas de veículos para o filtro
$stmt = $pdo->query("SELECT DISTINCT veiculo_marca FROM veiculo ORDER BY veiculo_marca");
$marcas = $stmt->fetchAll();

// Cidades e locais disponíveis
$stmt = $pdo->query("SELECT c.id AS cidade_id, c.cidade_nome, e.sigla FROM cidade c JOIN estado e ON c.estado_id = e.id ORDER BY c.cidade_nome");
$cidades = $stmt->fetchAll();

// Tipos de câmbio disponíveis
$stmt = $pdo->query("SELECT DISTINCT veiculo_cambio FROM veiculo WHERE veiculo_cambio IS NOT NULL ORDER BY veiculo_cambio");
$cambios = $stmt->fetchAll();

// Tipos de combustível disponíveis
$stmt = $pdo->query("SELECT DISTINCT veiculo_combustivel FROM veiculo WHERE veiculo_combustivel IS NOT NULL ORDER BY veiculo_combustivel");
$combustiveis = $stmt->fetchAll();

// Processar a pesquisa
$filtros = [];
$parametros = [];
$sqlBase = "
    SELECT v.*, l.nome_local, c.cidade_nome, e.sigla, cv.categoria, 
           CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario,
           u.media_avaliacao_proprietario, u.total_avaliacoes_proprietario
    FROM veiculo v 
    LEFT JOIN dono d ON v.dono_id = d.id
    LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
    LEFT JOIN local l ON v.local_id = l.id
    LEFT JOIN cidade c ON l.cidade_id = c.id
    LEFT JOIN estado e ON c.estado_id = e.id
    LEFT JOIN categoria_veiculo cv ON v.categoria_veiculo_id = cv.id
    WHERE v.disponivel = 1
    AND v.id NOT IN (
        SELECT veiculo_id FROM reserva 
        WHERE status != 'rejeitada' AND status != 'cancelada' AND status != 'finalizada'
        AND ((CURRENT_DATE() BETWEEN reserva_data AND devolucao_data) 
            OR (reserva_data > CURRENT_DATE()))
    )
";

// Aplicar os filtros de pesquisa se existirem
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
    
    // Filtro por categoria
    if (!empty($_GET['categoria']) && $_GET['categoria'] != 'todos') {
        $filtros[] = "v.categoria_veiculo_id = ?";
        $parametros[] = $_GET['categoria'];
    }
    
    // Filtro por marca
    if (!empty($_GET['marca']) && $_GET['marca'] != 'todos') {
        $filtros[] = "v.veiculo_marca = ?";
        $parametros[] = $_GET['marca'];
    }
    
    // Filtro por modelo
    if (!empty($_GET['modelo'])) {
        $filtros[] = "v.veiculo_modelo LIKE ?";
        $parametros[] = '%' . $_GET['modelo'] . '%';
    }
    
    // Filtro por ano mínimo
    if (!empty($_GET['ano_min'])) {
        $filtros[] = "v.veiculo_ano >= ?";
        $parametros[] = $_GET['ano_min'];
    }
    
    // Filtro por ano máximo
    if (!empty($_GET['ano_max'])) {
        $filtros[] = "v.veiculo_ano <= ?";
        $parametros[] = $_GET['ano_max'];
    }
    
    // Filtro por preço mínimo
    if (!empty($_GET['preco_min'])) {
        $filtros[] = "v.preco_diaria >= ?";
        $parametros[] = $_GET['preco_min'];
    }
    
    // Filtro por preço máximo
    if (!empty($_GET['preco_max'])) {
        $filtros[] = "v.preco_diaria <= ?";
        $parametros[] = $_GET['preco_max'];
    }
    
    // Filtro por cidade
    if (!empty($_GET['cidade']) && $_GET['cidade'] != 'todos') {
        $filtros[] = "c.id = ?";
        $parametros[] = $_GET['cidade'];
    }
    
    // Filtro por câmbio
    if (!empty($_GET['cambio']) && $_GET['cambio'] != 'todos') {
        $filtros[] = "v.veiculo_cambio = ?";
        $parametros[] = $_GET['cambio'];
    }
    
    // Filtro por combustível
    if (!empty($_GET['combustivel']) && $_GET['combustivel'] != 'todos') {
        $filtros[] = "v.veiculo_combustivel = ?";
        $parametros[] = $_GET['combustivel'];
    }
    
    // Filtro por número de portas
    if (!empty($_GET['portas']) && $_GET['portas'] > 0) {
        $filtros[] = "v.veiculo_portas >= ?";
        $parametros[] = $_GET['portas'];
    }
    
    // Filtro por número de assentos
    if (!empty($_GET['assentos']) && $_GET['assentos'] > 0) {
        $filtros[] = "v.veiculo_acentos >= ?";
        $parametros[] = $_GET['assentos'];
    }
}

// Adicionar os filtros à consulta SQL
if (!empty($filtros)) {
    $sqlBase .= " AND " . implode(" AND ", $filtros);
}

// Executar a consulta SQL
$stmt = $pdo->prepare($sqlBase);
$stmt->execute($parametros);
$veiculos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesquisa Avançada - DriveNow</title>
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
            <h1 class="text-3xl font-bold">Pesquisa Avançada</h1>
            <a href="../vboard.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
                Voltar
            </a>
        </div>
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-4">Pesquisa Avançada de Veículos</h1>
            <p class="text-white/70">Encontre o veículo perfeito para suas necessidades usando os filtros abaixo.</p>
        </div>
        
        <!-- Filtros de pesquisa -->
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg mb-8">
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <!-- Filtro de Categoria -->
                    <div class="space-y-2">
                        <label for="categoria" class="block text-white/90 font-medium">Categoria</label>
                        <select id="categoria" name="categoria" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="todos">Todas as categorias</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= (isset($_GET['categoria']) && $_GET['categoria'] == $categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro de Marca -->
                    <div class="space-y-2">
                        <label for="marca" class="block text-white/90 font-medium">Marca</label>
                        <select id="marca" name="marca" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="todos">Todas as marcas</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= htmlspecialchars($marca['veiculo_marca']) ?>" <?= (isset($_GET['marca']) && $_GET['marca'] == $marca['veiculo_marca']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($marca['veiculo_marca']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro de Modelo -->
                    <div class="space-y-2">
                        <label for="modelo" class="block text-white/90 font-medium">Modelo</label>
                        <input type="text" id="modelo" name="modelo" 
                                value="<?= isset($_GET['modelo']) ? htmlspecialchars($_GET['modelo']) : '' ?>"
                                placeholder="Ex.: Onix, Gol, Civic..." 
                                class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                    </div>
                    
                    <!-- Filtro de Cidade -->
                    <div class="space-y-2">
                        <label for="cidade" class="block text-white/90 font-medium">Cidade</label>
                        <select id="cidade" name="cidade" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="todos">Todas as cidades</option>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?= $cidade['cidade_id'] ?>" <?= (isset($_GET['cidade']) && $_GET['cidade'] == $cidade['cidade_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cidade['cidade_nome']) ?> (<?= htmlspecialchars($cidade['sigla']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro por Ano -->
                    <div class="space-y-2">
                        <label for="ano_min" class="block text-white/90 font-medium">Ano (Mínimo - Máximo)</label>
                        <div class="flex gap-2">
                            <input type="number" id="ano_min" name="ano_min" min="1990" max="<?= date('Y') + 1 ?>" 
                                value="<?= isset($_GET['ano_min']) ? htmlspecialchars($_GET['ano_min']) : '' ?>"
                                placeholder="Min" 
                                class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <input type="number" id="ano_max" name="ano_max" min="1990" max="<?= date('Y') + 1 ?>" 
                                value="<?= isset($_GET['ano_max']) ? htmlspecialchars($_GET['ano_max']) : '' ?>"
                                placeholder="Max" 
                                class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                        </div>
                    </div>
                    
                    <!-- Filtro por Preço -->
                    <div class="space-y-2">
                        <label for="preco_min" class="block text-white/90 font-medium">Preço Diária (R$ Min - Max)</label>
                        <div class="flex gap-2">
                            <input type="number" id="preco_min" name="preco_min" min="0" step="10" 
                                value="<?= isset($_GET['preco_min']) ? htmlspecialchars($_GET['preco_min']) : '' ?>"
                                placeholder="Min" 
                                class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <input type="number" id="preco_max" name="preco_max" min="0" step="10" 
                                value="<?= isset($_GET['preco_max']) ? htmlspecialchars($_GET['preco_max']) : '' ?>"
                                placeholder="Max" 
                                class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                        </div>
                    </div>
                    
                    <!-- Filtro de Câmbio -->
                    <div class="space-y-2">
                        <label for="cambio" class="block text-white/90 font-medium">Tipo de Câmbio</label>
                        <select id="cambio" name="cambio" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="todos">Todos os tipos</option>
                            <?php foreach ($cambios as $cambio): ?>
                                <option value="<?= htmlspecialchars($cambio['veiculo_cambio']) ?>" <?= (isset($_GET['cambio']) && $_GET['cambio'] == $cambio['veiculo_cambio']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cambio['veiculo_cambio']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro de Combustível -->
                    <div class="space-y-2">
                        <label for="combustivel" class="block text-white/90 font-medium">Combustível</label>
                        <select id="combustivel" name="combustivel" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="todos">Todos os tipos</option>
                            <?php foreach ($combustiveis as $combustivel): ?>
                                <option value="<?= htmlspecialchars($combustivel['veiculo_combustivel']) ?>" <?= (isset($_GET['combustivel']) && $_GET['combustivel'] == $combustivel['veiculo_combustivel']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($combustivel['veiculo_combustivel']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro por Número de Portas -->
                    <div class="space-y-2">
                        <label for="portas" class="block text-white/90 font-medium">Número de Portas (Mínimo)</label>
                        <select id="portas" name="portas" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="0">Qualquer</option>
                            <option value="2" <?= (isset($_GET['portas']) && $_GET['portas'] == '2') ? 'selected' : '' ?>>2 ou mais</option>
                            <option value="3" <?= (isset($_GET['portas']) && $_GET['portas'] == '3') ? 'selected' : '' ?>>3 ou mais</option>
                            <option value="4" <?= (isset($_GET['portas']) && $_GET['portas'] == '4') ? 'selected' : '' ?>>4 ou mais</option>
                            <option value="5" <?= (isset($_GET['portas']) && $_GET['portas'] == '5') ? 'selected' : '' ?>>5 ou mais</option>
                        </select>
                    </div>
                    
                    <!-- Filtro por Número de Assentos -->
                    <div class="space-y-2">
                        <label for="assentos" class="block text-white/90 font-medium">Número de Assentos (Mínimo)</label>
                        <select id="assentos" name="assentos" class="w-full bg-white/5 border subtle-border rounded-xl h-10 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white">
                            <option value="0">Qualquer</option>
                            <option value="2" <?= (isset($_GET['assentos']) && $_GET['assentos'] == '2') ? 'selected' : '' ?>>2 ou mais</option>
                            <option value="4" <?= (isset($_GET['assentos']) && $_GET['assentos'] == '4') ? 'selected' : '' ?>>4 ou mais</option>
                            <option value="5" <?= (isset($_GET['assentos']) && $_GET['assentos'] == '5') ? 'selected' : '' ?>>5 ou mais</option>
                            <option value="7" <?= (isset($_GET['assentos']) && $_GET['assentos'] == '7') ? 'selected' : '' ?>>7 ou mais</option>
                        </select>
                    </div>
                </div>
                
                <!-- Botões de ação -->
                <div class="flex flex-wrap gap-4 mt-6">
                    <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-6 py-2 shadow-md hover:shadow-lg">
                        Pesquisar
                    </button>
                    <a href="pesquisa_avancada.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-6 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg">
                        Limpar Filtros
                    </a>
                    <!-- <a href="listagem_veiculos.php" class="text-white/70 hover:text-white transition-colors px-4 py-2">
                        Voltar para Listagem
                    </a> -->
                </div>
            </form>
        </div>
        
        <!-- Listagem de veículos -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold"><?= count($veiculos) ?> veículos encontrados</h2>
            </div>
            
            <?php if (empty($veiculos)): ?>
                <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg text-center">
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="p-3 rounded-2xl bg-indigo-500/30 text-white border border-indigo-400/30 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                <path d="M7 17h10"/>
                                <circle cx="7" cy="17" r="2"/>
                                <path d="M17 17h2"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Nenhum veículo encontrado</h3>
                        <p class="text-white/70 max-w-lg">Não encontramos veículos disponíveis com os filtros selecionados. Tente ajustar os critérios de pesquisa ou verificar mais tarde.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($veiculos as $veiculo): ?>
                        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 flex flex-col">
                            <div class="relative mb-4 rounded-2xl bg-gray-200 overflow-hidden h-44">
                                <?php
                                // Verificar se o veículo tem imagens
                                $stmt = $pdo->prepare("SELECT imagem_url FROM imagem WHERE veiculo_id = ? ORDER BY imagem_ordem LIMIT 1");
                                $stmt->execute([$veiculo['id']]);
                                $imagem = $stmt->fetch();
                                
                                if ($imagem): ?>
                                    <img src="<?= htmlspecialchars($imagem['imagem_url']) ?>" alt="<?= htmlspecialchars($veiculo['veiculo_modelo']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-indigo-900/50">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 text-white">
                                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                            <path d="M7 17h10"/>
                                            <circle cx="7" cy="17" r="2"/>
                                            <path d="M17 17h2"/>
                                            <circle cx="17" cy="17" r="2"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute bottom-3 right-3 bg-indigo-500 text-white px-3 py-1 rounded-xl font-bold text-sm border border-indigo-400/30">
                                    R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?>/dia
                                </div>
                            </div>
                            
                            <div class="flex-1">
                                <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?></h3>
                                
                                <!-- Avaliações do veículo e proprietário -->
                                <div class="flex gap-3 mb-2">
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
                                        <span class="text-xs text-white/70 ml-1">(<?= $veiculo['total_avaliacoes'] ?? 0 ?>)</span>
                                    </div>
                                    <div class="text-white/50 text-xs">|</div>
                                    <div class="flex items-center">
                                        <span class="text-xs text-white/70">Proprietário: </span>
                                        <div class="text-yellow-300 ml-1">
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
                                        <span class="text-xs text-white/70 ml-1">(<?= $veiculo['total_avaliacoes_proprietario'] ?? 0 ?>)</span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-4 text-sm">
                                    <div class="text-white/70">Ano:</div>
                                    <div class="text-white"><?= htmlspecialchars($veiculo['veiculo_ano']) ?></div>
                                    
                                    <div class="text-white/70">Categoria:</div>
                                    <div class="text-white"><?= htmlspecialchars($veiculo['categoria'] ?? 'N/D') ?></div>
                                    
                                    <div class="text-white/70">Câmbio:</div>
                                    <div class="text-white"><?= htmlspecialchars($veiculo['veiculo_cambio'] ?? 'N/D') ?></div>
                                    
                                    <div class="text-white/70">Combustível:</div>
                                    <div class="text-white"><?= htmlspecialchars($veiculo['veiculo_combustivel'] ?? 'N/D') ?></div>
                                    
                                    <div class="text-white/70">Local:</div>
                                    <div class="text-white">
                                        <?= htmlspecialchars($veiculo['nome_local'] ?? 'N/D') ?>
                                        <?php if (isset($veiculo['cidade_nome'])): ?>
                                            <span class="text-white/60 text-xs">(<?= htmlspecialchars($veiculo['cidade_nome']) ?>-<?= htmlspecialchars($veiculo['sigla']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <a href="detalhes_veiculo.php?id=<?= $veiculo['id'] ?>" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center justify-center">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="mt-12 mb-6 px-4 text-center text-white/50 text-sm">
        <p>&copy; <?= date('Y') ?> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Script para integração com o sistema de notificações existente, se necessário
        document.addEventListener('DOMContentLoaded', function() {
            // Você pode adicionar comportamentos adicionais aqui
        });
    </script>
</body>
</html>
