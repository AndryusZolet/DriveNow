<?php
/**
 * API para verificar novas mensagens no chat
 * Retorna novas mensagens desde um timestamp específico
 */

// Incluir arquivo de autenticação
require_once '../includes/auth.php';

// Retornar como JSON
header('Content-Type: application/json');

// Verificar autenticação
if (!estaLogado()) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ]);
    exit;
}

$usuario = getUsuario();
global $pdo;

// Verificar parâmetros necessários
if (!isset($_GET['reserva']) || !is_numeric($_GET['reserva'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID da reserva não fornecido ou inválido'
    ]);
    exit;
}

$reservaId = (int)$_GET['reserva'];
$timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : null;

// Verificar se o usuário tem permissão para acessar esta conversa
$stmt = $pdo->prepare("
    SELECT r.*, 
           d.conta_usuario_id AS proprietario_id,
           r.conta_usuario_id AS locatario_id
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN dono d ON v.dono_id = d.id
    WHERE r.id = ?
");
$stmt->execute([$reservaId]);
$reserva = $stmt->fetch();

if (!$reserva) {
    echo json_encode([
        'success' => false,
        'message' => 'Reserva não encontrada'
    ]);
    exit;
}

// Verificar se o usuário é o locatário ou o proprietário
if ($reserva['locatario_id'] !== $usuario['id'] && $reserva['proprietario_id'] !== $usuario['id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Sem permissão para acessar esta conversa'
    ]);
    exit;
}

// Buscar mensagens novas desde o timestamp informado
$sql = "
    SELECT m.*, cu.primeiro_nome, cu.segundo_nome, 
           CONCAT(cu.primeiro_nome, ' ', cu.segundo_nome) AS nome_remetente
    FROM mensagem m
    INNER JOIN conta_usuario cu ON m.remetente_id = cu.id
    WHERE m.reserva_id = ?
";

$params = [$reservaId];

// Adicionar condição de timestamp se fornecido
if ($timestamp) {
    $sql .= " AND m.data_envio > ?";
    $params[] = $timestamp;
}

// Adicionar restrição para evitar duplicação com base no conteúdo da mensagem
// Apenas se não houver mensagens recentes idênticas do mesmo remetente
$sql .= " AND NOT EXISTS (
    SELECT 1 FROM mensagem m2
    WHERE m2.reserva_id = m.reserva_id
    AND m2.remetente_id = m.remetente_id
    AND m2.mensagem = m.mensagem
    AND m2.id < m.id
    AND m2.data_envio >= DATE_SUB(m.data_envio, INTERVAL 10 SECOND)
)";

$sql .= " ORDER BY m.data_envio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$novasMensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar todas as mensagens como lidas
if (!empty($novasMensagens)) {
    $stmt = $pdo->prepare("
        UPDATE mensagem
        SET lida = 1
        WHERE reserva_id = ? AND remetente_id != ? AND lida = 0
    ");
    $stmt->execute([$reservaId, $usuario['id']]);
}

// Retornar resultado como JSON
echo json_encode([
    'success' => true,
    'mensagens' => $novasMensagens,
    'count' => count($novasMensagens)
]);
