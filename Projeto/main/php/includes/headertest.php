<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a conexão já foi estabelecida
if(!isset($conn)) {
    require 'connection.php'; // Usa a nova implementação
}
?>

<header>
    <img class="logo" src="LogoB.png" alt="Logo">
    <nav class="navegation">
        <a href="index.php">Home</a>
        <a href="sobre.php">Sobre</a>
        <a href="servicos.php">Serviço</a>
        <a href="contato.php">Contato</a>
        <?php if(isset($_SESSION['login_client'])): ?>
            <span class="welcome-msg">Bem-vindo, <?php echo htmlspecialchars($_SESSION['login_client']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php elseif(isset($_SESSION['login_customer'])): ?>
            <span class="welcome-msg">Bem-vindo, <?php echo htmlspecialchars($_SESSION['login_customer']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <button class="btnlogin-popup" onclick="location.href='login.php';">Login</button>
        <?php endif; ?>
    </nav>
</header>