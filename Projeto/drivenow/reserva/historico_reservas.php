<?php
require_once '../includes/auth.php';

if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();

// Buscar histórico completo de reservas do usuário
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
        <h2>Histórico de Reservas</h2>
        <div>
            <a href="../dashboard.php" class="btn btn-secondary">Voltar ao Dashboard</a>
            <a href="listagem_veiculos.php" class="btn btn-primary ms-2">Nova Reserva</a>
        </div>
    </div>
    
    <?php if (empty($reservas)): ?>
        <div class="alert alert-info">
            Você ainda não fez nenhuma reserva.
            <a href="listagem_veiculos.php" class="alert-link">Buscar veículos disponíveis</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Veículo</th>
                        <th>Proprietário</th>
                        <th>Período</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Avaliação</th>
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
                            <td>R$ <?= number_format($reserva['valor_total'], 2, ',', '.') ?></td>
                            <td>
                                <span class="badge <?= $statusClass ?>"><?= $status ?></span>
                            </td>
                            <td>
                                <?php if ($status === 'Concluída'): ?>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#avaliacaoModal"
                                            data-reserva="<?= $reserva['id'] ?>">
                                        Avaliar
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Disponível após conclusão</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Avaliação -->
<div class="modal fade" id="avaliacaoModal" tabindex="-1" aria-labelledby="avaliacaoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="avaliacaoModalLabel">Avaliar Veículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAvaliacao">
                    <input type="hidden" name="reserva_id" id="reserva_id">
                    <div class="mb-3">
                        <label for="nota" class="form-label">Nota (1-5)</label>
                        <select class="form-select" name="nota" id="nota" required>
                            <option value="">Selecione...</option>
                            <option value="1">1 - Péssimo</option>
                            <option value="2">2 - Ruim</option>
                            <option value="3">3 - Regular</option>
                            <option value="4">4 - Bom</option>
                            <option value="5">5 - Excelente</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentário</label>
                        <textarea class="form-control" name="comentario" id="comentario" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarAvaliacao">Salvar Avaliação</button>
            </div>
        </div>
    </div>
</div>

<script>
// Configurar modal de avaliação
document.addEventListener('DOMContentLoaded', function() {
    var avaliacaoModal = document.getElementById('avaliacaoModal');
    if (avaliacaoModal) {
        avaliacaoModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var reservaId = button.getAttribute('data-reserva');
            var modalInput = avaliacaoModal.querySelector('#reserva_id');
            modalInput.value = reservaId;
        });
    }
    
    // Enviar avaliação via AJAX
    document.getElementById('btnSalvarAvaliacao').addEventListener('click', function() {
        const form = document.getElementById('formAvaliacao');
        const formData = new FormData(form);
        
        fetch('../api/avaliar_reserva.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Avaliação salva com sucesso!');
                location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocorreu um erro ao enviar a avaliação.');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>