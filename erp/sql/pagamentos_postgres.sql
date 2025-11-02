-- ============================================
-- SISTEMA DE PAGAMENTOS COM AGRUPAMENTO
-- PostgreSQL Version
-- ============================================

-- Drop types if exist (para recriar)
DROP TYPE IF EXISTS pix_tipo_enum CASCADE;
DROP TYPE IF EXISTS tipo_conta_enum CASCADE;
DROP TYPE IF EXISTS tipo_documento_enum CASCADE;
DROP TYPE IF EXISTS status_conta_enum CASCADE;
DROP TYPE IF EXISTS status_lote_enum CASCADE;
DROP TYPE IF EXISTS metodo_pagamento_enum CASCADE;
DROP TYPE IF EXISTS tipo_evento_enum CASCADE;

-- Criar tipos ENUM
CREATE TYPE pix_tipo_enum AS ENUM ('CPF', 'CNPJ', 'EMAIL', 'TELEFONE', 'EVP');
CREATE TYPE tipo_conta_enum AS ENUM ('CORRENTE', 'POUPANCA');
CREATE TYPE tipo_documento_enum AS ENUM ('BOLETO', 'NOTA_FISCAL', 'RECIBO', 'FATURA', 'OUTROS');
CREATE TYPE status_conta_enum AS ENUM ('PENDENTE', 'AGRUPADA', 'PAGA', 'CANCELADA', 'ATRASADA');
CREATE TYPE status_lote_enum AS ENUM ('AGUARDANDO', 'PROCESSANDO', 'PAGO', 'ERRO', 'CANCELADO');
CREATE TYPE metodo_pagamento_enum AS ENUM ('PIX', 'TED', 'BOLETO');
CREATE TYPE tipo_evento_enum AS ENUM ('CRIADO', 'ENVIADO', 'CONFIRMADO', 'ERRO', 'CANCELADO');

-- Tabela de fornecedores
CREATE TABLE IF NOT EXISTS fornecedores (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf_cnpj VARCHAR(18) UNIQUE NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(20),
    pix_tipo pix_tipo_enum DEFAULT 'CPF',
    pix_chave VARCHAR(255),
    banco_codigo VARCHAR(10),
    banco_agencia VARCHAR(10),
    banco_conta VARCHAR(20),
    banco_conta_digito VARCHAR(2),
    banco_tipo_conta tipo_conta_enum DEFAULT 'CORRENTE',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_fornecedores_cpf_cnpj ON fornecedores(cpf_cnpj);
CREATE INDEX IF NOT EXISTS idx_fornecedores_ativo ON fornecedores(ativo);

-- Tabela de contas a pagar
CREATE TABLE IF NOT EXISTS contas_pagar (
    id SERIAL PRIMARY KEY,
    fornecedor_id INTEGER NOT NULL REFERENCES fornecedores(id) ON DELETE RESTRICT,
    numero_documento VARCHAR(100),
    descricao TEXT,
    valor_original DECIMAL(15,2) NOT NULL,
    valor_juros DECIMAL(15,2) DEFAULT 0.00,
    valor_desconto DECIMAL(15,2) DEFAULT 0.00,
    valor_final DECIMAL(15,2) NOT NULL,
    data_emissao DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE NULL,
    tipo_documento tipo_documento_enum DEFAULT 'OUTROS',
    categoria VARCHAR(100),
    status status_conta_enum DEFAULT 'PENDENTE',
    lote_pagamento_id INTEGER NULL,
    arquivo_anexo VARCHAR(500),
    observacoes TEXT,
    usuario_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_contas_fornecedor ON contas_pagar(fornecedor_id);
CREATE INDEX IF NOT EXISTS idx_contas_status ON contas_pagar(status);
CREATE INDEX IF NOT EXISTS idx_contas_vencimento ON contas_pagar(data_vencimento);
CREATE INDEX IF NOT EXISTS idx_contas_lote ON contas_pagar(lote_pagamento_id);

-- Tabela de lotes de pagamento
CREATE TABLE IF NOT EXISTS lotes_pagamento (
    id SERIAL PRIMARY KEY,
    fornecedor_id INTEGER NOT NULL REFERENCES fornecedores(id) ON DELETE RESTRICT,
    quantidade_contas INTEGER NOT NULL,
    valor_total DECIMAL(15,2) NOT NULL,
    data_agrupamento TIMESTAMP NOT NULL,
    data_pagamento_programada DATE NOT NULL,
    data_pagamento_realizada TIMESTAMP NULL,
    metodo_pagamento metodo_pagamento_enum DEFAULT 'PIX',
    status status_lote_enum DEFAULT 'AGUARDANDO',
    transacao_id VARCHAR(255),
    transacao_comprovante TEXT,
    transacao_erro TEXT,
    observacoes TEXT,
    usuario_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_lotes_fornecedor ON lotes_pagamento(fornecedor_id);
CREATE INDEX IF NOT EXISTS idx_lotes_status ON lotes_pagamento(status);
CREATE INDEX IF NOT EXISTS idx_lotes_data_programada ON lotes_pagamento(data_pagamento_programada);

-- Adicionar FK de lote_pagamento_id após criar lotes_pagamento
ALTER TABLE contas_pagar 
ADD CONSTRAINT fk_contas_lote 
FOREIGN KEY (lote_pagamento_id) 
REFERENCES lotes_pagamento(id) 
ON DELETE SET NULL;

-- Tabela de histórico de transações
CREATE TABLE IF NOT EXISTS transacoes_pagamento (
    id SERIAL PRIMARY KEY,
    lote_pagamento_id INTEGER NOT NULL REFERENCES lotes_pagamento(id) ON DELETE CASCADE,
    tipo_evento tipo_evento_enum NOT NULL,
    descricao TEXT,
    dados_json JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_transacoes_lote ON transacoes_pagamento(lote_pagamento_id);
CREATE INDEX IF NOT EXISTS idx_transacoes_tipo ON transacoes_pagamento(tipo_evento);

-- Tabela de configurações
CREATE TABLE IF NOT EXISTS config_pagamentos (
    id SERIAL PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descricao VARCHAR(500),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir configurações padrão
INSERT INTO config_pagamentos (chave, valor, descricao) VALUES
('api_provider', 'asaas', 'Provedor da API: asaas, banco_inter'),
('api_key', '', 'Token/Chave da API'),
('api_sandbox', '1', 'Usar ambiente de testes: 1=sim, 0=não'),
('agrupamento_ativo', '1', 'Ativar agrupamento: 1=sim, 0=não'),
('agrupamento_periodo', 'manual', 'Período: diario, semanal, mensal, manual'),
('limite_pix_gratuitos', '30', 'Limite de PIX gratuitos (Asaas=30, Inter=ilimitado)'),
('notificar_fornecedor', '1', 'Enviar email ao pagar: 1=sim, 0=não')
ON CONFLICT (chave) DO UPDATE SET valor = EXCLUDED.valor;

-- View para contas agrupadas
CREATE OR REPLACE VIEW view_contas_agrupadas AS
SELECT 
    lp.id as lote_id,
    lp.fornecedor_id,
    f.nome as fornecedor_nome,
    f.cpf_cnpj as fornecedor_documento,
    f.pix_chave,
    lp.quantidade_contas,
    lp.valor_total,
    lp.data_agrupamento,
    lp.data_pagamento_programada,
    lp.data_pagamento_realizada,
    lp.status as lote_status,
    lp.metodo_pagamento,
    STRING_AGG(
        CONCAT(cp.numero_documento, ' (R$ ', cp.valor_final, ')'),
        ', ' ORDER BY cp.data_vencimento
    ) as contas_detalhes
FROM lotes_pagamento lp
INNER JOIN fornecedores f ON lp.fornecedor_id = f.id
LEFT JOIN contas_pagar cp ON cp.lote_pagamento_id = lp.id
GROUP BY lp.id, f.nome, f.cpf_cnpj, f.pix_chave;

-- View para dashboard
CREATE OR REPLACE VIEW view_dashboard_pagamentos AS
SELECT 
    (SELECT COUNT(*) FROM contas_pagar WHERE status = 'PENDENTE') as contas_pendentes,
    (SELECT COALESCE(SUM(valor_final), 0) FROM contas_pagar WHERE status = 'PENDENTE') as valor_pendente,
    (SELECT COUNT(*) FROM contas_pagar WHERE status = 'ATRASADA') as contas_atrasadas,
    (SELECT COALESCE(SUM(valor_final), 0) FROM contas_pagar WHERE status = 'ATRASADA') as valor_atrasado,
    (SELECT COUNT(*) FROM lotes_pagamento WHERE status = 'AGUARDANDO') as lotes_aguardando,
    (SELECT COALESCE(SUM(valor_total), 0) FROM lotes_pagamento WHERE status = 'AGUARDANDO') as valor_lotes_aguardando,
    (SELECT COUNT(*) FROM contas_pagar WHERE data_pagamento >= CURRENT_DATE - INTERVAL '30 days') as contas_pagas_mes,
    (SELECT COALESCE(SUM(valor_final), 0) FROM contas_pagar WHERE data_pagamento >= CURRENT_DATE - INTERVAL '30 days') as valor_pago_mes;

-- Function para atualizar updated_at automaticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para updated_at
CREATE TRIGGER update_fornecedores_updated_at BEFORE UPDATE ON fornecedores
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_contas_updated_at BEFORE UPDATE ON contas_pagar
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_lotes_updated_at BEFORE UPDATE ON lotes_pagamento
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_config_updated_at BEFORE UPDATE ON config_pagamentos
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function para agrupar pagamentos
CREATE OR REPLACE FUNCTION sp_agrupar_pagamentos(p_data_programada DATE)
RETURNS TABLE(lotes_criados INTEGER) AS $$
DECLARE
    v_fornecedor_id INTEGER;
    v_total DECIMAL(15,2);
    v_quantidade INTEGER;
    v_lote_id INTEGER;
    v_count INTEGER := 0;
BEGIN
    FOR v_fornecedor_id, v_total, v_quantidade IN
        SELECT 
            fornecedor_id,
            SUM(valor_final) as total,
            COUNT(*) as quantidade
        FROM contas_pagar
        WHERE status = 'PENDENTE'
        AND data_vencimento <= p_data_programada
        GROUP BY fornecedor_id
        HAVING COUNT(*) > 0
    LOOP
        -- Criar lote de pagamento
        INSERT INTO lotes_pagamento (
            fornecedor_id,
            quantidade_contas,
            valor_total,
            data_agrupamento,
            data_pagamento_programada,
            status
        ) VALUES (
            v_fornecedor_id,
            v_quantidade,
            v_total,
            CURRENT_TIMESTAMP,
            p_data_programada,
            'AGUARDANDO'
        ) RETURNING id INTO v_lote_id;
        
        -- Vincular contas ao lote
        UPDATE contas_pagar
        SET 
            lote_pagamento_id = v_lote_id,
            status = 'AGRUPADA'
        WHERE fornecedor_id = v_fornecedor_id
        AND status = 'PENDENTE'
        AND data_vencimento <= p_data_programada;
        
        v_count := v_count + 1;
    END LOOP;
    
    RETURN QUERY SELECT v_count;
END;
$$ LANGUAGE plpgsql;
