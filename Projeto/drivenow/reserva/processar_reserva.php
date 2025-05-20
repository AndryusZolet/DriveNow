<?php
require_once '../includes/auth.php';

// Verificar se é uma requisição AJAX
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Requisição inválida']);
    exit;
}

// Verificar autenticação
if (!estaLogado()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'error', 'message' => 'Você precisa estar logado para fazer uma reserva.']);
    exit;
}

// Obter e validar dados
$veiculoId = filter_input(INPUT_POST, 'veiculo_id', FILTER_VALIDATE_INT);
$reservaData = filter_input(INPUT_POST, 'reserva_data', FILTER_SANITIZE_STRING);
$devolucaoData = filter_input(INPUT_POST, 'devolucao_data', FILTER_SANITIZE_STRING);
$observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);

// Validações básicas
if (!$veiculoId || !$reservaData || !$devolucaoData) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Todos os campos obrigatórios devem ser preenchidos.']);
    exit;
}

if (strtotime($devolucaoData) <= strtotime($reservaData)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'A data de devolução deve ser posterior à data de reserva.']);
    exit;
}

try {
    global $pdo;
    
    // Verificar se o veículo existe e obter preço da diária
    $stmt = $pdo->prepare("SELECT preco_diaria FROM veiculo WHERE id = ?");
    $stmt->execute([$veiculoId]);
    $veiculo = $stmt->fetch();
    
    if (!$veiculo) {
        throw new Exception('Veículo não encontrado.');
    }
    
    // Buscar o usuário logado
    $usuario = getUsuario();
    
    // Configurar taxas
    $diariaValor = $veiculo['preco_diaria'];
    $taxaUso = 20.00;
    $taxaLimpeza = 30.00;
    
    // Calcular valor total
    $dias = (strtotime($devolucaoData) - strtotime($reservaData)) / (60 * 60 * 24);
    $valorTotal = ($diariaValor * $dias) + $taxaUso + $taxaLimpeza;
    
    // Inserir reserva no banco de dados
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
        'message' => 'Reserva realizada com sucesso! Valor total: R$ ' . number_format($valorTotal, 2, ',', '.')
    ];
    
    // Redirecionar para a página de minhas reservas
    echo json_encode([
        'status' => 'success', 
        'message' => 'Reserva realizada com sucesso!', 
        'valor_total' => number_format($valorTotal, 2, ',', '.'),
        'reserva_id' => $pdo->lastInsertId(),
        'redirect' => 'minhas_reservas.php'
    ]);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['status' => 'error', 'message' => 'Erro ao processar reserva: ' . $e->getMessage()]);
    exit;
}