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

<div class="wrapper">
    <div class="for-box register">
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
        
        <form method="POST">
            <div class="input-box">
                <input type="text" name="primeiro_nome" required>
                <label>Primeiro Nome</label>
                <span class="icon"><ion-icon name="person-outline"></ion-icon></span>
            </div>
            
            <div class="input-box">
                <input type="text" name="segundo_nome">
                <label>Segundo Nome</label>
                <span class="icon"><ion-icon name="person-outline"></ion-icon></span>
            </div>
            
            <div class="input-box">
                <input type="email" name="email" required>
                <label>E-mail</label>
                <span class="icon"><ion-icon name="mail"></ion-icon></span>
            </div>
            
            <div class="input-box">
                <input type="password" name="senha" required>
                <label>Senha</label>
                <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
            </div>
            
            <div class="input-box">
                <input type="password" name="confirmar_senha" required>
                <label>Confirmar Senha</label>
                <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
            </div>
            
            <div class="remember-forgot">
                <label><input type="checkbox" required> Aceito os termos de uso</label>
            </div>
            
            <button type="submit" class="btn">Registrar</button>
            
            <div class="login-register">
                <p>Já possui uma conta? <a href="login.php">Login</a></p>
            </div>
        </form>
    </div>
</div>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<?php require_once 'includes/footer.php'; ?>