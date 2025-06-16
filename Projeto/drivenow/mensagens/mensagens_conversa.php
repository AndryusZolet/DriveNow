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
    header('Location: mensagens.php');
    exit;
}

$reservaId = (int)$_GET['reserva'];

// Processar ações de status da reserva (similar ao reservas_recebidas.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_id']) && isset($_POST['acao'])) {
    $reservaIdPost = (int)$_POST['reserva_id'];
    $acao = $_POST['acao'];
    
    // Verificar se a reserva ID do POST corresponde à da URL
    if ($reservaIdPost === $reservaId) {
        try {
            // Verificar se o usuário tem permissão para esta ação
            $stmt = $pdo->prepare("
                SELECT r.*, v.dono_id, d.conta_usuario_id as proprietario_id
                FROM reserva r
                INNER JOIN veiculo v ON r.veiculo_id = v.id
                INNER JOIN dono d ON v.dono_id = d.id
                WHERE r.id = ?
            ");
            $stmt->execute([$reservaId]);
            $reservaCheck = $stmt->fetch();
            
            if (!$reservaCheck) {
                throw new Exception('Reserva não encontrada');
            }
            
            $ehProprietario = $reservaCheck['proprietario_id'] === $usuario['id'];
            $ehLocatario = $reservaCheck['conta_usuario_id'] === $usuario['id'];
            
            if (!$ehProprietario && !$ehLocatario) {
                throw new Exception('Você não tem permissão para esta ação');
            }
            
            $statusAtualizado = null;
            $mensagemSucesso = '';
            
            switch ($acao) {
                case 'confirmar':
                    if (!$ehProprietario) {
                        throw new Exception('Apenas o proprietário pode confirmar reservas');
                    }
                    $statusAtualizado = 'confirmada';
                    $mensagemSucesso = 'Reserva confirmada com sucesso!';
                    break;
                    
                case 'rejeitar':
                    if (!$ehProprietario) {
                        throw new Exception('Apenas o proprietário pode rejeitar reservas');
                    }
                    $statusAtualizado = 'rejeitada';
                    $mensagemSucesso = 'Reserva rejeitada com sucesso!';
                    break;
                    
                case 'finalizar':
                    if (!$ehProprietario) {
                        throw new Exception('Apenas o proprietário pode finalizar reservas');
                    }
                    $statusAtualizado = 'finalizada';
                    $mensagemSucesso = 'Reserva finalizada com sucesso!';
                    break;
                    
                case 'cancelar':
                    if (!$ehLocatario) {
                        throw new Exception('Apenas o locatário pode cancelar reservas');
                    }
                    $statusAtualizado = 'cancelada';
                    $mensagemSucesso = 'Reserva cancelada com sucesso!';
                    break;
                    
                default:
                    throw new Exception('Ação inválida');
            }
            
            if ($statusAtualizado) {
                $stmt = $pdo->prepare("UPDATE reserva SET status = ? WHERE id = ?");
                $stmt->execute([$statusAtualizado, $reservaId]);
                
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => $mensagemSucesso
                ];
            }
            
        } catch (Exception $e) {
            $_SESSION['notification'] = [
                'type' => 'error',
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
        
        // Redirecionar para evitar reenvio do formulário
        header("Location: mensagens_conversa.php?reserva={$reservaId}");
        exit;
    }
}

// Verificar se o usuário tem permissão para acessar esta conversa
// (deve ser o locatário ou o proprietário do veículo)
$stmt = $pdo->prepare("
    SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano, v.veiculo_placa,
           loc.nome_local, c.cidade_nome, e.sigla, 
           d.conta_usuario_id AS proprietario_id,
           proprio.primeiro_nome AS dono_nome, proprio.segundo_nome AS dono_sobrenome,
           locat.primeiro_nome AS locatario_nome, locat.segundo_nome AS locatario_sobrenome,
           locat.id AS locatario_id
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN dono d ON v.dono_id = d.id
    INNER JOIN conta_usuario proprio ON d.conta_usuario_id = proprio.id
    INNER JOIN conta_usuario locat ON r.conta_usuario_id = locat.id
    LEFT JOIN local loc ON v.local_id = loc.id
    LEFT JOIN cidade c ON loc.cidade_id = c.id
    LEFT JOIN estado e ON c.estado_id = e.id
    WHERE r.id = ?
");
$stmt->execute([$reservaId]);
$reserva = $stmt->fetch();

if (!$reserva) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não encontrada.'
    ];
    header('Location: mensagens.php');
    exit;
}

// Verificar se o usuário é o locatário ou o proprietário do veículo
if ($reserva['locatario_id'] !== $usuario['id'] && $reserva['proprietario_id'] !== $usuario['id']) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Você não tem permissão para acessar esta conversa.'
    ];
    header('Location: mensagens.php');
    exit;
}

// Determinar o papel do usuário
$ehProprietario = $reserva['proprietario_id'] === $usuario['id'];
$outraPessoa = $ehProprietario 
    ? $reserva['locatario_nome'] . ' ' . $reserva['locatario_sobrenome'] 
    : $reserva['dono_nome'] . ' ' . $reserva['dono_sobrenome'];
$outroUsuarioId = $ehProprietario ? $reserva['locatario_id'] : $reserva['proprietario_id'];

// Verificar se é uma requisição AJAX para envio de mensagem
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Buscar mensagens da conversa (com DISTINCT para evitar duplicatas)
$stmt = $pdo->prepare("
    SELECT DISTINCT m.id, m.reserva_id, m.remetente_id, m.mensagem, m.data_envio, m.lida,
           cu.primeiro_nome, cu.segundo_nome
    FROM mensagem m
    INNER JOIN conta_usuario cu ON m.remetente_id = cu.id
    WHERE m.reserva_id = ?
    ORDER BY m.id ASC
");
$stmt->execute([$reservaId]);
$mensagens = $stmt->fetchAll();

// Marcar todas as mensagens como lidas
$stmt = $pdo->prepare("
    UPDATE mensagem
    SET lida = 1
    WHERE reserva_id = ? AND remetente_id != ? AND lida = 0
");
$stmt->execute([$reservaId, $usuario['id']]);

// Processar o envio de nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem']) && !empty($_POST['mensagem'])) {
    // Verificar se a reserva permite troca de mensagens (não finalizada, cancelada ou rejeitada)
    if (in_array($reserva['status'], ['finalizada', 'cancelada', 'rejeitada'])) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Não é possível enviar mensagens em reservas finalizadas, canceladas ou rejeitadas.'
        ];
    } else {
        $mensagemTexto = trim($_POST['mensagem']);
        
        if (!empty($mensagemTexto)) {
            // Verificar se a mensagem já foi enviada recentemente (evitar duplicatas)
            $stmt = $pdo->prepare("
                SELECT id FROM mensagem 
                WHERE reserva_id = ? 
                AND remetente_id = ? 
                AND mensagem = ? 
                AND data_envio >= DATE_SUB(NOW(), INTERVAL 10 SECOND)
            ");
            $stmt->execute([$reservaId, $usuario['id'], $mensagemTexto]);
            
            if ($stmt->rowCount() > 0) {
                // Mensagem duplicada, não inserir novamente
                if ($isAjaxRequest) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Mensagem já enviada'
                    ]);
                    exit;
                } else {
                    header("Location: mensagens_conversa.php?reserva={$reservaId}");
                    exit;
                }
            }
            
            // Inserir a mensagem no banco de dados
            $stmt = $pdo->prepare("
                INSERT INTO mensagem (reserva_id, remetente_id, mensagem, data_envio) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$reservaId, $usuario['id'], $mensagemTexto]);
            
            // Incrementar o contador de mensagens não lidas do outro usuário
            $stmt = $pdo->prepare("
                UPDATE conta_usuario
                SET mensagens_nao_lidas = mensagens_nao_lidas + 1
                WHERE id = ?
            ");
            $stmt->execute([$outroUsuarioId]);
            
            // Responder se for AJAX ou redirecionar se for envio normal
            if ($isAjaxRequest) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mensagem enviada com sucesso'
                ]);
                exit;
            } else {
                // Redirecionar para limpar o formulário (evitar reenvio ao atualizar)
                header("Location: mensagens_conversa.php?reserva={$reservaId}");
                exit;
            }
        }
    }
}

// Formatar datas da reserva
$dataInicio = date('d/m/Y', strtotime($reserva['reserva_data']));
$dataFim = date('d/m/Y', strtotime($reserva['devolucao_data']));

// Determinar status atual baseado nas datas
$now = time();
$inicio = strtotime($reserva['reserva_data']);
$fim = strtotime($reserva['devolucao_data']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversa - DriveNow</title>
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
            height: calc(100vh - 400px);
            min-height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            padding-right: 8px;
        }

        .message-item {
            max-width: 80%;
            margin-bottom: 16px;
            padding: 12px 16px;
            border-width: 1px;
            position: relative;
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

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 4px;
            text-align: right;
        }
        
        /* Indicador de digitação */
        .typing-indicator {
            display: none;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 16px;
            opacity: 0.7;
            font-style: italic;
            font-size: 0.85rem;
        }
        
        /* Animação de atualização */
        @keyframes fadeInOut {
            0% { opacity: 0.7; }
            50% { opacity: 0.3; }
            100% { opacity: 0.7; }
        }
          .update-animation {
            animation: fadeInOut 2s infinite;
        }
        
        /* Animação de rotação para o botão de atualização */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .animate-spin {
            animation: spin 1s linear infinite;
        }

        /* Estilos para modais */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            border-radius: 1rem;
            max-width: 90%;
            width: 450px;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white p-4 md:p-8 overflow-x-hidden">

    <!-- Modais de Confirmação -->
    <div id="confirmModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Confirmar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja confirmar esta reserva?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelConfirm" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Cancelar</button>
                <form id="confirmForm" method="post">
                    <input type="hidden" name="reserva_id" id="confirmReservaId">
                    <input type="hidden" name="acao" value="confirmar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-green-500 hover:bg-green-600 text-white transition-colors">Confirmar</button>
                </form>
            </div>
        </div>
    </div>

    <div id="rejectModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Rejeitar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja rejeitar esta reserva?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelReject" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Cancelar</button>
                <form id="rejectForm" method="post">
                    <input type="hidden" name="reserva_id" id="rejectReservaId">
                    <input type="hidden" name="acao" value="rejeitar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors">Rejeitar</button>
                </form>
            </div>
        </div>
    </div>

    <div id="finishModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Finalizar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja finalizar esta reserva antecipadamente?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelFinish" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Cancelar</button>
                <form id="finishForm" method="post">
                    <input type="hidden" name="reserva_id" id="finishReservaId">
                    <input type="hidden" name="acao" value="finalizar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white transition-colors">Finalizar</button>
                </form>
            </div>
        </div>
    </div>

    <div id="cancelModal" class="modal">
        <div class="modal-content backdrop-blur-xl bg-slate-800/90 border subtle-border p-6 shadow-xl">
            <h3 class="text-xl font-bold text-white mb-4">Cancelar Reserva</h3>
            <p class="text-white/80 mb-6">Você deseja cancelar esta reserva?</p>
            <div class="flex justify-end gap-3">
                <button id="cancelCancel" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors">Voltar</button>
                <form id="cancelForm" method="post">
                    <input type="hidden" name="reserva_id" id="cancelReservaId">
                    <input type="hidden" name="acao" value="cancelar">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors">Cancelar Reserva</button>
                </form>
            </div>
        </div>
    </div>

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
                        <a href="mensagens.php" class="text-white/80 hover:text-white transition-colors">Mensagens</a>
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
        <!-- Área de conversa -->
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg overflow-hidden mb-6">            <!-- Cabeçalho da conversa -->
            <div class="p-6 border-b subtle-border flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="mensagens.php" class="p-2 rounded-full border subtle-border hover:bg-white/10 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center overflow-hidden">
                            <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($outraPessoa) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="<?= htmlspecialchars($outraPessoa) ?>" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h2 class="font-medium text-white"><?= htmlspecialchars($outraPessoa) ?></h2>
                            <p class="text-white/60 text-sm">
                                <?= $ehProprietario ? 'Locatário' : 'Proprietário' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <button id="refresh-chat" type="button" class="bg-white/5 hover:bg-white/10 text-white/90 rounded-xl transition-colors border subtle-border px-3 py-2 text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 2v6h-6"></path>
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                        <path d="M3 22v-6h6"></path>
                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                    </svg>
                    Atualizar
                </button>
                
                <!-- <a href="../reserva/detalhes_reserva.php?id=<?= $reservaId ?>" class="bg-indigo-500/20 hover:bg-indigo-500/30 text-white/90 font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="m12 16 4-4-4-4"/>
                        <path d="M8 12h8"/>
                    </svg>
                    Ver Reserva
                </a> -->
            </div>
            
            <!-- Informações da reserva -->
            <div class="px-6 py-3 bg-white/5 border-b subtle-border">
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <div class="text-white/70">
                        <span class="text-white/50">Veículo:</span>
                        <?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?> (<?= htmlspecialchars($reserva['veiculo_ano']) ?>)
                    </div>
                    <div class="text-white/70">
                        <span class="text-white/50">Período:</span>
                        <?= $dataInicio ?> - <?= $dataFim ?>
                    </div>
                    <div class="text-white/70">
                        <span class="text-white/50">Status:</span>
                        <?php
                            $statusLabels = [
                                'pendente' => 'Pendente',
                                'confirmada' => 'Confirmada',
                                'em_andamento' => 'Em Andamento',
                                'finalizada' => 'Finalizada',
                                'cancelada' => 'Cancelada',
                                'rejeitada' => 'Rejeitada'
                            ];
                            echo $statusLabels[$reserva['status']] ?? 'Desconhecido';
                        ?>
                    </div>
                </div>
            </div>            <!-- Mensagens -->
            <div class="message-container p-6" id="message-container">
                <?php if (empty($mensagens)): ?>
                    <div class="text-center py-8 text-white/50">
                        <div class="mx-auto mb-4 w-12 h-12 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <p>Nenhuma mensagem ainda. Inicie a conversa!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($mensagens as $mensagem): ?>
                        <?php 
                            $ehRemetente = $mensagem['remetente_id'] === $usuario['id'];
                            $dataEnvio = date('d/m/Y H:i', strtotime($mensagem['data_envio']));
                            $nomeRemetente = $mensagem['primeiro_nome'] . ' ' . $mensagem['segundo_nome'];
                        ?>                        <div class="message-item <?= $ehRemetente ? 'sent' : 'received' ?>" data-message-id="<?= $mensagem['id'] ?>">
                            <?php if (!$ehRemetente): ?>
                                <div class="text-xs text-white/60 mb-1"><?= htmlspecialchars($nomeRemetente) ?></div>
                            <?php endif; ?>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($mensagem['mensagem'])) ?>
                            </div>
                            <div class="message-time">
                                <?= $dataEnvio ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?></div>
            
            <!-- Indicador de nova mensagem -->
            <div id="typing-indicator" class="typing-indicator text-white/70 ml-4 mb-4">
                <span class="update-animation">Atualizando mensagens...</span>
            </div>
            
            <!-- Formulário de envio de mensagem -->
            <?php if (!in_array($reserva['status'], ['finalizada', 'cancelada', 'rejeitada'])): ?>
            <div class="p-6 border-t subtle-border">
                <form method="POST" action="" class="flex gap-3">
                    <textarea 
                        name="mensagem" 
                        placeholder="Digite sua mensagem..." 
                        class="flex-1 min-h-[60px] max-h-32 bg-white/5 border subtle-border rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-none outline-none text-white resize-none"
                        rows="2"
                        required
                    ></textarea>
                    <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-6 shadow-md hover:shadow-lg flex-shrink-0 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="m22 2-7 20-4-9-9-4Z"/>
                            <path d="M22 2 11 13"/>
                        </svg>
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="p-4 text-center border-t subtle-border bg-white/5">
                <div class="text-white/70 italic">
                    A conversa está encerrada pois a reserva foi <?= strtolower($reserva['status']) ?>.
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Informações adicionais e ações -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Informações do Veículo</h2>
                <div class="grid gap-2">
                    <div class="flex justify-between">
                        <span class="text-white/60">Marca:</span>
                        <span class="text-white"><?= htmlspecialchars($reserva['veiculo_marca']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Modelo:</span>
                        <span class="text-white"><?= htmlspecialchars($reserva['veiculo_modelo']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Ano:</span>
                        <span class="text-white"><?= htmlspecialchars($reserva['veiculo_ano']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Placa:</span>
                        <span class="text-white"><?= htmlspecialchars($reserva['veiculo_placa']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Local:</span>
                        <span class="text-white">
                            <?= htmlspecialchars($reserva['nome_local'] ?? 'N/D') ?>
                            <?php if (isset($reserva['cidade_nome'])): ?>
                                <span class="text-white/60 text-xs">(<?= htmlspecialchars($reserva['cidade_nome']) ?>-<?= htmlspecialchars($reserva['sigla']) ?>)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 shadow-lg">
                <h2 class="text-xl font-bold mb-4">Ações</h2>
                
                <div class="space-y-4">
                    <?php
                    // Verificar se a data de início da reserva já passou
                    $dataReservaPassou = strtotime($reserva['reserva_data']) < time();
                    
                    if ((empty($reserva['status']) || $reserva['status'] === 'pendente') && !$dataReservaPassou && $ehProprietario): ?>
                        <button data-id="<?= $reservaId ?>" class="w-full bg-green-500/20 hover:bg-green-500/30 text-green-300 border border-green-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors confirm-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2 inline-block">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Confirmar Reserva
                        </button>
                        <button data-id="<?= $reservaId ?>" class="w-full bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors reject-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2 inline-block">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Rejeitar Reserva
                        </button>
                    <?php elseif ($reserva['status'] === 'confirmada' && $now >= $inicio && $now <= $fim && $ehProprietario): ?>
                        <button data-id="<?= $reservaId ?>" class="w-full bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors finish-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2 inline-block">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                <line x1="4" y1="22" x2="4" y2="15"></line>
                            </svg>
                            Finalizar Reserva
                        </button>
                    <?php endif; ?>
                    
                    <?php if ((!isset($reserva['status']) || $reserva['status'] == 'pendente' || $reserva['status'] === null) && !$dataReservaPassou && !$ehProprietario): ?>
                        <button data-id="<?= $reservaId ?>" class="w-full bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors cancel-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2 inline-block">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Cancelar Reserva
                        </button>
                    <?php endif; ?>
                    
                    <a href="../reserva/detalhes_reserva.php?id=<?= $reservaId ?>" class="w-full bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 border border-indigo-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        Ver Detalhes da Reserva
                    </a>
                    
                    <?php if ($reserva['status'] === 'finalizada'): ?>
                        <?php if (!$ehProprietario): ?>
                            <a href="../avaliacao/avaliar_veiculo.php?reserva=<?= $reservaId ?>" class="w-full bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 border border-yellow-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2">
                                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                </svg>
                                Avaliar Veículo e Locador
                            </a>
                        <?php else: ?>
                            <a href="../avaliacao/avaliar_locatario.php?reserva=<?= $reservaId ?>" class="w-full bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 border border-yellow-400/30 rounded-xl px-4 py-2 text-sm font-medium transition-colors flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-2">
                                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                </svg>
                                Avaliar Locatário
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="mt-12 mb-6 px-4 text-center text-white/50 text-sm">
        <p>&copy; <?= date('Y') ?> DriveNow. Todos os direitos reservados.</p>
    </footer>
    
    <script src="../assets/notifications.js"></script>
    <script src="../assets/live-chat.js"></script>
    <script>        // Debug: verificar se as funções foram carregadas
        console.log('=== CHAT DEBUG INFO ===');
        console.log('Live chat carregado?', typeof initializeChat === 'function');
        console.log('CheckNewMessages disponível?', typeof checkNewMessages === 'function');
        console.log('Reserva ID:', <?= $reservaId ?>);
        console.log('User ID:', <?= $usuario['id'] ?>);
        console.log('========================');
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeNotifications();
            
            <?php if (isset($_SESSION['notification'])): ?>
                notify('<?= $_SESSION['notification']['message'] ?>', '<?= $_SESSION['notification']['type'] ?>');
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
            
            // Auto-scroll para o final da conversa
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
            
            // Auto-focus no campo de mensagem
            const messageInput = document.querySelector('textarea[name="mensagem"]');
            if (messageInput) {
                messageInput.focus();
            }
            
            // Definir o ID do usuário atual para o chat em tempo real
            window.userId = <?= $usuario['id'] ?>;
            
            // Verificar se estamos voltando de outra página ou reconectando
            // Usar localStorage para persistir entre abas
            const chatSession = localStorage.getItem('chatSession_' + <?= $reservaId ?>);
            const pageReloaded = chatSession ? true : false;
            localStorage.setItem('chatSession_' + <?= $reservaId ?>, Date.now());
              // Inicializar o chat em tempo real
            initializeChat({
                updateInterval: 3000, // Verificar a cada 3 segundos
                scrollOnNewMessages: true,
                showNotification: true,
                isReconnection: pageReloaded
            });
            
            // Funções para manipulação dos modais
            const confirmModal = document.getElementById('confirmModal');
            const rejectModal = document.getElementById('rejectModal');
            const finishModal = document.getElementById('finishModal');
            const cancelModal = document.getElementById('cancelModal');
            
            // Adicionar eventos aos botões de confirmação
            document.querySelectorAll('.confirm-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('confirmReservaId').value = this.getAttribute('data-id');
                    confirmModal.style.display = 'flex';
                });
            });
            
            // Adicionar eventos aos botões de rejeição
            document.querySelectorAll('.reject-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('rejectReservaId').value = this.getAttribute('data-id');
                    rejectModal.style.display = 'flex';
                });
            });
            
            // Adicionar eventos aos botões de finalização
            document.querySelectorAll('.finish-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('finishReservaId').value = this.getAttribute('data-id');
                    finishModal.style.display = 'flex';
                });
            });
            
            // Adicionar eventos aos botões de cancelamento
            document.querySelectorAll('.cancel-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('cancelReservaId').value = this.getAttribute('data-id');
                    cancelModal.style.display = 'flex';
                });
            });
            
            // Botões para fechar os modais
            document.getElementById('cancelConfirm').addEventListener('click', () => {
                confirmModal.style.display = 'none';
            });
            
            document.getElementById('cancelReject').addEventListener('click', () => {
                rejectModal.style.display = 'none';
            });
            
            document.getElementById('cancelFinish').addEventListener('click', () => {
                finishModal.style.display = 'none';
            });
            
            document.getElementById('cancelCancel').addEventListener('click', () => {
                cancelModal.style.display = 'none';
            });
            
            // Fechar modal ao clicar fora da área do conteúdo
            window.addEventListener('click', function(event) {
                if (event.target === confirmModal) {
                    confirmModal.style.display = 'none';
                }
                if (event.target === rejectModal) {
                    rejectModal.style.display = 'none';
                }
                if (event.target === finishModal) {
                    finishModal.style.display = 'none';
                }
                if (event.target === cancelModal) {
                    cancelModal.style.display = 'none';
                }
            });
            
            // Fechar modal com a tecla ESC
            window.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    confirmModal.style.display = 'none';
                    rejectModal.style.display = 'none';
                    finishModal.style.display = 'none';
                    cancelModal.style.display = 'none';
                }
            });
            
            // Manipular envio de formulário para evitar recarregar a página
            const messageForm = document.querySelector('form');
            messageForm.addEventListener('submit', function(e) {
                // Só interceptamos se tiver JavaScript habilitado
                // O fallback será o envio normal do formulário
                if (messageInput.value.trim() !== '') {
                    e.preventDefault();
                    
                    // Armazenar o texto da mensagem antes de enviá-la
                    const mensagemTexto = messageInput.value.trim();
                    
                    // Verificar se a mesma mensagem foi enviada recentemente (anti-duplicação)
                    const lastSentMessage = localStorage.getItem('lastSentMessage_' + <?= $reservaId ?>);
                    const lastSentTime = localStorage.getItem('lastSentTime_' + <?= $reservaId ?>);
                    const currentTime = Date.now();
                    
                    // Se a mesma mensagem foi enviada nos últimos 3 segundos, bloquear
                    if (lastSentMessage === mensagemTexto && 
                        lastSentTime && 
                        (currentTime - parseInt(lastSentTime)) < 3000) {
                        return; // Ignora cliques repetidos rápidos
                    }
                    
                    // Registrar esta mensagem como última enviada
                    localStorage.setItem('lastSentMessage_' + <?= $reservaId ?>, mensagemTexto);
                    localStorage.setItem('lastSentTime_' + <?= $reservaId ?>, currentTime);
                    
                    // Mostrar indicador de envio
                    const typingIndicator = document.getElementById('typing-indicator');
                    typingIndicator.style.display = 'block';
                    typingIndicator.querySelector('span').textContent = 'Enviando mensagem...';
                    
                    // Desabilitar o botão de envio
                    const submitButton = messageForm.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                    }
                    
                    // Criar FormData e enviar via AJAX
                    const formData = new FormData(this);
                    
                    fetch('<?= $_SERVER['REQUEST_URI'] ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erro ao enviar mensagem');
                        }
                        return response.json();
                    })                    .then(data => {
                        if (data.success) {
                            // Limpar o campo de mensagem
                            messageInput.value = '';
                            
                            // Adicionar a mensagem imediatamente no chat sem esperar
                            // Criar a mensagem localmente
                            const novaMensagem = {
                                id: Date.now(), // ID temporário único
                                remetente_id: <?= $usuario['id'] ?>,
                                mensagem: mensagemTexto,
                                data_envio: new Date().toISOString(),
                                primeiro_nome: '<?= htmlspecialchars($usuario['primeiro_nome']) ?>',
                                segundo_nome: '<?= htmlspecialchars($usuario['segundo_nome']) ?>'
                            };
                            
                            // Adicionar mensagem ao chat imediatamente
                            addNewMessages([novaMensagem]);
                            
                            // Aguardar um pequeno intervalo antes de verificar novas mensagens
                            // Isso dá tempo para o servidor processar a mensagem e sincronizar
                            setTimeout(() => {
                                // Forçar verificação imediata de novas mensagens para sincronizar
                                if (typeof checkNewMessages === 'function') {
                                    checkNewMessages();
                                }
                                // Esconder indicador
                                typingIndicator.style.display = 'none';
                                // Focar novamente no campo de mensagem
                                messageInput.focus();
                                // Reabilitar o botão de envio
                                if (submitButton) {
                                    submitButton.disabled = false;
                                }
                            }, 500);
                        } else {
                            throw new Error(data.message || 'Erro ao enviar mensagem');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        notify('Erro ao enviar mensagem. Tente novamente.', 'error');
                        typingIndicator.style.display = 'none';
                        // Reabilitar o botão de envio
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    });
                }
            });
            
            // Botão de atualização manual
            const refreshButton = document.getElementById('refresh-chat');
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    this.querySelector('svg').classList.add('animate-spin');
                    this.disabled = true;
                    
                    // Forçar verificação de novas mensagens
                    checkNewMessages();
                    
                    // Restaurar o botão após 1 segundo
                    setTimeout(() => {
                        this.querySelector('svg').classList.remove('animate-spin');
                        this.disabled = false;
                    }, 1000);
                });
            }
            
            // Verificar novas mensagens ao focar a janela novamente
            window.addEventListener('focus', function() {
                if (typeof chatInitialized !== 'undefined' && chatInitialized) {
                    checkNewMessages();
                }
            });
            
            // Limpar quando o usuário sai da página
            window.addEventListener('beforeunload', function() {
                // Manter um registro de que o chat estava ativo com timestamp
                // (será utilizado se o usuário retornar)
                localStorage.setItem('chatSession_' + <?= $reservaId ?>, Date.now());
            });
        });
    </script>
</body>
</html>