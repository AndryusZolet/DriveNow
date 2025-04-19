<?php
require_once '../includes/auth.php';
require_once '../includes/header.php';

// Buscar veículos disponíveis que não têm reservas ativas
global $pdo;
$stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local, 
                      CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario
                      FROM veiculo v
                      LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                      LEFT JOIN local l ON v.local_id = l.id
                      LEFT JOIN dono d ON v.dono_id = d.id
                      LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
                      WHERE v.id NOT IN (
                          SELECT veiculo_id FROM reserva 
                          WHERE (
                              (reserva_data BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))
                              OR 
                              (devolucao_data BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))
                              OR
                              (reserva_data <= CURRENT_DATE AND devolucao_data >= CURRENT_DATE)
                          )
                      )
                      ORDER BY v.id DESC");
$stmt->execute();
$veiculos = $stmt->fetchAll();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Veículos Disponíveis para Aluguel</h2>
        <a href="../dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
    </div>
    
    <div class="row">
        <?php if (empty($veiculos)): ?>
            <div class="col-12">
                <div class="alert alert-info">Nenhum veículo disponível no momento.</div>
            </div>
        <?php else: ?>
            <?php foreach ($veiculos as $veiculo): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-img-top bg-secondary text-white text-center py-4" 
                             style="height: 180px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-car-front" style="font-size: 3rem;"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?>
                            </h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> <?= htmlspecialchars($veiculo['veiculo_ano']) ?> | 
                                    <i class="bi bi-speedometer2"></i> <?= number_format($veiculo['veiculo_km'], 0, ',', '.') ?> km
                                </small>
                            </p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item">
                                    <i class="bi bi-gear"></i> <?= htmlspecialchars($veiculo['veiculo_cambio']) ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-fuel-pump"></i> <?= htmlspecialchars($veiculo['veiculo_combustivel']) ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($veiculo['nome_local'] ?? 'Local não informado') ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-person"></i> <?= htmlspecialchars($veiculo['nome_proprietario']) ?>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="detalhes_veiculo.php?id=<?= $veiculo['id'] ?>" class="btn btn-primary w-100">
                                Ver Detalhes e Reservar
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>