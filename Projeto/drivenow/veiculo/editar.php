<?php
require_once '../includes/auth.php';

verificarAutenticacao();

$usuario = getUsuario();

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    header('Location: veiculos.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: veiculos.php');
    exit;
}

$veiculoId = $_GET['id'];

// Buscar veículo
$stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local, 
                      l.cidade_id, ci.estado_id
                      FROM veiculo v
                      LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                      LEFT JOIN local l ON v.local_id = l.id
                      LEFT JOIN cidade ci ON l.cidade_id = ci.id
                      WHERE v.id = ? AND v.dono_id = ?");
$stmt->execute([$veiculoId, $dono['id']]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: veiculos.php');
    exit;
}

// Armazenar os dados do veículo na sessão
$_SESSION['editando_veiculo'] = $veiculo;

// Redirecionar para a página veiculos.php com parâmetro para abrir o modal
header('Location: veiculos.php?openModal=veiculos&editando=' . $veiculoId);
exit;
?>