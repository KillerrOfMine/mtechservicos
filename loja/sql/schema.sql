-- Schema do Marketplace para PostgreSQL
-- Criado em: 2025-11-01

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios_loja (
    id SERIAL PRIMARY KEY,
    google_id VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    nome VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(500),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    ativo BOOLEAN DEFAULT TRUE
);

CREATE INDEX IF NOT EXISTS idx_usuarios_google_id ON usuarios_loja(google_id);
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios_loja(email);

-- Tabela de configurações de tema por usuário
CREATE TABLE IF NOT EXISTS tema_config (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    cor_primaria VARCHAR(7) DEFAULT '#1a73e8',
    cor_secundaria VARCHAR(7) DEFAULT '#34a853',
    cor_fundo VARCHAR(7) DEFAULT '#ffffff',
    cor_texto VARCHAR(7) DEFAULT '#202124',
    cor_card VARCHAR(7) DEFAULT '#f8f9fa',
    fonte_principal VARCHAR(100) DEFAULT 'Roboto',
    tema_escuro BOOLEAN DEFAULT FALSE,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_loja(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_tema_usuario ON tema_config(usuario_id);

-- Trigger para atualizar data_atualizacao automaticamente
CREATE OR REPLACE FUNCTION update_tema_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_tema_timestamp
BEFORE UPDATE ON tema_config
FOR EACH ROW
EXECUTE FUNCTION update_tema_timestamp();

-- Tabela de tokens do Mercado Livre
CREATE TABLE IF NOT EXISTS ml_tokens (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    token_type VARCHAR(50) DEFAULT 'Bearer',
    expires_in INTEGER NOT NULL,
    user_id_ml BIGINT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_expiracao TIMESTAMP NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_loja(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_ml_tokens_usuario ON ml_tokens(usuario_id);
CREATE INDEX IF NOT EXISTS idx_ml_tokens_expiracao ON ml_tokens(data_expiracao);

-- Tabela de produtos/anúncios do Mercado Livre
CREATE TABLE IF NOT EXISTS produtos_ml (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    ml_id VARCHAR(50) UNIQUE NOT NULL,
    titulo TEXT NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    moeda VARCHAR(10) DEFAULT 'BRL',
    quantidade_disponivel INTEGER DEFAULT 0,
    quantidade_vendida INTEGER DEFAULT 0,
    condicao VARCHAR(20),
    categoria_id VARCHAR(50),
    thumbnail VARCHAR(500),
    permalink VARCHAR(500),
    status VARCHAR(50),
    data_sincronizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_loja(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_produtos_usuario ON produtos_ml(usuario_id);
CREATE INDEX IF NOT EXISTS idx_produtos_ml_id ON produtos_ml(ml_id);
CREATE INDEX IF NOT EXISTS idx_produtos_status ON produtos_ml(status);

-- Trigger para atualizar data_sincronizacao automaticamente
CREATE OR REPLACE FUNCTION update_produto_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_sincronizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_produto_timestamp
BEFORE UPDATE ON produtos_ml
FOR EACH ROW
EXECUTE FUNCTION update_produto_timestamp();

-- Tabela de imagens dos produtos
CREATE TABLE IF NOT EXISTS produto_imagens (
    id SERIAL PRIMARY KEY,
    produto_id INTEGER NOT NULL,
    url VARCHAR(500) NOT NULL,
    ordem INTEGER DEFAULT 0,
    FOREIGN KEY (produto_id) REFERENCES produtos_ml(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_produto_imagens_produto ON produto_imagens(produto_id);

-- Tabela de transações da carteira Mercado Livre
CREATE TABLE IF NOT EXISTS ml_transacoes (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    transacao_id BIGINT UNIQUE NOT NULL,
    tipo VARCHAR(50),
    status VARCHAR(50),
    valor DECIMAL(10,2) NOT NULL,
    moeda VARCHAR(10) DEFAULT 'BRL',
    descricao TEXT,
    data_criacao TIMESTAMP,
    data_aprovacao TIMESTAMP NULL,
    data_sincronizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_loja(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_transacoes_usuario ON ml_transacoes(usuario_id);
CREATE INDEX IF NOT EXISTS idx_transacoes_id ON ml_transacoes(transacao_id);
CREATE INDEX IF NOT EXISTS idx_transacoes_data ON ml_transacoes(data_criacao);

-- Tabela de logs de sincronização
CREATE TABLE IF NOT EXISTS sync_logs (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    mensagem TEXT,
    data_execucao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_loja(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_sync_logs_usuario ON sync_logs(usuario_id);
CREATE INDEX IF NOT EXISTS idx_sync_logs_data ON sync_logs(data_execucao);
