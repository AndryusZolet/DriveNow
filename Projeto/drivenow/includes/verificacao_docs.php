<?php
/**
 * Arquivo para verificar o status dos documentos do usuário.
 * Este arquivo deve ser colocado em: includes/verificacao_docs.php
 */

// Função para verificar se o usuário completou o cadastro com documentação
function verificarCadastroCompleto($usuario, $redirecionarSeNecessario = true) {
    global $pdo;
    
    // Verificar se o usuário está logado
    if (!isset($usuario['id'])) {
        return false;
    }
    
    // Verificar se o usuário já completou o cadastro
    $stmt = $pdo->prepare("SELECT cadastro_completo FROM conta_usuario WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $cadastroCompleto = $stmt->fetchColumn();
    
    // Se o cadastro não está completo e redirecionamento está ativado
    if (!$cadastroCompleto && $redirecionarSeNecessario) {
        // Salvar mensagem na sessão
        $_SESSION['notification'] = [
            'type' => 'warning',
            'message' => 'Por favor, complete seu cadastro antes de continuar.'
        ];
        
        // Redirecionar para a página de documentos
        header('Location: /perfil/editar.php');
        exit;
    }
    
    return (bool)$cadastroCompleto;
}

// Função para verificar se o usuário pode alugar veículos (tem CNH aprovada)
function podeAlugarVeiculo($usuario) {
    global $pdo;
    
    // Verificar se o usuário está logado
    if (!isset($usuario['id'])) {
        return false;
    }
    
    // Verificar se o usuário tem CNH aprovada
    $stmt = $pdo->prepare("SELECT tem_cnh, status_docs FROM conta_usuario WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $result = $stmt->fetch();
    
    // Usuário pode alugar se tem CNH e documentos foram aprovados
    return $result && $result['tem_cnh'] && $result['status_docs'] === 'aprovado';
}