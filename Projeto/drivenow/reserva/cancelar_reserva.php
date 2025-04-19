<?php
require_once '../includes/auth.php';

if (!estaLogado() || !isset($_GET['id'])) {
    header('Location: login.php');
    exit;
}

$reservaId = $_GET['id'];
$usuario = getUsuario();

// Verificar se a reserva pertence ao usuário
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM reserva WHERE id = ? AND conta_usuario_id = ?");
$stmt->execute([$reservaId, $usuario['id']]);
$reserva = $stmt->fetch();

if (!$reserva) {
    header('Location: minhas_reservas.php');
    exit;
}

// Excluir a reserva
try {
    $stmt = $pdo->prepare("DELETE FROM reserva WHERE id = ?");
    $stmt->execute([$reservaId]);
    
    $_SESSION['sucesso'] = 'Reserva cancelada com sucesso.';
} catch (PDOException $e) {
    $_SESSION['erro'] = 'Erro ao cancelar reserva: ' . $e->getMessage();
}

header('Location: minhas_reservas.php');
exit;