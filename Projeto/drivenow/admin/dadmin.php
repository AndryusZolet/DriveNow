<?php
require_once '../includes/auth.php';

// Verificar se o usuário está logado e é administrador
verificarAutenticacao();
verificarAdmin();

// Obtém o usuário atual
$usuario = getUsuario();

// Para filtragem de usuários
$filtroStatus = isset($_GET['status']) ? $_GET['status'] : 'pendente';
$filtroPesquisa = isset($_GET['search']) ? trim($_GET['search']) : '';

// Determinar a página atual e o limite de itens por página
$paginaAtual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itensPorPagina = 10;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Consultar usuários com documentos
global $pdo;

try {
    // Construir a query base
    $whereClause = "";
    $params = [];
    
    // Adicionar filtro de status
    if ($filtroStatus) {
        $whereClause .= " AND u.status_docs = ?";
        $params[] = $filtroStatus;
    }
    
    // Adicionar filtro de pesquisa se existir
    if ($filtroPesquisa) {
        $whereClause .= " AND (u.primeiro_nome LIKE ? OR u.segundo_nome LIKE ? OR u.e_mail LIKE ? OR u.cpf LIKE ?)";
        $termoPesquisa = "%{$filtroPesquisa}%";
        $params[] = $termoPesquisa;
        $params[] = $termoPesquisa;
        $params[] = $termoPesquisa;
        $params[] = $termoPesquisa;
    }
    
    // Query para contar total de resultados
    $queryCount = "SELECT COUNT(*) FROM conta_usuario u 
                   WHERE (u.foto_cnh_frente IS NOT NULL OR u.foto_cnh_verso IS NOT NULL)
                   {$whereClause}";
    
    $stmtCount = $pdo->prepare($queryCount);
    $stmtCount->execute($params);
    $totalUsuarios = $stmtCount->fetchColumn();
    
    // Calcular total de páginas
    $totalPaginas = ceil($totalUsuarios / $itensPorPagina);
    
    // Ajustar a página atual se for inválida
    if ($paginaAtual < 1) {
        $paginaAtual = 1;
    } elseif ($paginaAtual > $totalPaginas && $totalPaginas > 0) {
        $paginaAtual = $totalPaginas;
    }
    
    // Query principal para buscar os usuários
    $query = "SELECT u.id, u.primeiro_nome, u.segundo_nome, u.e_mail, u.cpf, 
                     u.foto_cnh_frente, u.foto_cnh_verso, u.status_docs, 
                     u.observacoes_docs, u.data_verificacao, u.admin_verificacao
              FROM conta_usuario u 
              WHERE (u.foto_cnh_frente IS NOT NULL OR u.foto_cnh_verso IS NOT NULL)
              {$whereClause}
              ORDER BY 
                CASE 
                    WHEN u.status_docs = 'pendente' THEN 1
                    WHEN u.status_docs = 'rejeitado' THEN 2
                    WHEN u.status_docs = 'aprovado' THEN 3
                    ELSE 4
                END,
                u.id DESC
              LIMIT {$offset}, {$itensPorPagina}";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar se nenhum usuário foi encontrado
    $nenhumUsuario = count($usuarios) === 0;
    
    // Processar ação de aprovação/rejeição se for POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
        $action = $_POST['action'];
        $userId = (int)$_POST['user_id'];
        $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';
        
        if ($action === 'aprovar' || $action === 'rejeitar') {
            $novoStatus = ($action === 'aprovar') ? 'aprovado' : 'rejeitado';
            
            // Atualizar status dos documentos
            $stmtUpdate = $pdo->prepare("UPDATE conta_usuario SET 
                                          status_docs = ?,
                                          observacoes_docs = ?,
                                          data_verificacao = NOW(),
                                          admin_verificacao = ?
                                          WHERE id = ?");
            
            $stmtUpdate->execute([$novoStatus, $observacoes, $usuario['id'], $userId]);
            
            // Feedback para o usuário
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Documento ' . ($novoStatus === 'aprovado' ? 'aprovado' : 'rejeitado') . ' com sucesso!'
            ];
            
            // Redirecionar para a mesma página para evitar reenvio do formulário
            header("Location: dadmin.php?status={$filtroStatus}&page={$paginaAtual}" . 
                   ($filtroPesquisa ? "&search=" . urlencode($filtroPesquisa) : ""));
            exit;
        }
    }
    
} catch (PDOException $e) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Erro ao carregar documentos: ' . $e->getMessage()
    ];
}

// Função para obter o nome do admin que verificou o documento
function getNomeAdmin($adminId) {
    global $pdo;
    
    if (!$adminId) return 'N/A';
    
    try {
        $stmt = $pdo->prepare("SELECT CONCAT(primeiro_nome, ' ', segundo_nome) as nome_completo 
                               FROM conta_usuario WHERE id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['nome_completo'] : 'Admin #' . $adminId;
    } catch (PDOException $e) {
        return 'Admin #' . $adminId;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Documentos - DriveNow Admin</title>
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

        .document-image {
            max-width: 100%;
            max-height: 300px;
            object-fit: contain;
        }

        /* Modal backdrop */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
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
                    <h1 class="text-xl font-bold text-white mr-8">DriveNow Admin</h1>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../vboard.php" class="text-white/80 hover:text-white transition-colors">Dashboard</a>
                        <a href="dadmin.php" class="text-white/80 hover:text-white transition-colors">Documentos</a>
                        <a href="usuarios.php" class="text-white/80 hover:text-white transition-colors">Usuários</a>
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
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                <h2 class="text-2xl md:text-3xl font-bold text-white">Verificação de Documentos</h2>
                <a href="../vboard.php" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg flex items-center w-full md:w-auto justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    Voltar ao Dashboard
                </a>
            </div>
            
            <!-- Filtros e Barra de Pesquisa -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 mb-8 shadow-lg">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <form action="dadmin.php" method="GET" class="flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <label for="search" class="block text-white font-medium mb-2">Pesquisar</label>
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    placeholder="Nome, email ou CPF..." 
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    value="<?= htmlspecialchars($filtroPesquisa) ?>"
                                >
                            </div>
                            
                            <div class="sm:w-48">
                                <label for="status" class="block text-white font-medium mb-2">Status</label>
                                <select 
                                    id="status" 
                                    name="status" 
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                >
                                    <option value="" <?= $filtroStatus === '' ? 'selected' : '' ?>>Todos</option>
                                    <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>Pendentes</option>
                                    <option value="aprovado" <?= $filtroStatus === 'aprovado' ? 'selected' : '' ?>>Aprovados</option>
                                    <option value="rejeitado" <?= $filtroStatus === 'rejeitado' ? 'selected' : '' ?>>Rejeitados</option>
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" class="w-full px-4 py-3 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 shadow-md hover:shadow-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                    Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista de Documentos -->
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                <?php if ($nenhumUsuario): ?>
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-white/30 mb-4">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <h3 class="text-xl font-medium text-white mb-2">Nenhum documento encontrado</h3>
                        <p class="text-white/70">
                            <?php if ($filtroStatus || $filtroPesquisa): ?>
                                Tente alterar seus filtros de pesquisa.
                            <?php else: ?>
                                Não há documentos para verificação no momento.
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($filtroStatus || $filtroPesquisa): ?>
                            <a href="dadmin.php" class="inline-block mt-4 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 shadow-md hover:shadow-lg">
                                Limpar Filtros
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/70 uppercase tracking-wider">Usuário</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/70 uppercase tracking-wider">CPF</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/70 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/70 uppercase tracking-wider">Verificado por</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-white/70 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-white/70 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 rounded-full overflow-hidden bg-indigo-500/30 flex items-center justify-center">
                                                    <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($user['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-white"><?= htmlspecialchars($user['primeiro_nome'] . ' ' . $user['segundo_nome']) ?></div>
                                                    <div class="text-sm text-white/60"><?= htmlspecialchars($user['e_mail']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">
                                            <?= htmlspecialchars($user['cpf'] ?: 'Não informado') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($user['status_docs'] === 'aprovado'): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                                    Aprovado
                                                </span>
                                            <?php elseif ($user['status_docs'] === 'rejeitado'): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-300 border border-red-400/30">
                                                    Rejeitado
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                                    Pendente
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">
                                            <?= $user['admin_verificacao'] ? htmlspecialchars(getNomeAdmin($user['admin_verificacao'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-white/80">
                                            <?= $user['data_verificacao'] ? date('d/m/Y H:i', strtotime($user['data_verificacao'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                type="button"
                                                onclick="abrirModalDocumentos(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['primeiro_nome'] . ' ' . $user['segundo_nome'])) ?>')"
                                                class="text-indigo-400 hover:text-indigo-300 transition-colors"
                                            >
                                                Verificar Documentos
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($totalPaginas > 1): ?>
                        <div class="mt-6 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php if ($paginaAtual > 1): ?>
                                    <a href="?page=<?= $paginaAtual - 1 ?><?= $filtroStatus ? '&status=' . htmlspecialchars($filtroStatus) : '' ?><?= $filtroPesquisa ? '&search=' . htmlspecialchars(urlencode($filtroPesquisa)) : '' ?>" class="px-3 py-1 rounded-md bg-white/10 text-white/70 hover:bg-white/20 hover:text-white transition-colors">
                                        &laquo; Anterior
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $paginaAtual - 2); $i <= min($totalPaginas, $paginaAtual + 2); $i++): ?>
                                    <?php if ($i == $paginaAtual): ?>
                                        <span class="px-3 py-1 rounded-md bg-indigo-500 text-white">
                                            <?= $i ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?><?= $filtroStatus ? '&status=' . htmlspecialchars($filtroStatus) : '' ?><?= $filtroPesquisa ? '&search=' . htmlspecialchars(urlencode($filtroPesquisa)) : '' ?>" class="px-3 py-1 rounded-md bg-white/10 text-white/70 hover:bg-white/20 hover:text-white transition-colors">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($paginaAtual < $totalPaginas): ?>
                                    <a href="?page=<?= $paginaAtual + 1 ?><?= $filtroStatus ? '&status=' . htmlspecialchars($filtroStatus) : '' ?><?= $filtroPesquisa ? '&search=' . htmlspecialchars(urlencode($filtroPesquisa)) : '' ?>" class="px-3 py-1 rounded-md bg-white/10 text-white/70 hover:bg-white/20 hover:text-white transition-colors">
                                        Próximo &raquo;
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal de Documentos -->
    <div id="modalDocumentos" class="fixed inset-0 z-50 hidden modal-backdrop flex items-center justify-center p-4">
        <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 border subtle-border rounded-3xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-6 flex items-center justify-between border-b border-white/10">
                <h3 class="text-xl font-bold text-white" id="modalTitle">Documentos de Usuário</h3>
                <button type="button" onclick="fecharModalDocumentos()" class="text-white/70 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-150px)]" id="modalContent">
                <div class="flex flex-col gap-8">
                    <!-- Documentos -->
                    <div>
                        <h4 class="text-lg font-medium text-white mb-4">Documentos CNH</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                    <h5 class="text-white/90 font-medium mb-3">Frente da CNH</h5>
                                    <div class="flex items-center justify-center bg-white/5 rounded-lg p-2 min-h-[200px] border border-white/10">
                                        <img id="imgCnhFrente" src="" alt="Frente da CNH" class="document-image hidden">
                                        <div id="noImgCnhFrente" class="text-white/50 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2">
                                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                                <circle cx="9" cy="9" r="2"></circle>
                                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                            </svg>
                                            <p>Imagem não disponível</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                    <h5 class="text-white/90 font-medium mb-3">Verso da CNH</h5>
                                    <div class="flex items-center justify-center bg-white/5 rounded-lg p-2 min-h-[200px] border border-white/10">
                                        <img id="imgCnhVerso" src="" alt="Verso da CNH" class="document-image hidden">
                                        <div id="noImgCnhVerso" class="text-white/50 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2">
                                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                                <circle cx="9" cy="9" r="2"></circle>
                                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                            </svg>
                                            <p>Imagem não disponível</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Formulário de Aprovação/Rejeição -->
                    <div id="formAvaliacaoContainer" class="bg-white/5 rounded-xl p-6 border border-white/10">
                        <h4 class="text-lg font-medium text-white mb-4">Avaliar Documentos</h4>
                        
                        <form id="formAvaliacao" method="POST" class="space-y-4">
                            <input type="hidden" id="user_id" name="user_id" value="">
                            
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                <label for="observacoes" class="block text-white font-medium mb-2">Observações (obrigatório em caso de rejeição)</label>
                                <textarea 
                                    id="observacoes" 
                                    name="observacoes" 
                                    rows="3" 
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    placeholder="Informe o motivo da rejeição ou observações adicionais..."
                                ></textarea>
                            </div>
                            
                            <div class="flex items-center gap-4 pt-2">
                                <button 
                                    type="submit" 
                                    name="action" 
                                    value="aprovar" 
                                    class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-colors border border-emerald-400/30 px-4 py-3 font-medium shadow-md hover:shadow-lg flex items-center justify-center"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    Aprovar Documentos
                                </button>
                                
                                <button 
                                    type="submit" 
                                    name="action" 
                                    value="rejeitar" 
                                    class="flex-1 bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-3 font-medium shadow-md hover:shadow-lg flex items-center justify-center"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    Rejeitar Documentos
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Status Atual -->
                    <div id="statusAtualContainer" class="bg-white/5 rounded-xl p-6 border border-white/10">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-medium text-white">Status Atual</h4>
                            <div id="statusBadge" class="px-3 py-1 rounded-full text-sm font-medium">Pendente</div>
                        </div>
                        
                        <div id="detalhesVerificacao" class="space-y-3">
                            <div class="text-white/80">
                                <span class="text-white/50">Verificado por:</span> 
                                <span id="verificadoPor">-</span>
                            </div>
                            
                            <div class="text-white/80">
                                <span class="text-white/50">Data da verificação:</span> 
                                <span id="dataVerificacao">-</span>
                            </div>
                            
                            <div id="observacoesAtuaisContainer" class="bg-white/5 rounded-xl p-4 border border-white/10 mt-3">
                                <h5 class="text-white/90 font-medium mb-2">Observações</h5>
                                <p id="observacoesAtuais" class="text-white/80 text-sm">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="container mx-auto mt-16 px-4 pb-8 text-center text-white/60 text-sm">
        <p>© <script>document.write(new Date().getFullYear())</script> DriveNow Admin. Todos os direitos reservados.</p>
    </footer>
    
    <!-- Incluir script de notificações -->
    <script src="../assets/notifications.js"></script>
    
    <!-- JavaScript para o modal de documentos -->
    <script>
        // Armazenar dados dos usuários para uso no modal
        const usuariosData = <?= json_encode($usuarios) ?>;
        
        // Função para abrir o modal de documentos
        function abrirModalDocumentos(userId, nomeUsuario) {
            // Encontrar dados do usuário selecionado
            const userData = usuariosData.find(user => parseInt(user.id) === userId);
            
            if (!userData) {
                notifyError('Erro ao carregar dados do usuário');
                return;
            }
            
            // Atualizar título do modal
            document.getElementById('modalTitle').textContent = `Documentos de ${nomeUsuario}`;
            
            // Debug no console
            console.log("Dados do usuário:", userData);
            
            // Atualizar imagens ou mostrar mensagem de não disponível
            const imgCnhFrente = document.getElementById('imgCnhFrente');
            const imgCnhVerso = document.getElementById('imgCnhVerso');
            const noImgCnhFrente = document.getElementById('noImgCnhFrente');
            const noImgCnhVerso = document.getElementById('noImgCnhVerso');
            
            if (userData.foto_cnh_frente) {
                // Exibir imagem frente da CNH
                const caminhoImagem = userData.foto_cnh_frente.startsWith('/') ? userData.foto_cnh_frente : '/' + userData.foto_cnh_frente;
                imgCnhFrente.src = '..' + caminhoImagem;
                imgCnhFrente.classList.remove('hidden');
                noImgCnhFrente.classList.add('hidden');
                
                // Debug de carregamento
                imgCnhFrente.onload = function() {
                    console.log("Imagem de frente carregada com sucesso");
                };
                
                imgCnhFrente.onerror = function() {
                    console.error("Erro ao carregar imagem de frente:", imgCnhFrente.src);
                    imgCnhFrente.classList.add('hidden');
                    noImgCnhFrente.classList.remove('hidden');
                };
            } else {
                imgCnhFrente.classList.add('hidden');
                noImgCnhFrente.classList.remove('hidden');
            }
            
            if (userData.foto_cnh_verso) {
                // Exibir imagem verso da CNH
                const caminhoImagem = userData.foto_cnh_verso.startsWith('/') ? userData.foto_cnh_verso : '/' + userData.foto_cnh_verso;
                imgCnhVerso.src = '..' + caminhoImagem;
                imgCnhVerso.classList.remove('hidden');
                noImgCnhVerso.classList.add('hidden');
                
                // Debug de carregamento
                imgCnhVerso.onload = function() {
                    console.log("Imagem de verso carregada com sucesso");
                };
                
                imgCnhVerso.onerror = function() {
                    console.error("Erro ao carregar imagem de verso:", imgCnhVerso.src);
                    imgCnhVerso.classList.add('hidden');
                    noImgCnhVerso.classList.remove('hidden');
                };
            } else {
                imgCnhVerso.classList.add('hidden');
                noImgCnhVerso.classList.remove('hidden');
            }
            
            // Preencher o ID do usuário no formulário
            document.getElementById('user_id').value = userId;
            
            // Configurar o status atual
            const statusBadge = document.getElementById('statusBadge');
            
            if (userData.status_docs === 'aprovado') {
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-emerald-500/30 text-emerald-300 border border-emerald-400/30';
                statusBadge.textContent = 'Aprovado';
            } else if (userData.status_docs === 'rejeitado') {
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-red-500/30 text-red-300 border border-red-400/30';
                statusBadge.textContent = 'Rejeitado';
            } else {
                statusBadge.className = 'px-3 py-1 rounded-full text-sm font-medium bg-amber-500/30 text-amber-300 border border-amber-400/30';
                statusBadge.textContent = 'Pendente';
            }
            
            // Preencher detalhes da verificação
            document.getElementById('verificadoPor').textContent = userData.admin_verificacao ? 
                usuariosData.find(u => u.id === userData.admin_verificacao)?.primeiro_nome + ' ' + 
                usuariosData.find(u => u.id === userData.admin_verificacao)?.segundo_nome || 
                `Admin #${userData.admin_verificacao}` : '-';
                
            document.getElementById('dataVerificacao').textContent = userData.data_verificacao ? 
                new Date(userData.data_verificacao).toLocaleString('pt-BR') : '-';
                
            document.getElementById('observacoesAtuais').textContent = userData.observacoes_docs || '-';
            
            // Mostrar/ocultar formulário conforme status
            const formAvaliacaoContainer = document.getElementById('formAvaliacaoContainer');
            
            // Se já estiver aprovado, oculta o formulário de avaliação
            if (userData.status_docs === 'aprovado') {
                formAvaliacaoContainer.classList.add('hidden');
            } else {
                formAvaliacaoContainer.classList.remove('hidden');
                
                // Limpa as observações anteriores se estiver reavaliando um documento rejeitado
                document.getElementById('observacoes').value = '';
            }
            
            // Mostrar o modal
            document.getElementById('modalDocumentos').classList.remove('hidden');
        }
        
        // Função para fechar o modal
        function fecharModalDocumentos() {
            document.getElementById('modalDocumentos').classList.add('hidden');
        }
        
        // Adicionar validação antes de rejeitar documentos
        document.getElementById('formAvaliacao').addEventListener('submit', function(e) {
            const action = e.submitter.value;
            const observacoes = document.getElementById('observacoes').value.trim();
            
            // Se estiver rejeitando, verificar se há observações
            if (action === 'rejeitar' && observacoes === '') {
                e.preventDefault();
                notifyError('Para rejeitar documentos, é necessário informar o motivo.');
                return false;
            }
            
            return true;
        });
        
        // Adicionar listener para a tecla ESC para fechar o modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalDocumentos();
            }
        });
        
        // Processar notificações de sessão
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['notification'])): ?>
                <?php if ($_SESSION['notification']['type'] === 'success'): ?>
                    notifySuccess('<?= addslashes($_SESSION['notification']['message']) ?>');
                <?php elseif ($_SESSION['notification']['type'] === 'error'): ?>
                    notifyError('<?= addslashes($_SESSION['notification']['message']) ?>');
                <?php elseif ($_SESSION['notification']['type'] === 'warning'): ?>
                    notifyWarning('<?= addslashes($_SESSION['notification']['message']) ?>');
                <?php else: ?>
                    notifyInfo('<?= addslashes($_SESSION['notification']['message']) ?>');
                <?php endif; ?>
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>