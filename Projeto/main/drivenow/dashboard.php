<?php
require_once 'includes/auth.php';

// Se não estiver logado, redireciona para o login
if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();

require_once 'includes/header.php';
?>

<div class="container">
    <h2>Bem-vindo, <?= htmlspecialchars($usuario['primeiro_nome']) ?>!</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Seus Dados</h5>
            <p class="card-text">
                <strong>Nome:</strong> <?= htmlspecialchars($usuario['primeiro_nome'] . ' ' . $usuario['segundo_nome']) ?><br>
                <strong>E-mail:</strong> <?= htmlspecialchars($usuario['e_mail']) ?><br>
                <strong>Membro desde:</strong> 
                <?= isset($usuario['data_de_entrada']) && $usuario['data_de_entrada'] ? date('d/m/Y', strtotime($usuario['data_de_entrada'])) : 'Data não disponível' ?>
            </p>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
    
    <!-- Aqui você pode adicionar mais conteúdo do dashboard -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Suas Reservas</h5>
                    <p class="card-text">Veja e gerencie suas reservas de veículos.</p>
                    <a href="#" class="btn btn-primary">Ver Reservas</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Favoritos</h5>
                    <p class="card-text">Veículos que você marcou como favorito.</p>
                    <a href="#" class="btn btn-primary">Ver Favoritos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>