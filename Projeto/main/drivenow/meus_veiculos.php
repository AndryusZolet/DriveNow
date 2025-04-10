<?php
require_once 'includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    $veiculos = [];
} else {
    // Buscar veículos do dono com informações de categoria e local
    $stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local 
                          FROM veiculo v
                          LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                          LEFT JOIN local l ON v.local_id = l.id
                          WHERE v.dono_id = ? 
                          ORDER BY v.id DESC");
    $stmt->execute([$dono['id']]);
    $veiculos = $stmt->fetchAll();
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Meus Veículos</h2>
        <?php if ($dono): ?>
            <a href="cadastro_veiculo.php" class="btn btn-success">Adicionar Veículo</a>
            <a href="dashboard.php" class="btn btn-danger">Voltar</a>
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
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Ano</th>
                        <th>KM</th>
                        <th>Categoria</th>
                        <th>Localização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($veiculos as $veiculo): ?>
                        <tr>
                            <td><?= htmlspecialchars($veiculo['veiculo_nome']) ?></td>
                            <td><?= htmlspecialchars($veiculo['veiculo_ano']) ?></td>
                            <td><?= $veiculo['veiculo_km'] ? number_format($veiculo['veiculo_km'], 0, ',', '.') . ' km' : '-' ?></td>
                            <td><?= htmlspecialchars($veiculo['categoria'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($veiculo['nome_local'] ?? '-') ?></td>
                            <td>
                                <a href="editar_veiculo.php?id=<?= $veiculo['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="excluir_veiculo.php?id=<?= $veiculo['id'] ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir este veículo?')">
                                    <i class="bi bi-trash"></i> Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>