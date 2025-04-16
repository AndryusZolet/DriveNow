<?php
    $paginaAtual = basename($_SERVER['PHP_SELF']);
    $caminhoAtual = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveNow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <?php if (
                str_contains($caminhoAtual, 'veiculo/') ||
                str_contains($caminhoAtual, 'perfil/')
            ): ?>
                <a class="navbar-brand" href="../index.html">DriveNow</a>
            <?php else: ?>
                <a class="navbar-brand" href="./index.html">DriveNow</a>
            <?php endif; ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (estaLogado()): ?>
                        <?php if ($paginaAtual != 'dashboard.php'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <?php if (
                                str_contains($caminhoAtual, 'veiculo/') ||
                                str_contains($caminhoAtual, 'perfil/')
                            ): ?>
                                <a class="nav-link" href="../logout.php">Sair</a>
                            <?php else: ?>
                                <a class="nav-link" href="./logout.php">Sair</a>
                            <?php endif; ?>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cadastro.php">Cadastro</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-fill">