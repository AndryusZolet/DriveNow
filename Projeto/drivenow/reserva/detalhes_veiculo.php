<?php
require_once '../includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: listagem_veiculos.php');
    exit;
}

$veiculoId = $_GET['id'];

// Buscar detalhes do veículo
global $pdo;
$stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local, 
                      CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario
                      FROM veiculo v
                      LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                      LEFT JOIN local l ON v.local_id = l.id
                      LEFT JOIN dono d ON v.dono_id = d.id
                      LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
                      WHERE v.id = ?");
$stmt->execute([$veiculoId]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: listagem_veiculos.php');
    exit;
}

// Usar o preço diário do veículo
$diariaValor = $veiculo['preco_diaria'];
$taxaUso = 20.00;
$taxaLimpeza = 30.00;

$erro = '';
$sucesso = '';

// Processar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && estaLogado()) {
    $reservaData = $_POST['reserva_data'];
    $devolucaoData = $_POST['devolucao_data'];
    $observacoes = trim($_POST['observacoes']);
    
    // Validações
    if (empty($reservaData) || empty($devolucaoData)) {
        $erro = 'Datas de reserva e devolução são obrigatórias.';
    } elseif (strtotime($devolucaoData) <= strtotime($reservaData)) {
        $erro = 'A data de devolução deve ser posterior à data de reserva.';
    } else {
        try {
            $usuario = getUsuario();
            
            // Calcular valor total
            $dias = (strtotime($devolucaoData) - strtotime($reservaData)) / (60 * 60 * 24);
            $valorTotal = ($diariaValor * $dias) + $taxaUso + $taxaLimpeza;
            
            $stmt = $pdo->prepare("INSERT INTO reserva 
                                  (veiculo_id, conta_usuario_id, reserva_data, devolucao_data, 
                                   diaria_valor, taxas_de_uso, taxas_de_limpeza, valor_total)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $veiculoId,
                $usuario['id'],
                $reservaData,
                $devolucaoData,
                $diariaValor,
                $taxaUso,
                $taxaLimpeza,
                $valorTotal
            ]);
            
            $sucesso = 'Reserva realizada com sucesso! Valor total: R$ ' . number_format($valorTotal, 2, ',', '.');
        } catch (PDOException $e) {
            $erro = 'Erro ao processar reserva: ' . $e->getMessage();
        }
    }
}
?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?>
                        <span class="float-end">R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?>/dia</span>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="bg-secondary text-white text-center py-5 mb-3 rounded">
                                <i class="bi bi-car-front" style="font-size: 4rem;"></i>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <!-- Seção de Descrição do Veículo -->
                            <div class="mb-4">
                                <h5>Descrição do Veículo</h5>
                                <div class="border p-3 rounded bg-light">
                                    <?php if (!empty($veiculo['descricao'])): ?>
                                        <?= nl2br(htmlspecialchars($veiculo['descricao'])) ?>
                                    <?php else: ?>
                                        <p class="text-muted">O proprietário não forneceu uma descrição para este veículo.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Proprietário:</strong> <?= htmlspecialchars($veiculo['nome_proprietario']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Ano:</strong> <?= htmlspecialchars($veiculo['veiculo_ano']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Placa:</strong> <?= htmlspecialchars($veiculo['veiculo_placa']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Quilometragem:</strong> <?= number_format($veiculo['veiculo_km'], 0, ',', '.') ?> km
                                </li>
                                <li class="list-group-item">
                                    <strong>Câmbio:</strong> <?= htmlspecialchars($veiculo['veiculo_cambio']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Combustível:</strong> <?= htmlspecialchars($veiculo['veiculo_combustivel']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Portas:</strong> <?= htmlspecialchars($veiculo['veiculo_portas']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Assentos:</strong> <?= htmlspecialchars($veiculo['veiculo_acentos']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Tração:</strong> <?= htmlspecialchars($veiculo['veiculo_tracao']) ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Categoria:</strong> <?= htmlspecialchars($veiculo['categoria'] ?? 'Não informada') ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Localização:</strong> <?= htmlspecialchars($veiculo['nome_local'] ?? 'Não informada') ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Solicitar Reserva</h5>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                        <a href="../vboard.php" class="btn btn-primary w-100">Voltar ao Dashboard</a>
                    <?php else: ?>
                        <?php if (estaLogado()): ?>
                            <?php if (usuarioPodeReservar()): ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="reserva_data" class="form-label">Data de Reserva</label>
                                        <input type="date" class="form-control" id="reserva_data" name="reserva_data" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="devolucao_data" class="form-label">Data de Devolução</label>
                                        <input type="date" class="form-control" id="devolucao_data" name="devolucao_data" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="observacoes" class="form-label">Observações</label>
                                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                                    </div>
                                    <div class="alert alert-info">
                                        <strong>Preço diário:</strong> R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Solicitar Reserva</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    Você não pode fazer reservas neste veículo. 
                                    <a href="../vboard.php" class="alert-link">Voltar ao Dashboard</a>.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Você precisa estar logado para fazer uma reserva.
                                <a href="../login.php" class="alert-link">Faça login</a> ou 
                                <a href="../cadastro.php" class="alert-link">cadastre-se</a>.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configurar datas mínimas no formulário
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('reserva_data').min = today;
    
    document.getElementById('reserva_data').addEventListener('change', function() {
        document.getElementById('devolucao_data').min = this.value;
    });
});
</script>