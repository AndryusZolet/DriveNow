<?php
// Habilitar logs de erro para depuração
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_once '../includes/FileUpload.php'; // Adicionar inclusão da classe de upload

// Verificar se o usuário está logado
verificarAutenticacao();

$usuario = getUsuario();
$erro = '';
$sucesso = '';

// Buscar dados de documentos do usuário
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM conta_usuario WHERE id = ?");
$stmt->execute([$usuario['id']]);
$docData = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se já possui documentos enviados
$temCnhFrente = !empty($docData['foto_cnh_frente']);
$temCnhVerso = !empty($docData['foto_cnh_verso']);
$jaEnviouDocumentos = $temCnhFrente && $temCnhVerso;
$docsAprovados = $docData['status_docs'] == 'aprovado';

// Processar edição se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoPrimeiroNome = trim($_POST['primeiro_nome']);
    $novoSegundoNome = trim($_POST['segundo_nome']);
    $novoTelefone = trim($_POST['telefone'] ?? ''); // Adicionar esta linha
    $senhaAtual = $_POST['senha_atual'];
    $novaSenha = $_POST['nova_senha'];
    $confirmarSenha = $_POST['confirmar_senha'];
    
    // Novos campos para documentos
    $cpf = $docsAprovados ? $docData['cpf'] : trim($_POST['cpf'] ?? '');
    
    // Se já enviou documentos, manter o valor atual do tem_cnh
    if ($jaEnviouDocumentos) {
        $temCnh = $docData['tem_cnh'] ? 1 : 0;
    } else {
        $temCnh = isset($_POST['tem_cnh']) ? 1 : 0;
    }
    
    // Validações básicas para nome
    if (empty($novoPrimeiroNome) || strlen($novoPrimeiroNome) < 3 || empty($novoSegundoNome) || strlen($novoSegundoNome) < 3) {
        $erro = 'O primeiro nome e o sobrenome são obrigatórios e devem conter mais de 2 caracteres.';
    } 
    // Validação CPF
    elseif (!empty($cpf) && !validarCPF($cpf)) {
        $erro = 'CPF inválido.';
    }
    else {
        // Verificar se a senha será alterada
        $alterarSenha = !empty($novaSenha);
        
        if ($alterarSenha) {
            if (empty($senhaAtual)) {
                $erro = 'Para alterar a senha, informe a senha atual.';
            } elseif ($novaSenha !== $confirmarSenha) {
                $erro = 'A nova senha e a confirmação não coincidem.';
            } elseif (strlen($novaSenha) < 5) {
                $erro = 'A nova senha deve ter no mínimo 5 caracteres.';
            }
        }
        
        if (empty($erro)) {
            global $pdo;
            try {
                // Verificar senha atual se for alterar a senha
                if ($alterarSenha) {
                    $stmt = $pdo->prepare("SELECT senha FROM conta_usuario WHERE id = ?");
                    $stmt->execute([$usuario['id']]);
                    $dadosUsuario = $stmt->fetch();
                    
                    if (!$dadosUsuario || !password_verify($senhaAtual, $dadosUsuario['senha'])) {
                        $erro = 'Senha atual incorreta.';
                    } else {
                        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
                    }
                }
                
                // Verificar se já existe outro usuário com esse CPF
                if (!empty($cpf) && empty($erro)) {
                    $stmt = $pdo->prepare("SELECT id FROM conta_usuario WHERE cpf = ? AND id != ?");
                    $stmt->execute([$cpf, $usuario['id']]);
                    if ($stmt->rowCount() > 0) {
                        $erro = 'Este CPF já está registrado para outro usuário.';
                    }
                }
                
                // Processar uploads de documentos
                if (empty($erro)) {
                    // Arrays para armazenar os caminhos dos arquivos
                    $filesPaths = [
                        'foto_cnh_frente' => $docData['foto_cnh_frente'] ?? null,
                        'foto_cnh_verso' => $docData['foto_cnh_verso'] ?? null
                    ];

                    // Setup para upload de arquivos
                    $uploadDir = '../uploads/user_' . $usuario['id'] . '/docs/';

                    // Garantir que o diretório exista
                    if (!file_exists($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $erro = 'Não foi possível criar o diretório para upload: ' . $uploadDir;
                        }
                    }

                    // Log para depuração
                    error_log("Diretório de upload: " . $uploadDir);
                    error_log("FILES array: " . print_r($_FILES, true));

                    $fileUpload = new FileUpload($uploadDir);

                    // Processa uploads apenas se tiver arquivos
                    $arquivosEnviados = false;
                    if (!$jaEnviouDocumentos) {
                        foreach (['foto_cnh_frente', 'foto_cnh_verso'] as $field) {
                            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                                error_log("Processando upload para o campo: " . $field);
                                $result = $fileUpload->uploadFile($_FILES[$field], $field . '_');
                                
                                if ($result) {
                                    // Salvar o caminho relativo
                                    $filesPaths[$field] = $result['path'];
                                    
                                    error_log("Upload bem-sucedido: " . $result['path']);
                                    $arquivosEnviados = true;
                                } else {
                                    $erro = 'Erro ao fazer upload do arquivo ' . getNomeAmigavel($field) . ': ' . $fileUpload->getLastError();
                                    error_log("Erro de upload: " . $erro);
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Verifica se arquivos obrigatórios estão presentes quando o CPF é fornecido e usuário tem CNH
                    if (!empty($cpf) && $temCnh) {
                        if ((empty($filesPaths['foto_cnh_frente']) || empty($filesPaths['foto_cnh_verso'])) && $arquivosEnviados) {
                            $erro = 'Se você possui CNH, envie tanto a frente quanto o verso da mesma.';
                        }
                    }
                
                    // Se não houver erros, atualizar o banco de dados
                    if (empty($erro)) {
                        $pdo->beginTransaction();
                        
                        try {
                            // Atualizar dados básicos do perfil
                            if ($alterarSenha) {
                                $stmt = $pdo->prepare("UPDATE conta_usuario SET primeiro_nome = ?, segundo_nome = ?, telefone = ?, senha = ? WHERE id = ?");
                                $stmt->execute([$novoPrimeiroNome, $novoSegundoNome, $novoTelefone, $senhaHash, $usuario['id']]);
                            } else {
                                $stmt = $pdo->prepare("UPDATE conta_usuario SET primeiro_nome = ?, segundo_nome = ?, telefone = ? WHERE id = ?");
                                $stmt->execute([$novoPrimeiroNome, $novoSegundoNome, $novoTelefone, $usuario['id']]);
                            }
                            
                            // Atualizar documentos apenas se CPF foi fornecido
                            if (!empty($cpf)) {
                                // Verificar se todos documentos necessários estão presentes
                                $cadastroCompleto = true;
                                if ($temCnh) {
                                    $cadastroCompleto = !empty($filesPaths['foto_cnh_frente']) && !empty($filesPaths['foto_cnh_verso']);
                                }
                                
                                $stmt = $pdo->prepare("UPDATE conta_usuario SET 
                                    cpf = ?, 
                                    foto_cnh_frente = ?, 
                                    foto_cnh_verso = ?,
                                    tem_cnh = ?,
                                    cadastro_completo = ?,
                                    status_docs = ?
                                    WHERE id = ?");
                                
                                // Define o status dos documentos
                                $temDocumentos = !empty($docData['foto_cnh_frente']) || !empty($docData['foto_cnh_verso']);
                                $statusDocs = null;

                                // Se está enviando documentos pela primeira vez, marca como pendente
                                if ($arquivosEnviados) {
                                    $statusDocs = 'pendente';
                                } 
                                // Se já tem um status e já tinha documentos, mantém ou atualiza o status
                                elseif (!empty($docData['status_docs']) && ($temDocumentos || $docData['cadastro_completo'])) {
                                    $statusDocs = $docData['status_docs'];
                                    // Se já estava aprovado e não enviou novos docs, mantém aprovado
                                    // Se enviou novos docs, volta para pendente (exceto se já estava aprovado)
                                    if ($arquivosEnviados && $statusDocs != 'aprovado') {
                                        $statusDocs = 'pendente';
                                    }
                                }
                                
                                $stmt->execute([
                                    $cpf,
                                    $filesPaths['foto_cnh_frente'],
                                    $filesPaths['foto_cnh_verso'],
                                    $temCnh,
                                    $cadastroCompleto,
                                    $statusDocs,
                                    $usuario['id']
                                ]);
                            }
                            
                            // Commit das alterações
                            $pdo->commit();
                            
                            // Atualizar dados na sessão
                            $_SESSION['usuario']['primeiro_nome'] = $novoPrimeiroNome;
                            $_SESSION['usuario']['segundo_nome'] = $novoSegundoNome;
                            
                            // Usar sistema de notificações
                            $_SESSION['notification'] = [
                                'type' => 'success',
                                'message' => 'Perfil atualizado com sucesso!'
                            ];
                            
                            header('Location: ../vboard.php');
                            exit;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $erro = 'Erro ao atualizar perfil: ' . $e->getMessage();
                        }
                    }
                }
            } catch (PDOException $e) {
                $erro = 'Erro ao atualizar perfil: ' . $e->getMessage();
            }
        }
    }
}

/**
 * Função para validar CPF
 */
function validarCPF($cpf) {
    // Remove formatação
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int)$cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int)$cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica se os dígitos verificadores estão corretos
    return ($cpf[9] == $dv1 && $cpf[10] == $dv2);
}

/**
 * Retorna um nome amigável para os campos de arquivo
 */
function getNomeAmigavel($field) {
    $nomes = [
        'foto_cnh_frente' => 'Foto da CNH (frente)',
        'foto_cnh_verso' => 'Foto da CNH (verso)'
    ];
    
    return $nomes[$field] ?? $field;
}

/**
 * Obtém o caminho completo da imagem para exibição
 */
/**
 * Obtém o caminho completo da imagem para exibição
 */
function getImagemUrl($caminho) {
    // Verifica se o caminho é vazio
    if (empty($caminho)) {
        return '';
    }
    
    // Verifica se o caminho já começa com http ou https
    if (preg_match('/^https?:\/\//', $caminho)) {
        return $caminho;
    }
    
    // Corrigir o caminho caso tenha "user_user_" duplicado
    if (strpos($caminho, 'user_user_') !== false) {
        $caminho = str_replace('user_user_', 'user_', $caminho);
        error_log("Caminho corrigido para: " . $caminho);
    }
    
    // Normalizar barras (converter backslashes para forward slashes)
    $caminho = str_replace('\\', '/', $caminho);
    
    // Adicionar o caminho base do site
    $caminhoBase = '../'; // Ajuste conforme necessário para o seu ambiente
    
    // Verificar se o arquivo existe
    $fullPath = $caminhoBase . $caminho;
    $normalizedPath = realpath($fullPath);
    
    if (!$normalizedPath || !file_exists($normalizedPath)) {
        error_log("Arquivo não encontrado: " . $fullPath);
        // Tentar verificar se o problema é com o caminho base
        if (file_exists($caminho)) {
            error_log("Arquivo encontrado sem o caminho base!");
            return $caminho;
        }
    }
    
    return $caminhoBase . $caminho;
}

/**
 * Obtém o caminho absoluto para o arquivo original (para download)
 */
function getFilePath($caminho) {
    if (empty($caminho)) {
        return false;
    }
    
    return realpath('../' . $caminho);
}

/**
 * Verifica se um arquivo existe e está acessível
 */
/**
 * Verifica se um arquivo existe e está acessível
 */
function verificarArquivo($caminho) {
    // Corrigir o caminho caso tenha "user_user_" duplicado
    if (strpos($caminho, 'user_user_') !== false) {
        $caminho = str_replace('user_user_', 'user_', $caminho);
        error_log("Caminho corrigido em verificarArquivo: " . $caminho);
    }
    
    // Normalizar barras
    $caminho = str_replace('\\', '/', $caminho);
    
    // Verificar diferentes possibilidades
    $possibilidades = [
        '../' . $caminho,
        $caminho,
        realpath('../' . $caminho),
        realpath($caminho)
    ];
    
    foreach ($possibilidades as $path) {
        if ($path && file_exists($path) && is_readable($path)) {
            error_log("Arquivo encontrado em: " . $path);
            return true;
        }
    }
    
    error_log("Arquivo não encontrado em nenhum caminho tentado para: " . $caminho);
    return false;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - DriveNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: sans-serif;
        }
        .animate-pulse-15s { animation-duration: 15s; }
        .animate-pulse-20s { animation-duration: 20s; }
        .animate-pulse-25s { animation-duration: 25s; }

        .subtle-border {
            border-color: rgba(255, 255, 255, 0.1);
        }

        option {
            background-color: #1e293b !important;
            color: white !important;
        }

        .file-upload-preview {
            max-width: 100%;
            max-height: 150px;
            object-fit: contain;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 8px;
            background-color: rgba(255, 255, 255, 0.05);
        }

        /* Estilo para o input de arquivo */
        input[type="file"] {
            padding: 8px;
            font-size: 14px;
        }

        /* Estilo para o checkbox */
        input[type="checkbox"] {
            cursor: pointer;
        }

        /* Estilo para as imagens de documentos */
        .doc-image {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background-color: rgba(0, 0, 0, 0.2);
            padding: 4px;
        }

        /* Estilo para o container de imagem de documento */
        .doc-image-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.05);
            transition: all 0.2s ease;
        }

        .doc-image-container:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Estilo para modal de visualização de imagem */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.85);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            position: relative;
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90vh;
            margin-top: 2rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            animation: zoomIn 0.3s;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: white;
            cursor: pointer;
            background-color: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background-color: rgba(255, 0, 0, 0.5);
        }

        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        @keyframes zoomIn {
            from {transform: scale(0.8); opacity: 0;}
            to {transform: scale(1); opacity: 1;}
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white p-4 md:p-8 overflow-x-hidden">

    <div class="fixed top-0 right-0 w-96 h-96 rounded-full bg-indigo-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-15s"></div>
    <div class="fixed bottom-0 left-0 w-80 h-80 rounded-full bg-purple-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-20s"></div>
    <div class="fixed top-1/3 left-1/4 w-64 h-64 rounded-full bg-slate-700 opacity-5 blur-3xl -z-10 animate-pulse animate-pulse-25s"></div>

    <header class="backdrop-blur-md bg-white/5 border subtle-border rounded-2xl mb-8 shadow-lg overflow-hidden">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white mr-8">DriveNow</h1>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-white hidden md:inline"><?= htmlspecialchars($usuario['primeiro_nome']) ?></span>
                    <div class="relative h-8 w-8 transition-transform hover:scale-110 rounded-full flex items-center justify-center bg-indigo-500 text-white overflow-hidden">
                        <?php if (isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                        <?php else: ?>
                            <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($usuario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-white">Editar Perfil</h2>
                <a href="../vboard.php" class="bg-red-500 hover:bg-red-600 text-white rounded-xl transition-colors border border-red-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    Voltar ao Dashboard
                </a>
            </div>
            
            <?php if ($erro): ?>
                <div class="mb-6 bg-red-500/20 border border-red-400/30 text-white px-6 py-4 rounded-xl">
                    <p class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?= htmlspecialchars($erro) ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="mb-6 bg-green-500/20 border border-green-400/30 text-white px-6 py-4 rounded-xl">
                    <p class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <?= htmlspecialchars($sucesso) ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-6 md:p-8 shadow-lg">
                <form method="POST" class="space-y-6" id="formEditarPerfil" enctype="multipart/form-data" onsubmit="return validarFormulario()">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="primeiro_nome" class="block text-white font-medium mb-2">Nome</label>
                            <input 
                                type="text" 
                                id="primeiro_nome" 
                                name="primeiro_nome" 
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                value="<?= htmlspecialchars($usuario['primeiro_nome']) ?>" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="segundo_nome" class="block text-white font-medium mb-2">Sobrenome</label>
                            <input 
                                type="text" 
                                id="segundo_nome" 
                                name="segundo_nome" 
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                value="<?= htmlspecialchars($usuario['segundo_nome']) ?>" 
                                required
                            >
                        </div>

                        <!-- Adicionar campo de telefone -->
                        <div>
                            <label for="telefone" class="block text-white font-medium mb-2">Telefone</label>
                            <input 
                                type="tel" 
                                id="telefone" 
                                name="telefone" 
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                value="<?= htmlspecialchars($docData['telefone'] ?? '') ?>" 
                                placeholder="(00) 00000-0000"
                            >
                            <p class="text-white/50 text-sm mt-1">Digite apenas números com DDD</p>
                        </div>
                        <div>
                            <label for="email" class="block text-white font-medium mb-2">E-mail</label>
                            <input 
                                type="text" 
                                id="email" 
                                name="email" 
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 opacity-50 cursor-not-allowed"
                                value="<?= htmlspecialchars($docData['e_mail'] ?? '') ?>" 
                                readonly 
                            >
                            <div class="absolute inset-0 pointer-events-none bg-gradient-to-r from-purple-500/5 to-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <p class="flex items-center text-white-300/10 text-xs mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                Email bloqueado para alterações
                            </p>
                        </div>
                    </div>
                    
                    <!-- ALTERACAO DE SENHA -->
                    <div class="border-t border-white/10 pt-6 mt-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Alteração de Senha</h3>
                        <p class="text-white/70 mb-4">Preencha apenas se desejar alterar sua senha</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="senha_atual" class="block text-white font-medium mb-2">Senha Atual</label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="senha_atual" 
                                        name="senha_atual" 
                                        class="password-field w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    >
                                    <button 
                                        type="button" 
                                        class="toggle-senha-atual absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition-colors"
                                        onclick="toggleSenhaAtual()"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="show-password-icon">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hide-password-icon hidden">
                                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                            <line x1="2" x2="22" y1="2" y2="22"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label for="nova_senha" class="block text-white font-medium mb-2">Nova Senha</label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="nova_senha" 
                                        name="nova_senha" 
                                        class="password-field w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    >
                                    <button 
                                        type="button" 
                                        class="toggle-novas-senhas absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition-colors"
                                        onclick="toggleNovasSenhas()"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="show-password-icon">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hide-password-icon hidden">
                                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                            <line x1="2" x2="22" y1="2" y2="22"></line>
                                        </svg>
                                    </button>
                                </div>
                                <p class="text-white/50 text-sm mt-1">Mínimo de 5 caracteres</p>
                            </div>
                            
                            <div>
                                <label for="confirmar_senha" class="block text-white font-medium mb-2">Confirmar Nova Senha</label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="confirmar_senha" 
                                        name="confirmar_senha" 
                                        class="password-field w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    >
                                    <button 
                                        type="button" 
                                        class="toggle-novas-senhas absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white/80 transition-colors"
                                        onclick="toggleNovasSenhas()"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="show-password-icon">
                                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hide-password-icon hidden">
                                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                            <line x1="2" x2="22" y1="2" y2="22"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOCUMENTOS PESSOAIS -->
                    <div class="border-t border-white/10 pt-6 mt-6">
                        <h3 class="text-xl font-semibold text-white mb-4">Documentos Pessoais</h3>
                        <p class="text-white/70 mb-4">Estes documentos são necessários para completar seu cadastro e alugar veículos</p>
                        
                        <div class="mb-6">
                            <div>
                                <label for="cpf" class="block text-white font-medium mb-2">CPF</label>
                                <input 
                                    type="text" 
                                    id="cpf" 
                                    name="cpf" 
                                    class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 <?= $docsAprovados ? 'opacity-75' : '' ?>"
                                    value="<?= htmlspecialchars($docData['cpf'] ?? '') ?>"
                                    placeholder="000.000.000-00"
                                    <?= $docsAprovados ? 'readonly' : '' ?>
                                >
                                <p class="text-white/50 text-sm mt-1">
                                    <?php if ($docsAprovados): ?>
                                        CPF não pode ser alterado após aprovação dos documentos.
                                    <?php else: ?>
                                        Digite apenas números
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <div class="flex items-center mb-4">
                                <input 
                                    type="checkbox" 
                                    id="tem_cnh" 
                                    name="tem_cnh" 
                                    class="w-5 h-5 rounded bg-white/5 border border-white/10 text-indigo-500 focus:ring-indigo-500/50"
                                    <?= ($docData['tem_cnh'] ?? false) ? 'checked' : '' ?>
                                    <?= $jaEnviouDocumentos ? 'disabled' : '' ?>
                                >
                                <label for="tem_cnh" class="ml-2 text-white">Possuo CNH</label>
                                
                                <?php if ($jaEnviouDocumentos && ($docData['tem_cnh'] ?? false)): ?>
                                    <span class="ml-2 text-xs bg-indigo-500/30 text-indigo-200 py-1 px-2 rounded-full">
                                        Confirmado
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-amber-400/80 text-sm mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block mr-1">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                Importante: É necessário possuir CNH válida para alugar veículos
                                <?php if ($jaEnviouDocumentos): ?>
                                    <br>
                                    <span class="text-white/70 mt-1 inline-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline-block mr-1">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        </svg>
                                        Após o envio dos documentos, esta opção não pode ser alterada.
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div id="cnh_upload_container" class="<?= ($docData['tem_cnh'] ?? false) ? '' : 'hidden' ?>">
                            <h4 class="text-lg font-medium text-white mb-3">Carteira Nacional de Habilitação (CNH)</h4>
                            
                            <!-- INÍCIO DA SUBSTITUIÇÃO -->
                            <?php if ($jaEnviouDocumentos): ?>
                                <!-- Mostrar as imagens dos documentos -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4">
                                    <?php if ($temCnhFrente): 
                                        $imgFrenteUrl = getImagemUrl($docData['foto_cnh_frente']);
                                        $arquivoFrenteExiste = verificarArquivo($docData['foto_cnh_frente']);
                                    ?>
                                        <div class="doc-image-container">
                                            <h5 class="text-white font-medium">Frente da CNH</h5>
                                            
                                            <?php if ($arquivoFrenteExiste): ?>
                                                <img src="<?= $imgFrenteUrl ?>" alt="CNH Frente" class="doc-image">
                                                
                                                <a href="download_documento.php?tipo=frente&id=<?= $usuario['id'] ?>" 
                                                class="mt-3 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                    Baixar Imagem
                                                </a>
                                            <?php else: ?>
                                                <div class="bg-red-500/20 border border-red-400/30 text-white px-4 py-3 rounded-xl text-sm text-center">
                                                    <p>Arquivo não encontrado</p>
                                                    <p class="text-xs mt-1">Caminho: <?= htmlspecialchars($docData['foto_cnh_frente']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($temCnhVerso): 
                                        $imgVersoUrl = getImagemUrl($docData['foto_cnh_verso']);
                                        $arquivoVersoExiste = verificarArquivo($docData['foto_cnh_verso']);
                                    ?>
                                        <div class="doc-image-container">
                                            <h5 class="text-white font-medium">Verso da CNH</h5>
                                            
                                            <?php if ($arquivoVersoExiste): ?>
                                                <img src="<?= $imgVersoUrl ?>" alt="CNH Verso" class="doc-image">
                                                
                                                <a href="download_documento.php?tipo=verso&id=<?= $usuario['id'] ?>" 
                                                class="mt-3 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg flex items-center justify-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                    Baixar Imagem
                                                </a>
                                            <?php else: ?>
                                                <div class="bg-red-500/20 border border-red-400/30 text-white px-4 py-3 rounded-xl text-sm text-center">
                                                    <p>Arquivo não encontrado</p>
                                                    <p class="text-xs mt-1">Caminho: <?= htmlspecialchars($docData['foto_cnh_verso']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Informação adicional quando o documento foi encontrado -->
                                <?php if (!$docData['status_docs'] == 'aprovado' && !$docData['status_docs'] == 'rejeitado' && ($arquivoFrenteExiste || $arquivoVersoExiste)): ?>
                                    <div class="bg-indigo-500/20 border border-indigo-400/30 rounded-xl p-4 mb-4">
                                        <p class="flex items-center text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            Após o envio, os documentos não podem ser alterados. Se precisar atualizar seus documentos, entre em contato com o suporte.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- Campos de upload de documentos quando ainda não enviou -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="foto_cnh_frente" class="block text-white font-medium mb-2">Frente da CNH</label>
                                        <input 
                                            type="file" 
                                            id="foto_cnh_frente" 
                                            name="foto_cnh_frente" 
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                            accept="image/*"
                                        >
                                        <img id="preview_cnh_frente" class="file-upload-preview hidden" alt="Preview CNH Frente">
                                    </div>
                                    
                                    <div>
                                        <label for="foto_cnh_verso" class="block text-white font-medium mb-2">Verso da CNH</label>
                                        <input 
                                            type="file" 
                                            id="foto_cnh_verso" 
                                            name="foto_cnh_verso" 
                                            class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                            accept="image/*"
                                        >
                                        <img id="preview_cnh_verso" class="file-upload-preview hidden" alt="Preview CNH Verso">
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- FIM DA SUBSTITUIÇÃO -->
                        </div>
                        
                        <?php 
                        // Mostrar o status apenas se o cadastro estiver completo ou se documentos já tiverem sido enviados
                        $temDocumentos = !empty($docData['foto_cnh_frente']) || !empty($docData['foto_cnh_verso']);
                        $statusPreenchido = isset($docData['status_docs']) && !empty($docData['status_docs']);
                        $cadastroCompleto = isset($docData['cadastro_completo']) && $docData['cadastro_completo'];

                        if ($statusPreenchido && ($temDocumentos || $cadastroCompleto)): 
                        ?>
                            <div class="mt-6 p-4 rounded-xl 
                                <?php if ($docData['status_docs'] == 'aprovado'): ?>
                                    bg-emerald-500/20 border border-emerald-400/30
                                <?php elseif ($docData['status_docs'] == 'rejeitado'): ?>
                                    bg-red-500/20 border border-red-400/30
                                <?php else: ?>
                                    bg-amber-500/20 border border-amber-400/30
                                <?php endif; ?>
                            ">
                                <h5 class="flex items-center font-medium mb-2
                                    <?php if ($docData['status_docs'] == 'aprovado'): ?>
                                        text-emerald-300
                                    <?php elseif ($docData['status_docs'] == 'rejeitado'): ?>
                                        text-red-300
                                    <?php else: ?>
                                        text-amber-300
                                    <?php endif; ?>
                                ">
                                    <?php if ($docData['status_docs'] == 'aprovado'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        Documentos Aprovados
                                    <?php elseif ($docData['status_docs'] == 'rejeitado'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                        Documentos Rejeitados
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        Documentos em Análise
                                    <?php endif; ?>
                                </h5>
                                <p class="text-white">
                                    <?php if ($docData['status_docs'] == 'aprovado'): ?>
                                        Seus documentos foram verificados e aprovados. Você já pode alugar veículos em nossa plataforma.
                                    <?php elseif ($docData['status_docs'] == 'rejeitado'): ?>
                                        Seus documentos foram analisados, mas não puderam ser aprovados.
                                        <?php if (!empty($docData['observacoes_docs'])): ?>
                                            <br><strong>Motivo:</strong> <?= htmlspecialchars($docData['observacoes_docs']) ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Seus documentos foram enviados e estão sendo analisados pela nossa equipe. Isso pode levar até 24 horas.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl transition-colors border border-emerald-400/30 px-6 py-3 font-medium shadow-md hover:shadow-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="container mx-auto mt-16 px-4 pb-8 text-center text-white/60 text-sm">
        <p>© <script>document.write(new Date().getFullYear())</script> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <!-- Incluir script de notificações -->
    <script src="../assets/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Código existente de notificações

            const temCnhCheckbox = document.getElementById('tem_cnh');
            const jaEnviouDocumentos = <?= $jaEnviouDocumentos ? 'true' : 'false' ?>;
            const docsAprovados = <?= ($docData['status_docs'] == 'aprovado') ? 'true' : 'false' ?>;
            
            if (temCnhCheckbox && jaEnviouDocumentos) {
                // Adiciona um hidden input para garantir que o valor seja enviado mesmo com o checkbox desabilitado
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'tem_cnh';
                hiddenInput.value = temCnhCheckbox.checked ? '1' : '0';
                temCnhCheckbox.parentNode.appendChild(hiddenInput);
                
                // Desabilita mudanças no checkbox
                temCnhCheckbox.addEventListener('click', function(e) {
                    if (jaEnviouDocumentos) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

            <?php if ($erro): ?>
                notifyError('<?= addslashes($erro) ?>');
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                notifySuccess('<?= addslashes($sucesso) ?>');
            <?php endif; ?>

            // Formatação de CPF
            const cpfInput = document.getElementById('cpf');
            if (cpfInput) {
                cpfInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                    
                    // Formatar CPF (XXX.XXX.XXX-XX)
                    if (value.length > 9) {
                        value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{2}).*/, '$1.$2.$3-$4');
                    } else if (value.length > 6) {
                        value = value.replace(/^(\d{3})(\d{3})(\d{3}).*/, '$1.$2.$3');
                    } else if (value.length > 3) {
                        value = value.replace(/^(\d{3})(\d{3}).*/, '$1.$2');
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Controle de visibilidade dos campos de CNH
            const cnhContainer = document.getElementById('cnh_upload_container');
            
            if (temCnhCheckbox && cnhContainer) {
                temCnhCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        cnhContainer.classList.remove('hidden');
                    } else {
                        cnhContainer.classList.add('hidden');
                        // Limpar os campos de upload quando desmarcado
                        document.getElementById('foto_cnh_frente').value = '';
                        document.getElementById('foto_cnh_verso').value = '';
                        
                        // Esconder pré-visualizações
                        const previewCnhFrente = document.getElementById('preview_cnh_frente');
                        const previewCnhVerso = document.getElementById('preview_cnh_verso');
                        
                        if (previewCnhFrente) previewCnhFrente.classList.add('hidden');
                        if (previewCnhVerso) previewCnhVerso.classList.add('hidden');
                    }
                });
            }
            
            // Pré-visualização das imagens de documentos
            const setupImagePreview = (inputId, previewId) => {
                const input = document.getElementById(inputId);
                const preview = document.getElementById(previewId);
                
                if (input && preview) {
                    input.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                preview.src = e.target.result;
                                preview.classList.remove('hidden');
                            };
                            
                            reader.readAsDataURL(this.files[0]);
                        }
                    });
                }
            };
            
            // Configurar pré-visualizações para cada campo de upload
            setupImagePreview('foto_cnh_frente', 'preview_cnh_frente');
            setupImagePreview('foto_cnh_verso', 'preview_cnh_verso');
        });
        
        // Funções existentes para alternar senha
        function toggleSenhaAtual() {
            const senhaAtualField = document.getElementById('senha_atual');
            const button = document.querySelector('.toggle-senha-atual');
            const showIcon = button.querySelector('.show-password-icon');
            const hideIcon = button.querySelector('.hide-password-icon');
            
            if (senhaAtualField.type === 'password') {
                senhaAtualField.type = 'text';
                showIcon.classList.add('hidden');
                hideIcon.classList.remove('hidden');
            } else {
                senhaAtualField.type = 'password';
                showIcon.classList.remove('hidden');
                hideIcon.classList.add('hidden');
            }
        }
        
        function toggleNovasSenhas() {
            // Seleciona os campos de nova senha e confirmação
            const novaSenhaField = document.getElementById('nova_senha');
            const confirmarSenhaField = document.getElementById('confirmar_senha');
            
            // Seleciona os botões de alternância para as novas senhas
            const buttons = document.querySelectorAll('.toggle-novas-senhas');
            
            // Verificamos o estado atual com base no campo de nova senha
            const isCurrentlyPassword = novaSenhaField.type === 'password';
            
            // Alteramos os dois campos de senha para o novo tipo
            novaSenhaField.type = isCurrentlyPassword ? 'text' : 'password';
            confirmarSenhaField.type = isCurrentlyPassword ? 'text' : 'password';
            
            // Atualizamos os ícones em todos os botões de alternância das novas senhas
            buttons.forEach(button => {
                const showIcon = button.querySelector('.show-password-icon');
                const hideIcon = button.querySelector('.hide-password-icon');
                
                if (isCurrentlyPassword) {
                    // Mudar para texto (mostrar senha)
                    showIcon.classList.add('hidden');
                    hideIcon.classList.remove('hidden');
                } else {
                    // Mudar para password (ocultar senha)
                    showIcon.classList.remove('hidden');
                    hideIcon.classList.add('hidden');
                }
            });
        }

        // Função estendida para validar o formulário antes de enviar
        function validarFormulario() {
            let valido = true;
            const primeiroNome = document.getElementById('primeiro_nome').value.trim();
            const segundoNome = document.getElementById('segundo_nome').value.trim();
            const senhaAtual = document.getElementById('senha_atual').value;
            const novaSenha = document.getElementById('nova_senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;
            const cpf = document.getElementById('cpf').value.trim();
            const temCnh = document.getElementById('tem_cnh').checked;
            const telefone = document.getElementById('telefone').value.trim();
            
            const jaEnviouDocumentos = <?= $jaEnviouDocumentos ? 'true' : 'false' ?>;
            // Limpar qualquer notificação anterior
            if (typeof clearNotifications === 'function') {
                clearNotifications();
            }

            // Validar nome e sobrenome
            if (primeiroNome.length < 3) {
                notifyError('O primeiro nome deve ter pelo menos 3 caracteres.', 12000);
                valido = false;
            }
            
            if (segundoNome.length < 3) {
                notifyError('O sobrenome deve ter pelo menos 3 caracteres.', 12000);
                valido = false;
            }
            
            // Se o usuário está tentando mudar a senha
            if (novaSenha || confirmarSenha) {
                // Verificar se a senha atual foi preenchida
                if (!senhaAtual) {
                    notifyError('Para alterar a senha, informe a senha atual.', 12000);
                    valido = false;
                }
                
                // Verificar se a nova senha tem o tamanho mínimo
                if (novaSenha.length < 5) {
                    notifyError('A nova senha deve ter no mínimo 5 caracteres.', 12000);
                    valido = false;
                }
                
                // Verificar se a confirmação coincide com a nova senha
                if (novaSenha !== confirmarSenha) {
                    notifyError('A nova senha e a confirmação não coincidem.', 12000);
                    valido = false;
                }
            }

            if (cpf === null || cpf === '') {
                // Verificar se o CPF foi preenchido
                notifyError('O CPF não pode estar vazio.', 12000);
                valido = false;
            }
            
            // Validar CPF apenas se estiver preenchido
            if (cpf && !docsAprovados) {
                // Remover formatação para validação
                const cpfLimpo = cpf.replace(/[^\d]/g, '');
                
                // Verificar se tem 11 dígitos
                if (cpfLimpo.length !== 11) {
                    notifyError('CPF inválido. O CPF deve conter 11 dígitos.', 12000);
                    valido = false;
                } else {
                    // Verificar se todos os dígitos são iguais (CPF inválido)
                    const todosDigitosIguais = /^(\d)\1+$/.test(cpfLimpo);
                    if (todosDigitosIguais) {
                        notifyError('CPF inválido. Todos os dígitos são iguais.', 12000);
                        valido = false;
                    } else {
                        // Validação completa do CPF
                        let soma = 0;
                        let resto;
                        
                        // Primeiro dígito verificador
                        for (let i = 1; i <= 9; i++) {
                            soma = soma + parseInt(cpfLimpo.substring(i-1, i)) * (11 - i);
                        }
                        resto = (soma * 10) % 11;
                        
                        if ((resto === 10) || (resto === 11)) {
                            resto = 0;
                        }
                        
                        if (resto !== parseInt(cpfLimpo.substring(9, 10))) {
                            notifyError('CPF inválido. Dígitos verificadores incorretos.', 12000);
                            valido = false;
                        } else {
                            // Segundo dígito verificador
                            soma = 0;
                            for (let i = 1; i <= 10; i++) {
                                soma = soma + parseInt(cpfLimpo.substring(i-1, i)) * (12 - i);
                            }
                            resto = (soma * 10) % 11;
                            
                            if ((resto === 10) || (resto === 11)) {
                                resto = 0;
                            }
                            
                            if (resto !== parseInt(cpfLimpo.substring(10, 11))) {
                                notifyError('CPF inválido. Dígitos verificadores incorretos.', 12000);
                                valido = false;
                            }
                        }
                    }
                }
            }
            
            // Se tem CPF preenchido e marcou que tem CNH, verificar se os documentos da CNH foram enviados
            if (cpf && temCnh && !jaEnviouDocumentos) {
                const cnhFrente = document.getElementById('foto_cnh_frente');
                const cnhVerso = document.getElementById('foto_cnh_verso');
                
                if (cnhFrente.files.length === 0 || cnhVerso.files.length === 0) {
                    notifyError('É necessário enviar frente e verso da CNH se você possui uma.', 12000);
                    valido = false;
                }
            }

            if (telefone) {
                // Remover formatação para validação
                const telefoneLimpo = telefone.replace(/\D/g, '');
                
                // Verificar se tem pelo menos 10 dígitos (DDD + número)
                if (telefoneLimpo.length < 10) {
                    notifyError('Telefone inválido. Digite o DDD e o número completo.', 12000);
                    valido = false;
                }
            }
            
            return valido;
        }

        // Modal para visualização de imagens
        function setupImageViewer() {
            // Adicionar modal ao body
            const modalHtml = `
                <div id="imageModal" class="modal">
                    <span class="modal-close">&times;</span>
                    <img class="modal-content" id="modalImage">
                </div>
            `;
            
            // Adicionar o modal ao final do body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Obter referências
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const closeBtn = document.querySelector('.modal-close');
            
            // Configurar todas as imagens de documentos para abrir no modal ao clicar
            document.querySelectorAll('.doc-image').forEach(img => {
                img.style.cursor = 'pointer';
                img.addEventListener('click', function() {
                    modal.style.display = 'block';
                    modalImg.src = this.src;
                });
            });
            
            // Fechar modal ao clicar no X
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
            
            // Fechar modal ao clicar fora da imagem
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Inicializar visualizador de imagens
        if (document.querySelectorAll('.doc-image').length > 0) {
            setupImageViewer();
        }

        // Formatação de telefone
        const telefoneInput = document.getElementById('telefone');
        if (telefoneInput) {
            telefoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                // Formatar telefone: (XX) XXXXX-XXXX para celular ou (XX) XXXX-XXXX para fixo
                if (value.length > 10) {
                    // Celular com 9 dígitos
                    value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
                } else if (value.length > 6) {
                    // Telefone fixo com 8 dígitos
                    value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                } else if (value.length > 2) {
                    // Só DDD
                    value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
                }
                
                e.target.value = value;
            });
        }

        <?php
        if (isset($_SESSION['notification'])) {
            $type = $_SESSION['notification']['type'];
            $message = $_SESSION['notification']['message'];
            
            if ($type === 'success') {
                echo "notifySuccess('" . addslashes($message) . "');";
            } elseif ($type === 'error') {
                echo "notifyError('" . addslashes($message) . "', 12000);";
            } elseif ($type === 'warning') {
                echo "notifyWarning('" . addslashes($message) . "');";
            } else {
                echo "notifyInfo('" . addslashes($message) . "');";
            }
            
            // Limpar a notificação da sessão após exibi-la
            unset($_SESSION['notification']);
        }
        ?>
    </script>
</body>
</html>