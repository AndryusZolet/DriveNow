<?php
require_once 'includes/auth.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $primeiroNome = trim($_POST['primeiro_nome']);
    $segundoNome = trim($_POST['segundo_nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['password'];
    $confirmarSenha = $_POST['confirmar_senha'];
    
    // Validações básicas
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
    } elseif (mb_strlen($senha) < 5) {
        $erro = 'A senha deve ser maior do que 5 caracteres.';
    } elseif (!isset($_POST['termos_aceitos'])) {
        $erro = 'Você precisa aceitar os termos de uso para continuar.';
    } else {
        $resultado = registrarUsuario($primeiroNome, $segundoNome, $email, $senha);
        
        if ($resultado === true) {
            $sucesso = 'Cadastro realizado com sucesso! Faça login para continuar.';
            $_SESSION['login_auto'] = [
                'email' => $email,
                'senha' => $senha
            ];
            header('Location: login.php?sucesso=1');
            exit;
        } else {
            $erro = $resultado;
        }
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
    <h2>Registre-se</h2>
        
        <?php if ($erro): ?>
            <div class="alert" style="color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="alert" style="color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($sucesso) ?>
            </div>
        <?php endif; ?>
            
        <div class="for-box register">
            <form method="POST">
                <div class="input-box">
                    <span class="icon"><ion-icon name="person-outline"></ion-icon></span>
                    <input type="text" name="primeiro_nome" required value="<?= isset($_POST['primeiro_nome']) ? htmlspecialchars($_POST['primeiro_nome']) : '' ?>">
                    <label>Nome</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="person-outline"></ion-icon></span>
                    <input type="text" name="segundo_nome" required value="<?= isset($_POST['segundo_nome']) ? htmlspecialchars($_POST['segundo_nome']) : '' ?>">
                    <label>Sobrenome</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <label>Email</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                    <input type="password" name="password" required>
                    <label>Senha</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                    <input type="password" name="confirmar_senha" required>
                    <label>Confirmar Senha</label>
                </div>
                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="termos_aceitos" required <?= isset($_POST['termos_aceitos']) ? 'checked' : '' ?>>
                        <a href="./termos.html" target="_blank">Aceita os termos de uso?</a>
                    </label>
                </div>
                <button type="submit" class="btn">Registrar</button>
                <div class="login-register">
                    <p>Já possui uma conta? <a href="./login.php" class="login-link">Login</a></p>
                </div>
            </form>
        </div>
    </div>
</html>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<?php require_once 'includes/footer.php'; ?>