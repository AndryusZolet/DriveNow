<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Verifica se esta logado, caso contrario redireciona para login.php
 * */
function verificarAutenticacao() {
    if (!estaLogado()) {
        header('Location: ../login.php');
        exit();
    }
}

function usuarioPodeReservar() {
    $usuario = getUsuario();
    
    if ($usuario['cadastro_completo'] == 1 && $usuario['status_docs'] == 'aprovado') {
        return true;
    }
}

/**
 * Verifica se o usuário atual é um administrador
 * 
 * Retorna true se o usuário for administrador, false caso contrário
 */
function isAdmin() {
    // Verificar se o usuário está logado
    if (!estaLogado()) {
        return false;
    }
    
    // Verificar se o usuário tem a flag de administrador
    $usuario = getUsuario();
    
    // Se o usuário tiver a flag is_admin = 1, então é administrador
    return isset($usuario['is_admin']) && $usuario['is_admin'] == 1;
}

/**
 * Verifica se o usuário é administrador e redireciona caso não seja
 */
function verificarAdmin() {
    if (!isAdmin()) {
        // Definir mensagem de erro
        // $_SESSION['notification'] = [
        //     'type' => 'error',
        //     'message' => 'Acesso negado! Você não tem permissões de administrador.'
        // ];
        
        // Redirecionar para a página inicial
        header('Location: ../vboard.php');
        exit;
    }
}

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

// Força a puxar novamente as informacoes do banco de dados
function updateInfosUsuario() {
    global $pdo;
    
    if (!estaLogado()) {
        return false;
    }
    
    $usuario = getUsuario();
    
    // Busca os dados atualizados do usuário
    $stmt = $pdo->prepare("SELECT * FROM conta_usuario WHERE e_mail = ?");
    $stmt->execute([$usuario['e_mail']]);
    $usuarioAtualizado = $stmt->fetch();
    
    if ($usuarioAtualizado) {
        // Remove a senha da sessão por segurança
        unset($usuarioAtualizado['senha']);
        
        // Atualiza a sessão com os novos dados
        $_SESSION['usuario'] = $usuarioAtualizado;
        return true;
    }
    
    return false;
}

/**
 * Faz login do usuário
 */
function fazerLogin($email, $senha) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM conta_usuario WHERE e_mail = ?");
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