<?php
require_once '../includes/auth.php';

if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header('Location: veiculos.php');
    exit;
}

$id = $_GET['id'];
$status = $_GET['status'];

global $pdo;
// Primeiro verifique se o veículo pertence ao usuário logado
$stmt = $pdo->prepare("SELECT v.id 
                      FROM veiculo v
                      JOIN dono d ON v.dono_id = d.id
                      JOIN conta_usuario u ON d.conta_usuario_id = u.id
                      WHERE v.id = ? AND u.id = ?");
$stmt->execute([$id, getUsuario()['id']]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: veiculos.php');
    exit;
}

try {
    // Verifique se a coluna existe
    $stmt = $pdo->query("SHOW COLUMNS FROM veiculo LIKE 'disponivel'");
    if ($stmt->rowCount() > 0) {
        // Atualizar o status de disponibilidade
        $stmt = $pdo->prepare("UPDATE veiculo SET disponivel = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        header('Location: veiculos.php');
        exit;
    } else {
        // Registrar erro ou redirecionar com mensagem
        error_log("Coluna 'disponivel' não encontrada na tabela veiculo");
        header('Location: veiculos.php?erro=1');
        exit;
    }
} catch (PDOException $e) {
    // Log do erro para depuração
    error_log("Erro ao atualizar disponibilidade do veículo: " . $e->getMessage());
    
    // Redirecionar com mensagem de erro
    header('Location: veiculos.php?erro=1');
    exit;
}
?>
