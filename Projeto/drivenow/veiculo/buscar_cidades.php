<?php
require_once '../includes/auth.php';

verificarAutenticacao();

// Verificar se o estado_id foi enviado
if (isset($_POST['estado_id']) && !empty($_POST['estado_id'])) {
    $estado_id = $_POST['estado_id'];
    
    // Buscar cidades do estado selecionado
    $stmt = $pdo->prepare("SELECT id, cidade_nome FROM cidade WHERE estado_id = ? ORDER BY cidade_nome");
    $stmt->execute([$estado_id]);
    $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar as cidades em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'cidades' => $cidades]);
    exit;
} else {
    // Retornar erro se o estado_id não foi enviado
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID do estado não fornecido']);
    exit;
}
?>