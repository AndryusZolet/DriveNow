<?php
// check_messages.php na pasta /mensagens/
require_once '../includes/auth.php';

// Verificar autenticação
if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$usuario = getUsuario();
global $pdo;

// Verificar se é uma requisição AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Obter parâmetros
$reservaId = isset($_GET['reserva']) ? (int)$_GET['reserva'] : 0;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (!$reservaId) {
    echo json_encode(['success' => false, 'message' => 'ID da reserva não fornecido']);
    exit;
}

// Verificar se o usuário tem permissão para acessar esta conversa
$stmt = $pdo->prepare("
    SELECT r.id, r.conta_usuario_id, d.conta_usuario_id AS proprietario_id
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN dono d ON v.dono_id = d.id
    WHERE r.id = ?
");
$stmt->execute([$reservaId]);
$reserva = $stmt->fetch();

if (!$reserva || 
    ($reserva['conta_usuario_id'] !== $usuario['id'] && 
     $reserva['proprietario_id'] !== $usuario['id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Buscar novas mensagens
$stmt = $pdo->prepare("
    SELECT m.id, m.reserva_id, m.remetente_id, m.mensagem, m.data_envio, m.lida,
           cu.primeiro_nome, cu.segundo_nome
    FROM mensagem m
    INNER JOIN conta_usuario cu ON m.remetente_id = cu.id
    WHERE m.reserva_id = ? AND m.id > ?
    ORDER BY m.id ASC
");
$stmt->execute([$reservaId, $lastId]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar mensagens como lidas
if (!empty($mensagens)) {
    $stmt = $pdo->prepare("
        UPDATE mensagem
        SET lida = 1
        WHERE reserva_id = ? AND remetente_id != ? AND lida = 0 AND id > ?
    ");
    $stmt->execute([$reservaId, $usuario['id'], $lastId]);
}

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'messages' => $mensagens,
    'count' => count($mensagens),
    'last_id' => $lastId
]);
?>