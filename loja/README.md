# Marketplace MTech

Marketplace completo com integração ao Mercado Livre, autenticação Google OAuth 2.0 e sistema de temas personalizáveis.

## 🚀 Funcionalidades

- ✅ **Login com Google OAuth 2.0** - Autenticação segura usando conta Google
- ✅ **Integração com Mercado Livre** - Sincronização automática de anúncios
- ✅ **Carteira Digital** - Visualização de saldo e transações do Mercado Pago
- ✅ **Temas Personalizáveis** - Sistema completo de customização de cores
- ✅ **Dashboard Intuitivo** - Estatísticas e ações rápidas
- ✅ **Responsive Design** - Interface adaptável para mobile e desktop

## 📋 Requisitos

- PHP 7.4 ou superior
- PostgreSQL 12 ou superior
- Extensões PHP: curl, pdo, pdo_pgsql
- Servidor web (Apache/Nginx)

## ⚙️ Instalação

### 1. Configure o Banco de Dados

Execute o script SQL para criar as tabelas:

```bash
psql -U seu_usuario -d seu_banco -f loja/sql/schema.sql
```

Ou conecte-se ao PostgreSQL e execute:

```sql
\i loja/sql/schema.sql
```

### 2. Configure as Credenciais

Edite o arquivo `loja/config.php` e configure:

#### Banco de Dados PostgreSQL
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'mtechservicos');
```

#### Google OAuth 2.0
1. Acesse [Google Cloud Console](https://console.cloud.google.com/)
2. Crie um novo projeto
3. Ative a API Google+ 
4. Crie credenciais OAuth 2.0
5. Configure a URL de redirecionamento: `http://seu-dominio/loja/callback_google.php`
6. Copie Client ID e Client Secret para o config.php

```php
define('GOOGLE_CLIENT_ID', 'seu-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'seu-client-secret');
define('GOOGLE_REDIRECT_URI', 'http://seu-dominio/loja/callback_google.php');
```

#### Mercado Livre API
1. Acesse [Mercado Livre Developers](https://developers.mercadolivre.com.br/)
2. Crie uma nova aplicação
3. Configure a URL de redirecionamento: `http://seu-dominio/loja/callback_ml.php`
4. Copie App ID e Client Secret para o config.php

```php
define('ML_APP_ID', 'seu-app-id');
define('ML_CLIENT_SECRET', 'seu-client-secret');
define('ML_REDIRECT_URI', 'http://seu-dominio/loja/callback_ml.php');
```

### 3. Configure Permissões

```bash
chmod 755 loja/
chmod 644 loja/*.php
chmod 600 loja/config.php
```

## 🎨 Sistema de Temas

O sistema inclui 5 temas pré-definidos:
- **Padrão** - Azul e verde
- **Escuro** - Tema dark mode
- **Roxo** - Rosa e roxo
- **Oceano** - Azul claro
- **Floresta** - Verde natural

Você pode personalizar:
- Cor primária
- Cor secundária
- Cor de fundo
- Cor do texto
- Cor dos cards

## 📁 Estrutura de Arquivos

```
loja/
├── classes/
│   ├── GoogleAuth.php          # Autenticação Google
│   ├── MercadoLivreAPI.php     # API Mercado Livre
│   └── ThemeManager.php        # Gerenciador de temas
├── sql/
│   └── schema.sql              # Schema do banco de dados
├── config.php                  # Configurações principais
├── login.php                   # Página de login
├── callback_google.php         # Callback OAuth Google
├── callback_ml.php             # Callback OAuth Mercado Livre
├── dashboard.php               # Dashboard principal
├── produtos.php                # Listagem de produtos
├── carteira.php                # Carteira e transações
├── configuracoes.php           # Configurações e temas
├── conectar_ml.php             # Conectar Mercado Livre
├── sincronizar.php             # Sincronização manual
├── logout.php                  # Logout
├── api_theme.php               # API de temas
└── api_stats.php               # API de estatísticas
```

## 🔄 Sincronização com Mercado Livre

A sincronização importa:
- Todos os anúncios ativos
- Dados dos produtos (título, preço, estoque, etc)
- Imagens dos produtos
- Status e quantidade vendida
- Transações da carteira (experimental)

Para sincronizar manualmente, acesse o dashboard e clique em "Sincronizar".

## 🔐 Segurança

- Senhas nunca são armazenadas (OAuth)
- Tokens são armazenados criptografados
- Sessões com cookie HTTPOnly
- Proteção contra SQL Injection via PDO
- Sanitização de output com htmlspecialchars

## 🐛 Troubleshooting

### Erro ao conectar com Google
- Verifique se as credenciais estão corretas
- Confirme que a URL de redirecionamento está configurada no Google Console
- Certifique-se que a API Google+ está ativa

### Erro ao conectar com Mercado Livre
- Verifique as credenciais da aplicação
- Confirme a URL de redirecionamento no painel do desenvolvedor
- Verifique se a aplicação tem as permissões necessárias

### Produtos não aparecem
- Conecte sua conta do Mercado Livre
- Clique em "Sincronizar" no dashboard
- Verifique os logs em `sync_logs` no banco de dados

## 📞 Suporte

Para problemas ou dúvidas:
- Verifique os logs de erro do PHP
- Consulte a tabela `sync_logs` no banco de dados
- Revise as configurações no `config.php`

## 📝 Licença

Este projeto é proprietário da MTech Serviços.

## 🔄 Atualizações Futuras

- [ ] Sistema de notificações em tempo real
- [ ] Relatórios avançados de vendas
- [ ] Gestão de estoque integrada
- [ ] App mobile
- [ ] Exportação de dados (PDF/Excel)
- [ ] Multi-idiomas
- [ ] Integração com mais marketplaces

---

Desenvolvido por MTech Serviços © 2025
