#!/bin/bash
# Script de configuração do marketplace no servidor
# Execute como root: bash setup_server.sh

echo "=========================================="
echo "Configuração do Marketplace MTech"
echo "=========================================="

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Verificar PostgreSQL instalado
echo -e "\n${YELLOW}[1/5] Verificando PostgreSQL...${NC}"
if ! command -v psql &> /dev/null; then
    echo "PostgreSQL não encontrado. Instalando..."
    apt update
    apt install -y postgresql postgresql-contrib
    systemctl start postgresql
    systemctl enable postgresql
else
    echo -e "${GREEN}PostgreSQL já instalado${NC}"
fi

# 2. Criar usuário e banco de dados
echo -e "\n${YELLOW}[2/5] Criando banco de dados e usuário...${NC}"
sudo -u postgres psql -f /var/www/loja/sql/setup_database.sql
echo -e "${GREEN}Banco de dados criado${NC}"

# 3. Executar schema
echo -e "\n${YELLOW}[3/5] Criando tabelas...${NC}"
sudo -u postgres psql -d loja_mtechservicos -f /var/www/loja/sql/schema.sql
echo -e "${GREEN}Tabelas criadas${NC}"

# 4. Configurar permissões
echo -e "\n${YELLOW}[4/5] Configurando permissões...${NC}"
chown -R www-data:www-data /var/www/loja
chmod 755 /var/www/loja
chmod 644 /var/www/loja/*.php
chmod 600 /var/www/loja/config.php
chmod 755 /var/www/loja/classes
chmod 644 /var/www/loja/classes/*.php
chmod 755 /var/www/loja/sql
echo -e "${GREEN}Permissões configuradas${NC}"

# 5. Verificar extensões PHP
echo -e "\n${YELLOW}[5/5] Verificando extensões PHP...${NC}"
php -m | grep -E "pdo|pgsql|curl" || {
    echo "Instalando extensões PHP..."
    apt install -y php-pgsql php-curl php-mbstring
    systemctl restart apache2 2>/dev/null || systemctl restart nginx 2>/dev/null
}
echo -e "${GREEN}Extensões PHP OK${NC}"

echo -e "\n${GREEN}=========================================="
echo "Configuração concluída!"
echo "==========================================${NC}"
echo ""
echo "Próximos passos:"
echo "1. Edite /var/www/loja/config.php com as credenciais OAuth"
echo "2. Configure as URLs de callback:"
echo "   - Google: https://mtechservicos.com/loja/callback_google.php"
echo "   - Mercado Livre: https://mtechservicos.com/loja/callback_ml.php"
echo "3. Acesse: https://mtechservicos.com/loja/login.php"
echo ""
echo "Credenciais do banco:"
echo "  Usuário: loja_user"
echo "  Senha: Loja@Mtech2025!"
echo "  Banco: loja_mtechservicos"
echo ""
