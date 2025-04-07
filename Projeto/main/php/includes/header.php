<?php
// Conexão com o banco de dados usando PDO
$dsn = 'mysql:host=localhost;dbname=DriveNow;charset=utf8mb4';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $dbh = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Em produção, não exibir detalhes do erro diretamente
    error_log("Erro na conexão: " . $e->getMessage());
    die("Desculpe, ocorreu um erro ao conectar ao sistema. Por favor, tente novamente mais tarde.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveNow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header>
        <img class="logo" src="assets/images/LogoB.png" alt="Logo DriveNow">
        <nav class="navegation">
            <a href="index.php">Home</a>
            <a href="sobre.php">Sobre</a>
            <a href="servicos.php">Serviço</a>
            <a href="contato.php">Contato</a>
            <button class="btnlogin-popup" onclick="window.location.href='login.php'">Login</button>
        </nav>
    </header>
