<?php
require_once 'includes/auth.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $primeiroNome = trim($_POST['primeiro_nome']);
    $segundoNome = trim($_POST['segundo_nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmar_senha'];
    
    // Validações básicas (CORREÇÃO APLICADA AQUI - FALTAVA PARÊNTESE)
    if (empty($primeiroNome)) {
        $erro = 'O primeiro nome é obrigatório.';
    } elseif (empty($email)) {
        $erro = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (empty($senha)) {
        $erro = 'A senha é obrigatória.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        $resultado = registrarUsuario($primeiroNome, $segundoNome, $email, $senha);
        
        if ($resultado === true) {
            $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
            header('Refresh: 3; URL=login.php');
        } else {
            $erro = $resultado;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <h2>Cadastro</h2>
    
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    
    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="primeiro_nome">Primeiro Nome:</label>
            <input type="text" class="form-control" id="primeiro_nome" name="primeiro_nome" required>
        </div>
        
        <div class="form-group">
            <label for="segundo_nome">Segundo Nome:</label>
            <input type="text" class="form-control" id="segundo_nome" name="segundo_nome">
        </div>
        
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" class="form-control" id="senha" name="senha" required>
        </div>
        
        <div class="form-group">
            <label for="confirmar_senha">Confirmar Senha:</label>
            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Cadastrar</button>
        <p class="mt-3">Já tem uma conta? <a href="login.php">Faça login</a></p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>