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

if (!$dono) {
    header('Location: ../dashboard.php?erro=' . urlencode('Acesso restrito a proprietários'));
    exit;
}

// Processar confirmação de reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserva_id']) && isset($_POST['acao'])) {
    $reservaId = $_POST['reserva_id'];
    $acao = $_POST['acao'];
    
    // Verificar se a reserva pertence a um veículo do dono
    $stmt = $pdo->prepare("SELECT r.id FROM reserva r JOIN veiculo v ON r.veiculo_id = v.id WHERE r.id = ? AND v.dono_id = ?");
    $stmt->execute([$reservaId, $dono['id']]);
    $reservaValida = $stmt->fetch();
    
    if ($reservaValida) {
        if ($acao === 'confirmar') {
            $status = 'confirmada';
        } elseif ($acao === 'rejeitar') {
            $status = 'rejeitada';
        } elseif ($acao === 'finalizar') {
            $status = 'finalizada';
        }
        
        $stmt = $pdo->prepare("UPDATE reserva SET status = ? WHERE id = ?");
        $stmt->execute([$status, $reservaId]);
        
        header('Location: reservas_recebidas.php?sucesso=' . urlencode("Reserva {$status} com sucesso!"));
        exit;
    }
}

// Aba ativa
$aba = isset($_GET['aba']) ? $_GET['aba'] : 'pendentes';

// Buscar reservas dos veículos do dono de acordo com a aba selecionada
$query = "SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_placa,
          CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_locatario,
          u.telefone AS telefone_locatario
          FROM reserva r
          JOIN veiculo v ON r.veiculo_id = v.id
          JOIN conta_usuario u ON r.conta_usuario_id = u.id
          WHERE v.dono_id = ?";

switch ($aba) {
    case 'pendentes':
        $query .= " AND (r.status IS NULL OR r.status = 'pendente')";
        break;
    case 'confirmadas':
        $query .= " AND r.status = 'confirmada'";
        break;
    case 'andamento':
        $query .= " AND r.status = 'confirmada' AND r.reserva_data <= CURRENT_DATE() AND r.devolucao_data >= CURRENT_DATE()";
        break;
    case 'finalizadas':
        $query .= " AND (r.status = 'finalizada' OR (r.status = 'confirmada' AND r.devolucao_data < CURRENT_DATE()))";
        break;
    case 'rejeitadas':
        $query .= " AND r.status = 'rejeitada'";
        break;
    case 'todas':
    default:
        // Não filtra por status
        break;
}

$query .= " ORDER BY r.reserva_data DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$dono['id']]);
$reservas = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reservas Recebidas</h2>
        <a href="../dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
    </div>
    
    <?php if (isset($_GET['sucesso'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['sucesso']) ?></div>
    <?php endif; ?>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $aba === 'pendentes' ? 'active' : '' ?>" href="?aba=pendentes">Pendentes</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $aba === 'confirmadas' ? 'active' : '' ?>" href="?aba=confirmadas">Confirmadas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $aba === 'andamento' ? 'active' : '' ?>" href="?aba=andamento">Em Andamento</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $aba === 'finalizadas' ? 'active' : '' ?>" href="?aba=finalizadas">Finalizadas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $aba === 'rejeitadas' ? 'active' : '' ?>" href="?aba=rejeitadas">Rejeitadas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $aba === 'todas' ? 'active' : '' ?>" href="?aba=todas">Todas</a>
        </li>
    </ul>
    
    <?php if (empty($reservas)): ?>
        <div class="alert alert-info">
            Nenhuma reserva encontrada nesta categoria.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Veículo</th>
                        <th>Locatário</th>
                        <th>Contato</th>
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
                        
                        // Definir status e classe com base no status do banco ou datas
                        if (!empty($reserva['status'])) {
                            $status = ucfirst($reserva['status']);
                            if ($reserva['status'] === 'confirmada') {
                                if ($now >= $inicio && $now <= $fim) {
                                    $status = 'Em Andamento';
                                    $statusClass = 'bg-warning';
                                } else if ($now > $fim) {
                                    $status = 'Finalizada';
                                    $statusClass = 'bg-secondary';
                                } else {
                                    $status = 'Confirmada';
                                    $statusClass = 'bg-success';
                                }
                            } elseif ($reserva['status'] === 'rejeitada') {
                                $statusClass = 'bg-danger';
                            } elseif ($reserva['status'] === 'finalizada') {
                                $statusClass = 'bg-secondary';
                            } else {
                                $statusClass = 'bg-primary';
                            }
                        } else {
                            $status = 'Pendente';
                            $statusClass = 'bg-primary';
                        }
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($reserva['veiculo_marca']) ?> <?= htmlspecialchars($reserva['veiculo_modelo']) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($reserva['veiculo_placa']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($reserva['nome_locatario']) ?></td>
                            <td><?= htmlspecialchars($reserva['telefone_locatario']) ?></td>
                            <td>
                                <?= date('d/m/Y', strtotime($reserva['reserva_data'])) ?> - 
                                <?= date('d/m/Y', strtotime($reserva['devolucao_data'])) ?>
                                <br><small><?= date('H:i', strtotime($reserva['reserva_data'])) ?> às <?= date('H:i', strtotime($reserva['devolucao_data'])) ?></small>
                            </td>
                            <td>
                                R$ <?= number_format($reserva['valor_total'], 2, ',', '.') ?>
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?>"><?= $status ?></span>
                            </td>
                            <td>
                                <?php if (empty($reserva['status']) || $reserva['status'] === 'pendente'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                        <input type="hidden" name="acao" value="confirmar">
                                        <button type="submit" class="btn btn-sm btn-success" 
                                                onclick="return confirm('Confirmar esta reserva?')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                        <input type="hidden" name="acao" value="rejeitar">
                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Rejeitar esta reserva?')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                <?php elseif ($reserva['status'] === 'confirmada' && $now >= $inicio && $now <= $fim): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="reserva_id" value="<?= $reserva['id'] ?>">
                                        <input type="hidden" name="acao" value="finalizar">
                                        <button type="submit" class="btn btn-sm btn-info" 
                                                onclick="return confirm('Finalizar esta reserva antecipadamente?')">
                                            <i class="bi bi-flag-fill"></i> Finalizar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="detalhes_reserva.php?id=<?= $reserva['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye-fill"></i>
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