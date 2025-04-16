<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'drivenow');
define('DB_USER', 'root'); 
define('DB_PASS', '');

// Tentativa de conexão
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>