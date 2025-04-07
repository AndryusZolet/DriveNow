<?php
session_start();

if (!isset($_SESSION['cadastro_sucesso'])) {
    header("Location: cadastro.php");
    exit();
}

unset($_SESSION['cadastro_sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Concluído - DriveNow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Cadastro Concluído!</h1>
        <p>Seu cadastro no DriveNow foi realizado com sucesso.</p>
        <a href="login.php" class="btn">Fazer Login</a>
    </div>
</body>
</html>
