<?php
require_once '../includes/auth.php';

verificarAutenticacao();

$usuario = getUsuario();
$resposta = ['status' => 'error', 'message' => 'Requisição inválida'];

// Verificar se o usuário é um dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if (!$dono) {
    $resposta['message'] = 'Você não tem permissão para executar esta ação.';
    echo json_encode($resposta);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['veiculo_id']) && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $veiculoId = $_POST['veiculo_id'];
    
    // Verificar se o veículo pertence ao dono
    $stmt = $pdo->prepare("SELECT id, veiculo_placa FROM veiculo WHERE id = ? AND dono_id = ?");
    $stmt->execute([$veiculoId, $dono['id']]);
    $veiculo = $stmt->fetch();
    
    if ($veiculo) {
        // Coletar os dados do formulário
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
            'preco_diaria' => trim($_POST['preco_diaria']),
            'descricao' => trim($_POST['descricao'] ?? '')
        ];
        
        // Validar dados
        if (empty($dados['veiculo_marca']) || empty($dados['veiculo_modelo']) || empty($dados['veiculo_placa']) || 
            empty($dados['veiculo_ano']) || empty($dados['veiculo_km']) || empty($dados['veiculo_cambio']) || 
            empty($dados['veiculo_combustivel']) || empty($dados['veiculo_portas']) || 
            empty($dados['veiculo_acentos']) || empty($dados['veiculo_tracao'])) {
            $resposta['message'] = 'Todos os campos são obrigatórios.';
        } elseif (!is_numeric($dados['veiculo_ano']) || $dados['veiculo_ano'] < 1900 || $dados['veiculo_ano'] > date('Y') + 1) {
            $resposta['message'] = 'Ano do veículo inválido.';
        } elseif (!is_numeric($dados['preco_diaria']) || $dados['preco_diaria'] <= 0) {
            $resposta['message'] = 'O preço diário deve ser um valor numérico positivo.';
        } else {
            // Verificar se a placa já existe (apenas se a placa for diferente da atual)
            if ($dados['veiculo_placa'] !== $veiculo['veiculo_placa']) {
                $stmt = $pdo->prepare("SELECT id FROM veiculo WHERE veiculo_placa = ? AND id != ?");
                $stmt->execute([$dados['veiculo_placa'], $veiculoId]);
                if ($stmt->rowCount() > 0) {
                    $resposta['message'] = 'Esta placa já está cadastrada no sistema.';
                    echo json_encode($resposta);
                    exit;
                }
            }
            
            try {
                $sql = "UPDATE veiculo SET 
                      veiculo_marca = ?, 
                      veiculo_modelo = ?, 
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
                      WHERE id = ? AND dono_id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $dados['veiculo_marca'],
                    $dados['veiculo_modelo'],
                    $dados['veiculo_ano'],
                    $dados['veiculo_km'],
                    $dados['veiculo_cambio'],
                    $dados['veiculo_combustivel'],
                    $dados['veiculo_portas'],
                    $dados['veiculo_acentos'],
                    $dados['veiculo_tracao'],
                    $dados['local_id'],
                    $dados['categoria_veiculo_id'],
                    $dados['preco_diaria'],
                    $dados['descricao'],
                    $veiculoId,
                    $dono['id']
                ]);
                
                $resposta = [
                    'status' => 'success',
                    'message' => 'Veículo atualizado com sucesso!',
                    'veiculo_id' => $veiculoId
                ];
                
            } catch (PDOException $e) {
                $resposta['message'] = 'Erro ao atualizar veículo: ' . $e->getMessage();
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($resposta);
exit;