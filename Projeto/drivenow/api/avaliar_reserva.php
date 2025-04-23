<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!estaLogado()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$usuario = getUsuario();
$reservaId = $_POST['reserva_id'] ?? null;
$nota = $_POST['nota'] ?? null;
$comentario = $_POST['comentario'] ?? '';

// Validar dados
if (empty($reservaId) || empty($nota)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

// Verificar se a reserva pertence ao usuário
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM reserva WHERE id = ? AND conta_usuario_id = ?");
$stmt->execute([$reservaId, $usuario['id']]);
$reserva = $stmt->fetch();

if (!$reserva) {
    echo json_encode(['success' => false, 'message' => 'Reserva não encontrada']);
    exit;
}

// Verificar se já existe avaliação para esta reserva
$stmt = $pdo->prepare("SELECT id FROM avaliacao WHERE reserva_id = ?");
$stmt->execute([$reservaId]);
$avaliacaoExistente = $stmt->fetch();

if ($avaliacaoExistente) {
    echo json_encode(['success' => false, 'message' => 'Esta reserva já foi avaliada']);
    exit;
}

// Obter informações do veículo para a avaliação
$stmt = $pdo->prepare("SELECT veiculo_id FROM reserva WHERE id = ?");
$stmt->execute([$reservaId]);
$dadosReserva = $stmt->fetch();

// Inserir avaliação
try {
    $stmt = $pdo->prepare("INSERT INTO avaliacao 
                          (veiculo_id, reserva_id, usuario_id, nota, comentario, data_avaliacao)
                          VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $dadosReserva['veiculo_id'],
        $reservaId,
        $usuario['id'],
        $nota,
        $comentario
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Avaliação registrada com sucesso']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar avaliação: ' . $e->getMessage()]);
}