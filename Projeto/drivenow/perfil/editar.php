
<?php
require_once '../includes/auth.php';

// Verificar se o usuário está logado
if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();
$erro = '';
$sucesso = '';

// Processar edição se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoPrimeiroNome = trim($_POST['primeiro_nome']);
    $novoSegundoNome = trim($_POST['segundo_nome']);
    $senhaAtual = $_POST['senha_atual'];
    $novaSenha = $_POST['nova_senha'];
    $confirmarSenha = $_POST['confirmar_senha'];
    
    // Validações básicas
    if (empty($novoPrimeiroNome) || strlen($novoPrimeiroNome) < 3 || empty($novoSegundoNome) || strlen($novoSegundoNome) < 3) {
        $erro = 'O primeiro nome e o sobrenome são obrigatórios e devem conter mais de 2 caracteres.';
    } else {
        // Verificar se a senha será alterada
        $alterarSenha = !empty($novaSenha);
        
        if ($alterarSenha) {
            if (empty($senhaAtual)) {
                $erro = 'Para alterar a senha, informe a senha atual.';
            } elseif ($novaSenha !== $confirmarSenha) {
                $erro = 'A nova senha e a confirmação não coincidem.';
            } elseif (strlen($novaSenha) < 5) {
                $erro = 'A nova senha deve ter no mínimo 5 caracteres.';
            }
        }
        
        if (empty($erro)) {
            global $pdo;
            try {
                // Verificar senha atual se for alterar a senha
                if ($alterarSenha) {
                    $stmt = $pdo->prepare("SELECT senha FROM conta_usuario WHERE id = ?");
                    $stmt->execute([$usuario['id']]);
                    $dadosUsuario = $stmt->fetch();
                    
                    if (!$dadosUsuario || !password_verify($senhaAtual, $dadosUsuario['senha'])) {
                        $erro = 'Senha atual incorreta.';
                    } else {
                        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
                    }
                }
                
                if (empty($erro)) {
                    // Atualizar no banco de dados
                    if ($alterarSenha) {
                        $stmt = $pdo->prepare("UPDATE conta_usuario SET primeiro_nome = ?, segundo_nome = ?, senha = ? WHERE id = ?");
                        $stmt->execute([$novoPrimeiroNome, $novoSegundoNome, $senhaHash, $usuario['id']]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE conta_usuario SET primeiro_nome = ?, segundo_nome = ? WHERE id = ?");
                        $stmt->execute([$novoPrimeiroNome, $novoSegundoNome, $usuario['id']]);
                    }
                    
                    // Atualizar dados na sessão
                    $_SESSION['usuario']['primeiro_nome'] = $novoPrimeiroNome;
                    $_SESSION['usuario']['segundo_nome'] = $novoSegundoNome;
                    
                    header('Location: ../dashboard.php?sucesso=' . urlencode('Perfil atualizado com sucesso!'));
                }
            } catch (PDOException $e) {
                $erro = 'Erro ao atualizar perfil: ' . $e->getMessage();
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container edit-profile-container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Editar Perfil</h4>
                            <a href="../dashboard.php" class="btn btn-danger">Voltar ao Dashboard</a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="primeiro_nome" class="form-label">Primeiro Nome</label>
                            <input type="text" class="form-control" id="primeiro_nome" name="primeiro_nome" 
                                   value="<?= htmlspecialchars($usuario['primeiro_nome']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="segundo_nome" class="form-label">Segundo Nome</label>
                            <input type="text" class="form-control" id="segundo_nome" name="segundo_nome" 
                                   value="<?= htmlspecialchars($usuario['segundo_nome']) ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Alteração de Senha</h5>
                        <p class="text-muted">Preencha apenas se desejar alterar sua senha</p>
                        
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            <div class="form-text">Mínimo de 5 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-success">
                                <ion-icon name="save-outline"></ion-icon> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>