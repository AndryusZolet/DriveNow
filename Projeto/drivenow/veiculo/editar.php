<?php
require_once '../includes/auth.php';

if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();
$erro = '';
$sucesso = '';

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
$stmt = $pdo->prepare("SELECT v.*, c.categoria, l.nome_local 
                      FROM veiculo v
                      LEFT JOIN categoria_veiculo c ON v.categoria_veiculo_id = c.id
                      LEFT JOIN local l ON v.local_id = l.id
                      WHERE v.id = ? AND v.dono_id = ?");
$stmt->execute([$veiculoId, $dono['id']]);
$veiculo = $stmt->fetch();

if (!$veiculo) {
    header('Location: veiculos.php');
    exit;
}

$categorias = $pdo->query("SELECT * FROM categoria_veiculo")->fetchAll();
$locais = $pdo->query("SELECT * FROM local")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'veiculo_marca' => trim($_POST['veiculo_marca']),
        'veiculo_modelo' => trim($_POST['veiculo_modelo']),
        'veiculo_ano' => trim($_POST['veiculo_ano']),
        'veiculo_placa' => trim($_POST['veiculo_placa']),
        'veiculo_km' => trim($_POST['veiculo_km']),
        'veiculo_cambio' => trim($_POST['veiculo_cambio']),
        'veiculo_combustivel' => trim($_POST['veiculo_combustivel']),
        'veiculo_portas' => trim($_POST['veiculo_portas']),
        'veiculo_acentos' => trim($_POST['veiculo_acentos']),
        'veiculo_tracao' => trim($_POST['veiculo_tracao']),
        'local_id' => !empty($_POST['local_id']) ? $_POST['local_id'] : null,
        'categoria_veiculo_id' => !empty($_POST['categoria_veiculo_id']) ? $_POST['categoria_veiculo_id'] : null,
        'veiculo_preco_diaria' => trim($_POST['veiculo_preco_diaria']),
        'descricao' => trim($_POST['descricao'] ?? ''),
        'id' => $veiculoId,
        'dono_id' => $dono['id']
    ];
    
    // Validações
    if (empty($dados['veiculo_marca']) || empty($dados['veiculo_modelo']) || empty($dados['veiculo_placa']) || 
        empty($dados['veiculo_ano']) || empty($dados['veiculo_km']) || empty($dados['veiculo_cambio']) || 
        empty($dados['veiculo_combustivel']) || empty($dados['veiculo_portas']) || 
        empty($dados['veiculo_acentos']) || empty($dados['veiculo_tracao'])) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (!is_numeric($dados['veiculo_ano']) || $dados['veiculo_ano'] < 1900 || $dados['veiculo_ano'] > date('Y') + 1) {
        $erro = 'Ano do veículo inválido.';
    } elseif (!is_numeric($dados['veiculo_preco_diaria']) || $dados['veiculo_preco_diaria'] <= 0) {
        $erro = 'O preço diário deve ser um valor numérico positivo.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE veiculo SET 
                                  veiculo_marca = ?, 
                                  veiculo_modelo = ?, 
                                  veiculo_placa = ?, 
                                  veiculo_ano = ?, 
                                  veiculo_km = ?, 
                                  veiculo_cambio = ?,
                                  veiculo_combustivel = ?, 
                                  veiculo_portas = ?, 
                                  veiculo_acentos = ?, 
                                  veiculo_tracao = ?,
                                  local_id = ?, 
                                  categoria_veiculo_id = ?, 
                                  preco_diaria = ?, 
                                  descricao = ?
                                  WHERE id = ? AND dono_id = ?");
            
            $stmt->execute([
                $dados['veiculo_marca'],
                $dados['veiculo_modelo'],
                $dados['veiculo_placa'],
                $dados['veiculo_ano'],
                $dados['veiculo_km'],
                $dados['veiculo_cambio'],
                $dados['veiculo_combustivel'],
                $dados['veiculo_portas'],
                $dados['veiculo_acentos'],
                $dados['veiculo_tracao'],
                $dados['local_id'],
                $dados['categoria_veiculo_id'],
                $dados['veiculo_preco_diaria'],
                $dados['descricao'],
                $dados['id'],
                $dados['dono_id']
            ]);
            
            $sucesso = 'Veículo atualizado com sucesso!';
            $veiculo = array_merge($veiculo, $dados);
        } catch (PDOException $e) {
            $erro = 'Erro ao atualizar veículo: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Editar Veículo</h4>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_marca" class="form-label">Nome do Veículo</label>
                                <!-- <input type="text" class="form-control" id="veiculo_nome" name="veiculo_nome" required> -->
                                <select class="form-select" id="veiculo_marca" name="veiculo_marca" required>
                                    <option value="">Selecione uma marca</option>
                                    <option value="Chevrolet" <?= $veiculo['veiculo_marca'] === 'Chevrolet' ? 'selected' : '' ?>>Chevrolet</option>
                                    <option value="Fiat" <?= $veiculo['veiculo_marca'] === 'Fiat' ? 'selected' : '' ?>>Fiat</option>
                                    <option value="Ford" <?= $veiculo['veiculo_marca'] === 'Ford' ? 'selected' : '' ?>>Ford</option>
                                    <option value="Volkswagen" <?= $veiculo['veiculo_marca'] === 'Volkswagen' ? 'selected' : '' ?>>Volkswagen</option>
                                    <option value="Toyota" <?= $veiculo['veiculo_marca'] === 'Toyota' ? 'selected' : '' ?>>Toyota</option>
                                    <option value="Hyundai" <?= $veiculo['veiculo_marca'] === 'Hyundai' ? 'selected' : '' ?>>Hyundai</option>
                                    <option value="Honda" <?= $veiculo['veiculo_marca'] === 'Honda' ? 'selected' : '' ?>>Honda</option>
                                    <option value="Renault" <?= $veiculo['veiculo_marca'] === 'Renault' ? 'selected' : '' ?>>Renault</option>
                                    <option value="Nissan" <?= $veiculo['veiculo_marca'] === 'Nissan' ? 'selected' : '' ?>>Nissan</option>
                                    <option value="Peugeot" <?= $veiculo['veiculo_marca'] === 'Peugeot' ? 'selected' : '' ?>>Peugeot</option>
                                    <option value="Citroën" <?= $veiculo['veiculo_marca'] === 'Citroën' ? 'selected' : '' ?>>Citroën</option>
                                    <option value="Jeep" <?= $veiculo['veiculo_marca'] === 'Jeep' ? 'selected' : '' ?>>Jeep</option>
                                    <option value="Mitsubishi" <?= $veiculo['veiculo_marca'] === 'Mitsubishi' ? 'selected' : '' ?>>Mitsubishi</option>
                                    <option value="Kia" <?= $veiculo['veiculo_marca'] === 'Kia' ? 'selected' : '' ?>>Kia</option>
                                    <option value="Mercedes-Benz" <?= $veiculo['veiculo_marca'] === 'Mercedes-Benz' ? 'selected' : '' ?>>Mercedes-Benz</option>
                                    <option value="BMW" <?= $veiculo['veiculo_marca'] === 'BMW' ? 'selected' : '' ?>>BMW</option>
                                    <option value="Audi" <?= $veiculo['veiculo_marca'] === 'Audi' ? 'selected' : '' ?>>Audi</option>
                                    <option value="BYD" <?= $veiculo['veiculo_marca'] === 'BYD' ? 'selected' : '' ?>>BYD</option>
                                    <option value="Chery" <?= $veiculo['veiculo_marca'] === 'Chery' ? 'selected' : '' ?>>Chery</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="veiculo_modelo" class="form-label">Modelo do Veículo</label>
                                <select class="form-select" id="veiculo_modelo" name="veiculo_modelo" 
                                        required data-selected="<?= htmlspecialchars($veiculo['veiculo_modelo']) ?>">
                                    <option value="">Selecione o modelo</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="veiculo_ano" class="form-label">Ano</label>
                                <input type="number" class="form-control" id="veiculo_ano" name="veiculo_ano" 
                                       value="<?= htmlspecialchars($veiculo['veiculo_ano']) ?>" 
                                       min="1900" max="<?= date('Y') + 1 ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="veiculo_placa" class="form-label">Placa do Veículo</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="veiculo_placa" 
                                    name="veiculo_placa" 
                                    value="<?= htmlspecialchars($veiculo['veiculo_placa']) ?>" 
                                    required
                                    maxlength="8"
                                    oninput="this.value = this.value.toUpperCase();"
                                >
                                <div class="invalid-feedback">
                                    Digite uma placa válida (Ex: ABC-1234 ou ABC1D23).
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label for="veiculo_km" class="form-label">Quilometragem</label>
                                <input type="number" class="form-control" id="veiculo_km" name="veiculo_km" 
                                       value="<?= htmlspecialchars($veiculo['veiculo_km']) ?>" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_cambio" class="form-label">Câmbio</label>
                                <select class="form-select" id="veiculo_cambio" name="veiculo_cambio">
                                    <option value="Automático" <?= $veiculo['veiculo_cambio'] === 'Automático' ? 'selected' : '' ?>>Automático</option>
                                    <option value="Manual" <?= $veiculo['veiculo_cambio'] === 'Manual' ? 'selected' : '' ?>>Manual</option>
                                    <option value="CVT" <?= $veiculo['veiculo_cambio'] === 'CVT' ? 'selected' : '' ?>>CVT</option>
                                    <option value="Semi-automático" <?= $veiculo['veiculo_cambio'] === 'Semi-automático' ? 'selected' : '' ?>>Semi-automático</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_combustivel" class="form-label">Combustível</label>
                                <select class="form-select" id="veiculo_combustivel" name="veiculo_combustivel">
                                    <option value="Gasolina" <?= $veiculo['veiculo_combustivel'] === 'Gasolina' ? 'selected' : '' ?>>Gasolina</option>
                                    <option value="Álcool" <?= $veiculo['veiculo_combustivel'] === 'Álcool' ? 'selected' : '' ?>>Álcool</option>
                                    <option value="Diesel" <?= $veiculo['veiculo_combustivel'] === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                                    <option value="Flex" <?= $veiculo['veiculo_combustivel'] === 'Flex' ? 'selected' : '' ?>>Flex</option>
                                    <option value="Elétrico" <?= $veiculo['veiculo_combustivel'] === 'Elétrico' ? 'selected' : '' ?>>Elétrico</option>
                                    <option value="Híbrido" <?= $veiculo['veiculo_combustivel'] === 'Híbrido' ? 'selected' : '' ?>>Híbrido</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_tracao" class="form-label">Tração</label>
                                <select class="form-select" id="veiculo_tracao" name="veiculo_tracao">
                                    <option value="Dianteira" <?= $veiculo['veiculo_tracao'] === 'Dianteira' ? 'selected' : '' ?>>Dianteira</option>
                                    <option value="Traseira" <?= $veiculo['veiculo_tracao'] === 'Traseira' ? 'selected' : '' ?>>Traseira</option>
                                    <option value="4x4" <?= $veiculo['veiculo_tracao'] === '4x4' ? 'selected' : '' ?>>4x4</option>
                                    <option value="AWD" <?= $veiculo['veiculo_tracao'] === 'AWD' ? 'selected' : '' ?>>AWD (Integral)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_portas" class="form-label">Número de Portas</label>
                                <input type="number" class="form-control" id="veiculo_portas" name="veiculo_portas" 
                                       value="<?= htmlspecialchars($veiculo['veiculo_portas']) ?>" min="2" max="6">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_acentos" class="form-label">Número de Assentos</label>
                                <input type="number" class="form-control" id="veiculo_acentos" name="veiculo_acentos" 
                                       value="<?= htmlspecialchars($veiculo['veiculo_acentos']) ?>" min="2" max="9">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="categoria_veiculo_id" class="form-label">Categoria</label>
                                <select class="form-select" id="categoria_veiculo_id" name="categoria_veiculo_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>" 
                                            <?= $veiculo['categoria_veiculo_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['categoria']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="local_id" class="form-label">Localização</label>
                                <select class="form-select" id="local_id" name="local_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($locais as $local): ?>
                                        <option value="<?= $local['id'] ?>" 
                                            <?= $veiculo['local_id'] == $local['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($local['nome_local']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="veiculo_preco_diaria" class="form-label">Preço Diário (R$)</label>
                                    <input type="number" class="form-control" id="veiculo_preco_diaria" name="veiculo_preco_diaria" 
                                           value="<?= htmlspecialchars($veiculo['preco_diaria'] ?? '150.00') ?>" 
                                           min="50" step="0.01" required>
                                    <small class="text-muted">Valor por dia de aluguel</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="descricao" class="form-label">Descrição do Veículo</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="4"
                                              placeholder="Descreva detalhes do veículo (estado de conservação, equipamentos, etc.)"><?= htmlspecialchars($veiculo['descricao'] ?? '') ?></textarea>
                                    <small class="text-muted">Esta descrição será exibida para os clientes na página de detalhes do veículo.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            <a href="./veiculos.php" class="btn btn-secondary">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/veiculos.js"></script>

<?php require_once '../includes/footer.php'; ?>