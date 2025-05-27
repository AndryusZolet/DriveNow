#!/bin/bash
# Script para atualizar automaticamente os status das reservas
# Adicione este script ao crontab para ser executado diariamente

# Caminho para o diretório do projeto
PROJECT_DIR="/Applications/XAMPP/xamppfiles/htdocs/drivenowatt"

# Verificar se o diretório existe
if [ ! -d "$PROJECT_DIR" ]; then
    echo "Diretório do projeto não encontrado: $PROJECT_DIR"
    exit 1
fi

# Data e hora da execução
echo "===== Atualizando status de reservas ====="
echo "Data: $(date)"

# Executar o script PHP de atualização
php "$PROJECT_DIR/api/atualizar_status_reservas.php"

echo "===== Atualização concluída ====="
