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

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Remix Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.css">

    <!-- Custom CSS -->
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
                <ul class="navbar-nav me-auto">
                    <?php if (estaLogado()): ?>
                        <!-- Links à esquerda para usuários logados -->
                        <li class="nav-item">
                            <a class="nav-link" href="#">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Sobre</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Carros</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (estaLogado()): ?>
                        <!-- Dropdown de perfil -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="perfilDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= htmlspecialchars($usuario['primeiro_nome']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="perfilDropdown">
                                <li><a class="dropdown-item" href="#"><i class="ri-user-line me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="ri-time-line me-2"></i>Atividades</a></li>
                                <li><a class="dropdown-item" href="#"><i class="ri-bookmark-line me-2"></i>Salvos</a></li>
                                <li><a class="dropdown-item" href="#"><i class="ri-settings-3-line me-2"></i>Configurações</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <?php if (
                                        str_contains($caminhoAtual, 'veiculo/') ||
                                        str_contains($caminhoAtual, 'perfil/')
                                    ): ?>
                                        <a class="dropdown-item" href="../logout.php"><i class="ri-logout-box-r-line me-2"></i>Sair</a>
                                    <?php else: ?>
                                        <a class="dropdown-item" href="./logout.php"><i class="ri-logout-box-r-line me-2"></i>Sair</a>
                                    <?php endif; ?>
                                </li>
                            </ul>
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
