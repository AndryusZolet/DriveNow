<?php
require_once '../includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();

// Buscar reservas do usuário
global $pdo;
$stmt = $pdo->prepare("SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_placa,
                      CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario
                      FROM reserva r
                      JOIN veiculo v ON r.veiculo_id = v.id
                      JOIN dono d ON v.dono_id = d.id
                      JOIN conta_usuario u ON d.conta_usuario_id = u.id
                      WHERE r.conta_usuario_id = ?
                      ORDER BY r.reserva_data DESC");
$stmt->execute([$usuario['id']]);
$reservas = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Minhas Reservas</h2>
        <div>
            <a href="../dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
            <a href="../listagem_veiculos.php" class="btn btn-primary ms-2">Nova Reserva</a>
        </div>
    </div>
    
    <?php if (empty($reservas)): ?>
        <div class="alert alert-info">
            Você ainda não fez nenhuma reserva.
            <a href="../listagem_veiculos.php" class="alert-link">Veja os veículos disponíveis</a>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Veículo</th>
                        <th>Proprietário</th>
                        <th>Período</th>
                        <th>Valor Total</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <?php
                        $now = time();
                        $inicio = strtotime($reserva['reserva_data']);
                        $fim = strtotime($reserva['devolucao_data']);
                        
                        if ($now < $inicio) {
                            $status = 'Agendada';
                            $statusClass = 'bg-primary';
                        } elseif ($now >= $inicio && $now <= $fim) {
                            $status = 'Em andamento';
                            $statusClass = 'bg-success';
                        } else {
                            $status = 'Concluída';
                            $statusClass = 'bg-secondary';
                        }
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($reserva['veiculo_placa']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($reserva['nome_proprietario']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?> - 
                                <?= date('d/m/Y', strtotime($reserva['devolucao_data'])) ?>
                            </td>
                            <td>
                                R$ <?= number_format($reserva['valor_total'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?>"><?= $status ?></span>
                            </td>
                            <td>
                                <a href="cancelar_reserva.php?id=<?= $reserva['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Tem certeza que deseja cancelar esta reserva?')">
                                    Cancelar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>