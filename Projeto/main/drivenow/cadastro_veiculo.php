<?php
require_once 'includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
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
    $erro = 'Você precisa ser um proprietário para cadastrar veículos.';
}

// Buscar categorias e locais disponíveis
$categorias = $pdo->query("SELECT * FROM categoria_veiculo")->fetchAll();
$locais = $pdo->query("SELECT * FROM local")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dono) {
    $dados = [
        'dono_id' => $dono['id'],
        'veiculo_nome' => trim($_POST['veiculo_nome']),
        'veiculo_ano' => trim($_POST['veiculo_ano']),
        'veiculo_km' => trim($_POST['veiculo_km']),
        'veiculo_cambio' => trim($_POST['veiculo_cambio']),
        'veiculo_combustivel' => trim($_POST['veiculo_combustivel']),
        'veiculo_portas' => trim($_POST['veiculo_portas']),
        'veiculo_acentos' => trim($_POST['veiculo_acentos']),
        'veiculo_tracao' => trim($_POST['veiculo_tracao']),
        'local_id' => !empty($_POST['local_id']) ? $_POST['local_id'] : null,
        'categoria_veiculo_id' => !empty($_POST['categoria_veiculo_id']) ? $_POST['categoria_veiculo_id'] : null
    ];
    
    // Validações
    if (empty($dados['veiculo_nome']) || empty($dados['veiculo_ano'])) {
        $erro = 'Nome e ano do veículo são obrigatórios.';
    } elseif (!is_numeric($dados['veiculo_ano']) || $dados['veiculo_ano'] < 1900 || $dados['veiculo_ano'] > date('Y') + 1) {
        $erro = 'Ano do veículo inválido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO veiculo 
                                  (dono_id, veiculo_nome, veiculo_ano, veiculo_km, veiculo_cambio, 
                                   veiculo_combustivel, veiculo_portas, veiculo_acentos, veiculo_tracao,
                                   local_id, categoria_veiculo_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_values($dados));
            
            $sucesso = 'Veículo cadastrado com sucesso!';
        } catch (PDOException $e) {
            $erro = 'Erro ao cadastrar veículo: ' . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Cadastrar Novo Veículo</h4>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($dono): ?>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="veiculo_nome" class="form-label">Nome do Veículo</label>
                                <input type="text" class="form-control" id="veiculo_nome" name="veiculo_nome" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_ano" class="form-label">Ano</label>
                                <input type="number" class="form-control" id="veiculo_ano" name="veiculo_ano" 
                                       min="1900" max="<?= date('Y') + 1 ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_km" class="form-label">Quilometragem</label>
                                <input type="number" class="form-control" id="veiculo_km" name="veiculo_km" min="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="veiculo_cambio" class="form-label">Câmbio</label>
                                <select class="form-select" id="veiculo_cambio" name="veiculo_cambio">
                                    <option value="Automático">Automático</option>
                                    <option value="Manual">Manual</option>
                                    <option value="CVT">CVT</option>
                                    <option value="Semi-automático">Semi-automático</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="veiculo_combustivel" class="form-label">Combustível</label>
                                <select class="form-select" id="veiculo_combustivel" name="veiculo_combustivel">
                                    <option value="Gasolina">Gasolina</option>
                                    <option value="Álcool">Álcool</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="Flex">Flex</option>
                                    <option value="Elétrico">Elétrico</option>
                                    <option value="Híbrido">Híbrido</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="veiculo_tracao" class="form-label">Tração</label>
                                <select class="form-select" id="veiculo_tracao" name="veiculo_tracao">
                                    <option value="Dianteira">Dianteira</option>
                                    <option value="Traseira">Traseira</option>
                                    <option value="4x4">4x4</option>
                                    <option value="AWD">AWD (Integral)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_portas" class="form-label">Número de Portas</label>
                                <input type="number" class="form-control" id="veiculo_portas" name="veiculo_portas" min="2" max="6">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="veiculo_acentos" class="form-label">Número de Assentos</label>
                                <input type="number" class="form-control" id="veiculo_acentos" name="veiculo_acentos" min="2" max="9">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="categoria_veiculo_id" class="form-label">Categoria</label>
                                <select class="form-select" id="categoria_veiculo_id" name="categoria_veiculo_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['categoria']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="local_id" class="form-label">Localização</label>
                                <select class="form-select" id="local_id" name="local_id">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($locais as $local): ?>
                                        <option value="<?= $local['id'] ?>"><?= htmlspecialchars($local['nome_local']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Cadastrar Veículo</button>
                            <a href="meus_veiculos.php" class="btn btn-secondary">Ver Meus Veículos</a>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Você precisa registrar-se como proprietário para cadastrar veículos.
                            <a href="registrar_dono.php" class="alert-link">Clique aqui para se registrar como proprietário</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>