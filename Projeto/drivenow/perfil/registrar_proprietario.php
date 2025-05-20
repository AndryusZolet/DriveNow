<?php
// Incluir o arquivo de autenticação
require_once '../includes/auth.php';

// Verificar se o usuário está logado
verificarAutenticacao();

// Obter dados do usuário logado
$usuario = getUsuario();

if (!$usuario) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não está logado']);
    exit;
}

// Verificar se já é dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if ($dono) {
    echo json_encode(['status' => 'error', 'message' => 'Você já está registrado como proprietário']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO dono (conta_usuario_id) VALUES (?)");
    $stmt->execute([$usuario['id']]);
    
    echo json_encode(['status' => 'success', 'message' => 'Você foi registrado como proprietário com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao registrar como proprietário: ' . $e->getMessage()]);
}
?>