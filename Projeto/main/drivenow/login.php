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
    $senha = $_POST['password'];
    
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
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="./assets/style.css">
    </head>
    <div class="container">
        <h2>Login</h2>
        
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= $erro ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-box">
                <span class="icon"><ion-icon name="mail"></ion-icon></span>
                <input type="email" name="email" required>
                <label>Email</label>
            </div>
            <div class="input-box">
                <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                <input type="password" name="password" required>
                <label>Senha</label>
            </div>
            <div class="remember-forgot">
                <label><input type="checkbox" name="remember">Lembre-me</label>
                <a href="#">Esqueci a senha</a>
            </div>
            <button type="submit" class="btn">Login</button>
            <div class="login-register">
                <p>Ainda não possui uma conta? <a href="./cadastro.php" class="register-link">Registre-se</a></p>
            </div>
        </form>
    </div>
</html>

<?php require_once 'includes/footer.php'; ?>