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
$totalReservas = 0;
$reservasAtivas = 0;

if ($dono) {
    // Contar veículos do dono
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM veiculo WHERE dono_id = ?");
    $stmt->execute([$dono['id']]);
    $totalVeiculos = $stmt->fetchColumn();
    
    // Contar reservas dos veículos do dono
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva r 
                          JOIN veiculo v ON r.veiculo_id = v.id 
                          WHERE v.dono_id = ?");
    $stmt->execute([$dono['id']]);
    $totalReservas = $stmt->fetchColumn();
}

// Contar reservas do usuário (tanto locatário quanto proprietário)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$minhasReservas = $stmt->fetchColumn();

// Contar reservas ativas do usuário (com data futura)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reserva 
                      WHERE conta_usuario_id = ? AND reserva_data >= CURDATE()");
$stmt->execute([$usuario['id']]);
$reservasAtivas = $stmt->fetchColumn();

require_once 'includes/header.php';
?>

<div class="container">
    <h2>Bem-vindo, <?= htmlspecialchars($usuario['primeiro_nome']) ?>!</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <?php if (isset($_GET['erro']) && $_GET['erro']): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars(urldecode($_GET['erro'])) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['sucesso']) && $_GET['sucesso']): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars(urldecode($_GET['sucesso'])) ?>
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
                    <br><strong>Reservas recebidas:</strong> <?= $totalReservas ?>
                <?php endif; ?>
                <br><strong>Suas reservas ativas:</strong> <?= $reservasAtivas ?>
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
                    <p class="card-text">
                        Você tem <?= $minhasReservas ?> reserva(s) no total, 
                        sendo <?= $reservasAtivas ?> ativa(s).
                    </p>
                    <a href="reserva/minhas_reservas.php" class="btn btn-primary">Ver Reservas</a>
                    <?php if ($dono): ?>
                        <a href="reserva/reservas_recebidas.php" class="btn btn-secondary ms-2">Reservas Recebidas</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Card de Busca de Veículos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Alugar Veículo</h5>
                    <p class="card-text">Encontre o veículo perfeito para sua próxima viagem.</p>
                    <a href="reserva/listagem_veiculos.php" class="btn btn-primary">Buscar Veículos</a>
                </div>
            </div>
        </div>
        
        <!-- Card de Histórico -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Histórico</h5>
                    <p class="card-text">Veja seu histórico de aluguéis e reservas.</p>
                    <a href="reserva/historico_reservas.php" class="btn btn-primary">Ver Histórico</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>