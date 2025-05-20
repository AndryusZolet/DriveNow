<?php
require_once '../includes/auth.php';

verificarAutenticacao();

// Verificar se o cidade_id foi enviado
if (isset($_POST['cidade_id']) && !empty($_POST['cidade_id'])) {
    $cidade_id = $_POST['cidade_id'];
    
    // Buscar locais da cidade selecionada
    $stmt = $pdo->prepare("SELECT id, nome_local FROM local WHERE cidade_id = ? ORDER BY nome_local");
    $stmt->execute([$cidade_id]);
    $locais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar os locais em formato JSON
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'locais' => $locais]);
    exit;
} else {
    // Retornar erro se o cidade_id não foi enviado
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'ID da cidade não fornecido']);
    exit;
}
?>