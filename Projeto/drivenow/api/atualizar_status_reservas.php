<?php
/**
 * Script para atualização automática de status de reservas
 * 
 * Este script deve ser executado regularmente através de um cronjob
 * para atualizar automaticamente o status das reservas com base na data
 */

require_once __DIR__ . '/../config/db.php';

// Registrar execução do script
$log = "Iniciando atualização de status de reservas: " . date('Y-m-d H:i:s') . "\n";

// 1. Atualizar reservas pendentes que já passaram da data de início para "rejeitadas"
try {
    $stmt = $pdo->prepare("
        UPDATE reserva
        SET status = 'rejeitada'
        WHERE (status IS NULL OR status = 'pendente')
        AND reserva_data < CURRENT_DATE()
    ");
    
    $stmt->execute();
    $log .= "Reservas pendentes com data passada atualizadas para rejeitadas: " . $stmt->rowCount() . "\n";
} catch (PDOException $e) {
    $log .= "ERRO ao atualizar reservas pendentes: " . $e->getMessage() . "\n";
}

// 2. Atualizar reservas confirmadas que já passaram da data de fim para "finalizadas"
try {
    $stmt = $pdo->prepare("
        UPDATE reserva
        SET status = 'finalizada'
        WHERE status = 'confirmada'
        AND devolucao_data < CURRENT_DATE()
    ");
    
    $stmt->execute();
    $log .= "Reservas confirmadas com data passada atualizadas para finalizadas: " . $stmt->rowCount() . "\n";
} catch (PDOException $e) {
    $log .= "ERRO ao atualizar reservas confirmadas: " . $e->getMessage() . "\n";
}

// Finalizar log
$log .= "Atualização concluída em: " . date('Y-m-d H:i:s') . "\n";
$log .= "----------------------------------------\n";

// Salvar log em arquivo
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

file_put_contents(
    $logDir . '/atualizacao_reservas.log',
    $log,
    FILE_APPEND
);

// Se executado via CLI, exibir resultado
if (php_sapi_name() === 'cli') {
    echo $log;
}

// Retornar feedback para chamadas HTTP
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Atualização de reservas concluída',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
