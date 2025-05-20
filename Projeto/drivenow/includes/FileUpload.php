<?php
/**
 * Classe para upload de arquivos
 * Este arquivo deve ser colocado em: includes/FileUpload.php
 */

class FileUpload {
    private $uploadDir;
    private $allowedExtensions;
    private $maxFileSize;
    private $errors = [];

    /**
     * Construtor da classe
     * 
     * @param string $uploadDir Diretório para upload dos arquivos
     * @param array $allowedExtensions Extensões permitidas
     * @param int $maxFileSize Tamanho máximo do arquivo em bytes (padrão 5MB)
     */
    public function __construct($uploadDir = 'uploads/', $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'], $maxFileSize = 5242880) {
        // Garantir que o diretório termina com uma barra
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;

        // Criar diretório se não existir
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload de arquivo
     * 
     * @param array $file Array $_FILES do arquivo
     * @param string $prefix Prefixo para o nome do arquivo (opcional)
     * @return array|bool Caminho do arquivo ou false em caso de erro
     */
    public function uploadFile($file, $prefix = '') {
        // Verificar se o arquivo foi enviado
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->addError('Falha no upload do arquivo. Código de erro: ' . $file['error']);
            return false;
        }

        // Verificar tamanho
        if ($file['size'] > $this->maxFileSize) {
            $this->addError('O arquivo excede o tamanho máximo permitido de ' . $this->formatSize($this->maxFileSize));
            return false;
        }

        // Verificar tipo/extensão
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension']);
        
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->addError('Tipo de arquivo não permitido. Extensões permitidas: ' . implode(', ', $this->allowedExtensions));
            return false;
        }

        // Verificar se é realmente uma imagem, se for um dos tipos de imagem
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $check = getimagesize($file['tmp_name']);
            if ($check === false) {
                $this->addError('O arquivo enviado não é uma imagem válida.');
                return false;
            }
        }

        // Gerar nome único para o arquivo
        $newFileName = $prefix . uniqid() . '_' . time() . '.' . $extension;
        $destination = $this->uploadDir . $newFileName;

        // Mover arquivo para destino
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->addError('Não foi possível mover o arquivo para o destino.');
            return false;
        }

        // Calcular caminho relativo para armazenamento
        $baseDir = $_SERVER['DOCUMENT_ROOT'];
        $relativePath = str_replace($baseDir, '', $destination);
        
        // Caso o diretório não esteja dentro do document_root
        if (strpos($destination, $baseDir) === false) {
            // Pegamos o diretório pai
            $parentDir = basename(dirname(dirname($destination)));
            
            // Remover 'user_' duplicado
            if (strpos($parentDir, 'user_') === 0) {
                $userId = substr($parentDir, 5); // Remove o "user_" inicial
            } else {
                $userId = $parentDir;
            }
            
            $relativePath = 'uploads/user_' . $userId . '/docs/' . $newFileName;
            
            // Log para debug
            error_log("Caminho relativo gerado: " . $relativePath);
        }
        
        // Log para debug
        error_log("Caminho absoluto: " . $destination);
        error_log("Caminho relativo: " . $relativePath);

        // Retornar caminho relativo e informações do arquivo
        return [
            'path' => $relativePath,
            'absolute_path' => $destination,
            'name' => $newFileName,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'extension' => $extension
        ];
    }

    /**
     * Retorna erros ocorridos durante o upload
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Retorna o último erro
     */
    public function getLastError() {
        return end($this->errors);
    }

    /**
     * Adiciona um erro à lista de erros
     */
    private function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * Formata o tamanho do arquivo para exibição
     */
    private function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Gera um nome único para um diretório de usuário
     * 
     * @param int $userId ID do usuário
     * @return string Caminho do diretório
     */
    public function createUserDirectory($userId) {
        $dirPath = $this->uploadDir . 'user_' . $userId . '/docs/';
        
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0755, true);
        }
        
        return $dirPath;
    }
}