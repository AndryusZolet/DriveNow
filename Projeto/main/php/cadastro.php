<?php
session_start();
require 'connection.php'; // Arquivo de conexão que você já tem

$erros = [];
$sucesso = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coletar e validar dados
    $primeiro_nome = trim($_POST['primeiro_nome']);
    $segundo_nome = trim($_POST['segundo_nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $tipo_conta = $_POST['tipo_conta']; // 'cliente' ou 'dono'

    // Validações
    if (empty($primeiro_nome)) {
        $erros[] = "Primeiro nome é obrigatório";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido";
    } else {
        // Verificar se e-mail já existe
        $stmt = $conn->prepare("SELECT id FROM conta_usuario WHERE e_mail = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $erros[] = "Este e-mail já está cadastrado";
        }
        $stmt->close();
    }

    if (strlen($senha) < 8) {
        $erros[] = "A senha deve ter pelo menos 8 caracteres";
    }

    if ($senha !== $confirmar_senha) {
        $erros[] = "As senhas não coincidem";
    }

    // Se não houver erros, proceder com o cadastro
    if (empty($erros)) {
        // Hash da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir na tabela conta_usuario
        $stmt = $conn->prepare("INSERT INTO conta_usuario (primeiro_nome, segundo_nome, e_mail, senha, data_de_entrada) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("ssss", $primeiro_nome, $segundo_nome, $email, $senha_hash);

        if ($stmt->execute()) {
            $usuario_id = $stmt->insert_id;
            
            // Se for dono, inserir na tabela dono
            if ($tipo_conta == 'dono') {
                $stmt_dono = $conn->prepare("INSERT INTO dono (conta_usuario_id) VALUES (?)");
                $stmt_dono->bind_param("i", $usuario_id);
                $stmt_dono->execute();
                $stmt_dono->close();
            }
            
            $sucesso = true;
            $_SESSION['cadastro_sucesso'] = true;
            header("Location: cadastro_sucesso.php");
            exit();
        } else {
            $erros[] = "Erro ao cadastrar: " . $conn->error;
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - DriveNow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Criar Conta no DriveNow</h1>
        
        <?php if (!empty($erros)): ?>
            <div class="error">
                <?php foreach ($erros as $erro): ?>
                    <p><?php echo htmlspecialchars($erro); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="cadastro.php" method="post">
            <div class="form-group">
                <label for="primeiro_nome">Primeiro Nome*</label>
                <input type="text" id="primeiro_nome" name="primeiro_nome" required 
                       value="<?php echo isset($_POST['primeiro_nome']) ? htmlspecialchars($_POST['primeiro_nome']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="segundo_nome">Segundo Nome</label>
                <input type="text" id="segundo_nome" name="segundo_nome"
                       value="<?php echo isset($_POST['segundo_nome']) ? htmlspecialchars($_POST['segundo_nome']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">E-mail*</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha* (mínimo 8 caracteres)</label>
                <input type="password" id="senha" name="senha" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha*</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8">
            </div>
            
            <div class="form-group">
                <label>Tipo de Conta*</label>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="cliente" name="tipo_conta" value="cliente" checked>
                        <label for="cliente">Cliente</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="dono" name="tipo_conta" value="dono">
                        <label for="dono">Dono de Veículo</label>
                    </div>
                </div>
            </div>
            
            <button type="submit">Cadastrar</button>
        </form>
        
        <div class="login-link">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>
</body>
</html>