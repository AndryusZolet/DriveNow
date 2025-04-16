<?php
require_once 'includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();

// Verificar se o usuário é um dono e contar veículos
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

$totalVeiculos = 0;
if ($dono) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM veiculo WHERE dono_id = ?");
    $stmt->execute([$dono['id']]);
    $totalVeiculos = $stmt->fetchColumn();
}

require_once 'includes/header.php';
?>

<div class="container">
    <h2>Bem-vindo, <?= htmlspecialchars($usuario['primeiro_nome']) ?>!</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <?php if ($erro || isset($_GET['erro'])): ?>
                <div class="alert alert-danger"><
                    <?= htmlspecialchars($erro ?: $_GET['erro']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($sucesso || isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($sucesso ?: $_GET['sucesso']) ?>
                </div>
            <?php endif; ?>
            
            <h5 class="card-title">Seus Dados</h5>
            <p class="card-text">
                <strong>Nome:</strong> <?= htmlspecialchars($usuario['primeiro_nome'] . ' ' . $usuario['segundo_nome']) ?><br>
                <strong>E-mail:</strong> <?= htmlspecialchars($usuario['e_mail']) ?><br>
                <strong>Membro desde:</strong> 
                <?= isset($usuario['data_de_entrada']) && $usuario['data_de_entrada'] ? date('d/m/Y', strtotime($usuario['data_de_entrada'])) : 'Data não disponível' ?>
                <?php if ($dono): ?>
                    <br><strong>Veículos cadastrados:</strong> <?= $totalVeiculos ?>
                <?php endif; ?>
            </p>
            <a href="./perfil/editar.php" class="btn btn-primary">Editar Perfil</a>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Card de Veículos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Veículos</h5>
                    <?php if ($dono): ?>
                        <p class="card-text">Você tem <?= $totalVeiculos ?> veículo(s) cadastrado(s).</p>
                        <div class="d-flex gap-2">
                            <a href="./veiculo/veiculos.php" class="btn btn-primary">Gerenciar Veículos</a>
                            <a href="./veiculo/cadastro.php" class="btn btn-success">Adicionar Veículo</a>
                        </div>
                    <?php else: ?>
                        <p class="card-text">Cadastre-se como proprietário para gerenciar veículos.</p>
                        <a href="./perfil/registrar_dono.php" class="btn btn-primary">Torne-se um Proprietário</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Card de Reservas -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Suas Reservas</h5>
                    <p class="card-text">Veja e gerencie suas reservas de veículos.</p>
                    <!-- <a> href="minhas_reservas.php" </a> REMOVIDO DE BAIXO -->
                    <a onclick="return alert('Função Desativada.');" class="btn btn-primary">Ver Reservas</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Card de Favoritos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Favoritos</h5>
                    <p class="card-text">Veículos que você marcou como favorito.</p>
                    <!-- <a> href="favoritos.php" </a> REMOVIDO DE BAIXO -->
                    <a onclick="return alert('Função Desativada.');" class="btn btn-primary">Ver Favoritos</a>
                </div>
            </div>
        </div>
        
        <!-- Card extra pode ser usado para outras funcionalidades -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Configurações</h5>
                    <p class="card-text">Ajuste suas preferências e configurações de conta.</p>
                    <!-- <a> href="configuracoes.php" </a> REMOVIDO DE BAIXO -->
                    <a onclick="return alert('Função Desativada.');" class="btn btn-primary" aria-disabled="true">Acessar Configurações</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>