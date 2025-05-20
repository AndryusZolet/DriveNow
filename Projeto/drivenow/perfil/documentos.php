<?php
/**
 * Página de documentos do usuário
 * Este arquivo deve ser colocado em: perfil/documentos.php
 */

require_once '../includes/auth.php';
require_once '../includes/FileUpload.php';

// Verificar se o usuário está logado
verificarAutenticacao();

// Obter dados do usuário
$usuario = getUsuario();
$erro = '';
$sucesso = '';

// Verificar se o usuário já completou o cadastro
global $pdo;
$stmt = $pdo->prepare("SELECT cpf, foto_cnh_frente, foto_cnh_verso, 
                      status_docs, cadastro_completo, tem_cnh, observacoes_docs FROM conta_usuario WHERE id = ?");
$stmt->execute([$usuario['id']]);
$docData = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar se já possui documentos enviados
$temCnhFrente = !empty($docData['foto_cnh_frente']);
$temCnhVerso = !empty($docData['foto_cnh_verso']);
$jaEnviouDocumentos = $temCnhFrente && $temCnhVerso;

// Verificar se o usuário não enviou documentos ainda mas tem CNH marcada
$naoEnviouAinda = ($docData['tem_cnh'] == 1) && 
                 (!$jaEnviouDocumentos) &&
                 (empty($docData['status_docs']) || $docData['status_docs'] == 'pendente');

// Processar o formulário se foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicializar variáveis
    $cpf = trim($_POST['cpf'] ?? '');
    $temCnh = isset($_POST['tem_cnh']) ? 1 : 0;
    
    // Validar CPF
    if (empty($cpf)) {
        $erro = 'O CPF é obrigatório.';
    } elseif (!validarCPF($cpf)) {
        $erro = 'CPF inválido.';
    } else {
        // Verificar se já existe outro usuário com esse CPF
        $stmt = $pdo->prepare("SELECT id FROM conta_usuario WHERE cpf = ? AND id != ?");
        $stmt->execute([$cpf, $usuario['id']]);
        if ($stmt->rowCount() > 0) {
            $erro = 'Este CPF já está registrado para outro usuário.';
        } else {
            // Setup para upload de arquivos
            $uploadDir = '../uploads/user_' . $usuario['id'] . '/docs/';
            
            // Garantir que o diretório exista
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $erro = 'Não foi possível criar o diretório para upload: ' . $uploadDir;
                }
            }
            
            $fileUpload = new FileUpload($uploadDir);
            
            // Arrays para armazenar os caminhos dos arquivos
            $filesPaths = [
                'foto_cnh_frente' => $docData['foto_cnh_frente'] ?? null,
                'foto_cnh_verso' => $docData['foto_cnh_verso'] ?? null
            ];
            
            // Processa os uploads apenas se marcou que tem CNH
            $requiredFiles = [];
            if ($temCnh) {
                $requiredFiles[] = 'foto_cnh_frente';
                $requiredFiles[] = 'foto_cnh_verso';
            }
            
            // Verificar e fazer upload dos arquivos
            $arquivosEnviados = false;
            foreach ($requiredFiles as $field) {
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $result = $fileUpload->uploadFile($_FILES[$field], $field . '_');
                    if ($result) {
                        $filesPaths[$field] = $result['path'];
                        $arquivosEnviados = true;
                    } else {
                        $erro = 'Erro ao fazer upload do arquivo ' . getNomeAmigavel($field) . ': ' . $fileUpload->getLastError();
                        break;
                    }
                } elseif (!isset($docData[$field]) || empty($docData[$field])) {
                    // Se o arquivo é obrigatório e não foi enviado nem existe
                    $erro = 'O arquivo ' . getNomeAmigavel($field) . ' é obrigatório.';
                    break;
                }
            }
            
            // Se não houver erros, atualizar o banco de dados
            if (empty($erro)) {
                try {
                    // Verificar se todos documentos necessários estão presentes
                    $cadastroCompleto = true;
                    if ($temCnh) {
                        $cadastroCompleto = !empty($filesPaths['foto_cnh_frente']) && !empty($filesPaths['foto_cnh_verso']);
                    }
                    
                    // Define o status dos documentos
                    $statusDocs = null;
                    
                    // Se está enviando documentos pela primeira vez, marca como pendente
                    if ($arquivosEnviados) {
                        $statusDocs = 'pendente';
                    } 
                    // Se já tem um status e já tinha documentos, mantém o status
                    elseif (!empty($docData['status_docs']) && $jaEnviouDocumentos) {
                        $statusDocs = $docData['status_docs'];
                    }
                    
                    $stmt = $pdo->prepare("UPDATE conta_usuario SET 
                        cpf = ?, 
                        foto_cnh_frente = ?, 
                        foto_cnh_verso = ?,
                        tem_cnh = ?,
                        cadastro_completo = ?,
                        status_docs = ?
                        WHERE id = ?");
                    
                    $stmt->execute([
                        $cpf,
                        $filesPaths['foto_cnh_frente'],
                        $filesPaths['foto_cnh_verso'],
                        $temCnh,
                        $cadastroCompleto,
                        $statusDocs,
                        $usuario['id']
                    ]);
                    
                    $sucesso = 'Documentos enviados com sucesso! Aguarde a verificação pela nossa equipe.';
                    
                    // Atualizar dados da sessão
                    $_SESSION['usuario']['cadastro_completo'] = $cadastroCompleto;
                    
                    // Usar sistema de notificações
                    $_SESSION['notification'] = [
                        'type' => 'success',
                        'message' => 'Documentos enviados com sucesso!'
                    ];
                    
                    // Redirecionar após o sucesso
                    header('Location: ../vboard.php');
                    exit;
                    
                } catch (PDOException $e) {
                    $erro = 'Erro ao atualizar dados: ' . $e->getMessage();
                }
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
    }
    
    // Normalizar barras
    $caminho = str_replace('\\', '/', $caminho);
    
    // Adicionar o caminho base do site
    $caminhoBase = '../';
    
    return $caminhoBase . $caminho;
}

/**
 * Verifica se um arquivo existe e está acessível
 */
function verificarArquivo($caminho) {
    // Corrigir o caminho caso tenha "user_user_" duplicado
    if (strpos($caminho, 'user_user_') !== false) {
        $caminho = str_replace('user_user_', 'user_', $caminho);
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
            return true;
        }
    }
    
    return false;
}
?>