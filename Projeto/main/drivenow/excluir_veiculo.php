<?php
require_once 'includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();

if (!isset($_GET['id'])) {
    header('Location: meus_veiculos.php');
    exit;
}

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    header('Location: meus_veiculos.php');
    exit;
}

$veiculoId = $_GET['id'];

// Verificar se o veículo pertence ao dono
$stmt = $pdo->prepare("SELECT id FROM veiculo WHERE id = ? AND dono_id = ?");
$stmt->execute([$veiculoId, $dono['id']]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: meus_veiculos.php');
    exit;
}

// Excluir o veículo
try {
    $stmt = $pdo->prepare("DELETE FROM veiculo WHERE id = ?");
    $stmt->execute([$veiculoId]);
    
    $_SESSION['sucesso'] = 'Veículo excluído com sucesso!';
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao excluir veículo: ' . $e->getMessage();
}

header('Location: meus_veiculos.php');
exit;