<?php
require_once '../includes/auth.php';

// Verificar autenticação
verificarAutenticacao();

// Define a variável global $usuario para uso nas páginas
$usuario = getUsuario();

// Preparar a resposta
header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Erro desconhecido.'];

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    $response['message'] = 'Você precisa ser um proprietário para cadastrar veículos.';
    echo json_encode($response);
    exit;
}

// Se for uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'dono_id' => $dono['id'],
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
        'preco_diaria' => trim($_POST['preco_diaria']),
        'descricao' => trim($_POST['descricao'] ?? '')
    ];
    
    // Verificar se a placa já existe
    $stmt = $pdo->prepare("SELECT id FROM veiculo WHERE veiculo_placa = ?");
    $stmt->execute([$dados['veiculo_placa']]);
    if ($stmt->rowCount() > 0) {
        $response['message'] = 'Esta placa já está cadastrada no sistema.';
        echo json_encode($response);
        exit;
    }
    
    // Validações
    if (empty($dados['veiculo_marca']) || empty($dados['veiculo_modelo']) || empty($dados['veiculo_ano']) || empty($dados['veiculo_placa']) || empty($dados['veiculo_km']) || empty($dados['veiculo_cambio']) || empty($dados['veiculo_combustivel']) || empty($dados['veiculo_portas']) || empty($dados['veiculo_acentos']) || empty($dados['veiculo_tracao'])) {
        $response['message'] = 'Todos os campos são obrigatórios.';
    } elseif (!is_numeric($dados['veiculo_ano']) || $dados['veiculo_ano'] < 1900 || $dados['veiculo_ano'] > date('Y') + 1) {
        $response['message'] = 'Ano do veículo inválido.';
    } elseif (!is_numeric($dados['preco_diaria']) || $dados['preco_diaria'] <= 0) {
        $response['message'] = 'O preço diário deve ser um valor numérico positivo.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO veiculo 
                (dono_id, veiculo_marca, veiculo_modelo, veiculo_ano, veiculo_placa, veiculo_km, veiculo_cambio,
                veiculo_combustivel, veiculo_portas, veiculo_acentos, veiculo_tracao,
                local_id, categoria_veiculo_id, preco_diaria, descricao) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute(array_values($dados));
            
            $response['status'] = 'success';
            $response['message'] = 'Veículo cadastrado com sucesso!';
        } catch (PDOException $e) {
            $response['message'] = 'Erro ao cadastrar veículo: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);