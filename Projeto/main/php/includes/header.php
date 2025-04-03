<?php
// Conexão com o banco de dados
$dsn = 'mysql:host=localhost;dbname=DriveNow;charset=utf8';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $dbh = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>

<header>
    <img class="logo" src="LogoB.png" alt="Logo">
    <nav class="navegation">
        <a href="#">Home</a>
        <a href="#">Sobre</a>
        <a href="#">Serviço</a>
        <a href="#">Contato</a>
        <button class="btnlogin-popup">Login</button>
    </nav>
</header>