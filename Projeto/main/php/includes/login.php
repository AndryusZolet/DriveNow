<?php
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "DriveNow"; // Nome do banco que você criou

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verificar se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Usar prepared statement para prevenir SQL Injection
    $stmt = $conn->prepare("SELECT id, primeiro_nome, senha FROM conta_usuario WHERE e_mail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $usuario = $result->fetch_assoc();
        
        // Verificar a senha (assumindo que está usando password_hash no cadastro)
        if (password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            session_start();
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['login_customer'] = $usuario['primeiro_nome']; // ou 'login_client' para donos
            $_SESSION['email'] = $email;
            
            // Verificar se é dono de veículo
            $stmt_dono = $conn->prepare("SELECT id FROM dono WHERE conta_usuario_id = ?");
            $stmt_dono->bind_param("i", $usuario['id']);
            $stmt_dono->execute();
            
            if ($stmt_dono->get_result()->num_rows > 0) {
                $_SESSION['user_type'] = 'owner';
                header("Location: owner_dashboard.php");
            } else {
                $_SESSION['user_type'] = 'customer';
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $erro = "Email ou senha incorretos.";
        }
    } else {
        $erro = "Email ou senha incorretos.";
    }
    
    $stmt->close();
}

$conn->close();
?>