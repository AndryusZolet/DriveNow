<?php
require_once '../includes/auth.php';

verificarAutenticacao();

// Preparar resposta
header('Content-Type: application/json');
$response = ['exists' => false];

if (isset($_POST['placa'])) {
    $placa = trim($_POST['placa']);
    
    // Validar formato da placa
    $regexAntiga = '/^[A-Z]{3}-[0-9]{4}$/';
    $regexMercosul = '/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/';
    
    if (preg_match($regexAntiga, $placa) || preg_match($regexMercosul, $placa)) {
        global $pdo;
        
        // Verificar se a placa existe, excluindo o veículo atual se estiver em edição
        $sql = "SELECT id FROM veiculo WHERE veiculo_placa = ?";
        $params = [$placa];
        
        // Se estamos editando um veículo, adicionamos a condição para excluir esse veículo da verificação
        if (isset($_POST['veiculo_id']) && !empty($_POST['veiculo_id'])) {
            $sql .= " AND id != ?";
            $params[] = $_POST['veiculo_id'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            $response['exists'] = true;
        }
    }
}

echo json_encode($response);