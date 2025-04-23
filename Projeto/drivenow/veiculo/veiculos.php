<?php
require_once '../includes/auth.php';

if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

// Verificar se a coluna 'disponivel' existe na tabela veiculo
$columnExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM veiculo LIKE 'disponivel'");
    $columnExists = ($stmt->rowCount() > 0);
} catch (PDOException $e) {
    // Se houver erro, considerar que a coluna não existe
    $columnExists = false;
}

if (!$dono) {
    $veiculos = [];
} else {
    // Buscar veículos do dono com informações de categoria e local
    $sql = "SELECT v.*, c.categoria, l.nome_local" . 
          ($columnExists ? ", v.disponivel" : "") . 
          " FROM veiculo v
          LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
          LEFT JOIN local l ON v.local_id = l.id
          WHERE v.dono_id = ? 
          ORDER BY v.id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dono['id']]);
    $veiculos = $stmt->fetchAll();
}

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Meus Veículos</h2>
        <?php if ($dono): ?>
            <div class="d-flex gap-2">
                <a href="./cadastro.php" class="btn btn-success">Adicionar Veículo</a>
                <a href="../dashboard.php" class="btn btn-danger">Voltar</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($veiculos)): ?>
        <div class="alert alert-info">
            <?php if ($dono): ?>
                Você ainda não possui veículos cadastrados.
            <?php else: ?>
                Você não é registrado como proprietário de veículos.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Placa</th>
                        <th>Ano</th>
                        <th>KM</th>
                        <th>Câmbio</th>
                        <th>Combustível</th>
                        <th>Tração</th>
                        <th>Categoria</th>
                        <th>Localização</th>
                        <th>Diário</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($veiculos as $veiculo): 
                        // Definir disponivel como 1 (disponível) por padrão se não existir
                        $disponivel = $veiculo['disponivel'] ?? 1;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($veiculo['veiculo_marca']) ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_modelo']) ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_placa']) ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_ano']) ?></td>
                            <td><?= $veiculo['veiculo_km'] ? number_format($veiculo['veiculo_km'], 0, ',', '.') . ' km' : '-' ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_cambio']) ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_combustivel']) ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_tracao']) ?></td>
                            <td><?= htmlspecialchars($veiculo['categoria'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($veiculo['nome_local'] ?? '-') ?></td>
                            <td>R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($disponivel == 1): ?>
                                    <span class="badge bg-success">Disponível</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Indisponível</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a href="./editar.php?id=<?= $veiculo['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="./ativar.php?id=<?= $veiculo['id'] ?>&status=<?= $disponivel == 1 ? 0 : 1 ?>" 
                                       class="btn btn-sm <?= $disponivel == 1 ? 'btn-secondary' : 'btn-info' ?>">
                                        <i class="bi bi-power"></i> <?= $disponivel == 1 ? 'Desativar' : 'Ativar' ?>
                                    </a>
                                    <a href="./excluir.php?id=<?= $veiculo['id'] ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir este veículo?')">
                                        <i class="bi bi-trash"></i> Excluir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>