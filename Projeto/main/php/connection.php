<?php
// Arquivo de conexão com o banco de dados DriveNow

class Database {
    private $host = 'localhost';
    private $db_name = 'DriveNow';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Método para conectar ao banco
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            // Em produção, você deve registrar este erro em um arquivo de log
            // e mostrar uma mensagem genérica ao usuário
            error_log("Erro de conexão: " . $e->getMessage());
            die("Desculpe, ocorreu um erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.");
        }

        return $this->conn;
    }
}

// Função de conveniência para manter compatibilidade com seu código existente
function Connect() {
    $database = new Database();
    return $database->connect();
}

// Conexão para uso em scripts que precisam de $conn diretamente
$conn = Connect();
?>