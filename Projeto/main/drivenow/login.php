<?php
require_once 'includes/auth.php';

// Se o usuário já estiver logado, redireciona para o dashboard
if (estaLogado()) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    
    $resultado = fazerLogin($email, $senha);
    
    if ($resultado === true) {
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = $resultado;
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <h2>Login</h2>
    
    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="senha">Senha:</label>
            <input type="password" class="form-control" id="senha" name="senha" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Entrar</button>
        <p class="mt-3">Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>