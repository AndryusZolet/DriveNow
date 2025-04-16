<?php
require_once '../includes/auth.php';

if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$usuario = getUsuario();
$erro = '';
$sucesso = '';

// Verificar se já é dono
global $pdo;
$stmt = $pdo->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
$stmt->execute([$usuario['id']]);
$dono = $stmt->fetch();

if ($dono) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO dono (conta_usuario_id) VALUES (?)");
        $stmt->execute([$usuario['id']]);
        
        $sucesso = 'Registro como proprietário realizado com sucesso!';
        header('Location: ../dashboard.php');
    } catch (PDOException $e) {
        $erro = 'Erro ao registrar como proprietário: ' . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Registrar-se como Proprietário</h4>
                </div>
                <div class="card-body">
                    <?php if ($erro): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($sucesso): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!$dono): ?>
                        <p>Ao se registrar como proprietário, você poderá cadastrar e gerenciar veículos em nossa plataforma.</p>
                        
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <ion-icon name="checkmark-circle-outline"></ion-icon> Confirmar Registro
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <ion-icon name="arrow-back-outline"></ion-icon> Voltar
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>