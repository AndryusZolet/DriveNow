<?php
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "drivenow";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém os dados do formulário
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

    // Valida os campos
    if (empty($nome) || empty($email) || empty($senha)) {
        echo "Por favor, preencha todos os campos.";
    } else {
        // Hash da senha para segurança
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Insere os dados no banco de dados
        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nome, $email, $senhaHash);

        if ($stmt->execute()) {
            echo "Registro realizado com sucesso!";
        } else {
            echo "Erro ao registrar: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>