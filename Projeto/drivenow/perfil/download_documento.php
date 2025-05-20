<?php
// Arquivo para download de documentos
require_once '../includes/auth.php';

// Verificar se o usuário está logado
verificarAutenticacao();

// Verificar permissões
$usuarioLogado = getUsuario();
$solicitadoId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Validar tipo
if (!in_array($tipo, ['frente', 'verso'])) {
    die('Tipo de documento inválido.');
}

// Segurança: Um usuário só pode baixar seus próprios documentos
// Administradores podem ver documentos de qualquer usuário (verificar função de admin se existir)
if ($usuarioLogado['id'] != $solicitadoId) {
    // Verificar se o usuário atual é administrador (adapte para sua lógica)
    if (!isset($usuarioLogado['admin']) || !$usuarioLogado['admin']) {
        die('Acesso não autorizado.');
    }
}

// Buscar informações do documento
global $pdo;
$campo = $tipo === 'frente' ? 'foto_cnh_frente' : 'foto_cnh_verso';
$stmt = $pdo->prepare("SELECT {$campo} FROM conta_usuario WHERE id = ?");
$stmt->execute([$solicitadoId]);
$resultado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resultado || empty($resultado[$campo])) {
    die('Documento não encontrado.');
}

$arquivoPath = '../' . $resultado[$campo];

// Verificar se o arquivo existe
if (!file_exists($arquivoPath) || !is_readable($arquivoPath)) {
    die('Arquivo não encontrado ou não pode ser lido.');
}

// Obter informações do arquivo
$fileInfo = pathinfo($arquivoPath);
$extension = strtolower($fileInfo['extension']);

// Definir tipo MIME com base na extensão
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'pdf' => 'application/pdf'
];

$contentType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';

// Preparar para download
$filename = "cnh_{$tipo}_{$solicitadoId}.{$extension}";

// Limpar qualquer saída anterior
ob_clean();

// Enviar headers para download
header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($arquivoPath));

// Ler e enviar o arquivo
readfile($arquivoPath);
exit;
?>