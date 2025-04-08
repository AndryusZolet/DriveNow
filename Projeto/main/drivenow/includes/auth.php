<?php
session_start();
require_once __DIR__ . '/../config/db.php';

/**
 * Registra um novo usuário
 */
function registrarUsuario($primeiroNome, $segundoNome, $email, $senha) {
    global $pdo;
    
    // Verifica se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM conta_usuario WHERE e_mail = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        return "Este e-mail já está cadastrado.";
    }
    
    // Hash da senha
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
    
    try {
        // Substitui a chamada da procedure por INSERT direto
        $stmt = $pdo->prepare("INSERT INTO conta_usuario (primeiro_nome, segundo_nome, e_mail, senha, data_de_entrada) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->execute([$primeiroNome, $segundoNome, $email, $senhaHash]);
        
        return true;
    } catch (PDOException $e) {
        return "Erro ao registrar usuário: " . $e->getMessage();
    }
}

/**
 * Faz login do usuário
 */
function fazerLogin($email, $senha) {
    global $pdo;
    
    // Adicione data_de_entrada à consulta SELECT
    $stmt = $pdo->prepare("SELECT id, primeiro_nome, segundo_nome, e_mail, senha, data_de_entrada FROM conta_usuario WHERE e_mail = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Remove a senha da sessão por segurança
        unset($usuario['senha']);
        
        $_SESSION['usuario'] = $usuario;
        return true;
    }
    
    return "E-mail ou senha incorretos.";
}

/**
 * Verifica se o usuário está logado
 */
function estaLogado() {
    return isset($_SESSION['usuario']);
}

/**
 * Retorna os dados do usuário logado
 */
function getUsuario() {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Faz logout do usuário
 */
function fazerLogout() {
    session_unset();
    session_destroy();
}
?>