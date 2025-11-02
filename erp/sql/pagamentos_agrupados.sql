-- ============================================
-- SISTEMA DE PAGAMENTOS COM AGRUPAMENTO
-- PostgreSQL Version
-- Suporta: Asaas ou Banco Inter
-- ============================================

-- Tipos ENUM
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
    
    -- Dados bancários para PIX
    pix_tipo pix_tipo_enum DEFAULT 'CPF',
    pix_chave VARCHAR(255),
    
    -- Dados bancários para TED (backup)
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

-- Tabela de contas a pagar (individual)
CREATE TABLE IF NOT EXISTS contas_pagar (
    id SERIAL PRIMARY KEY,
    fornecedor_id BIGINT UNSIGNED NOT NULL,
    
    numero_documento VARCHAR(100),
    descricao TEXT,
    valor_original DECIMAL(15,2) NOT NULL,
    valor_juros DECIMAL(15,2) DEFAULT 0.00,
    valor_desconto DECIMAL(15,2) DEFAULT 0.00,
    valor_final DECIMAL(15,2) NOT NULL, -- valor_original + juros - desconto
    
    data_emissao DATE NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE NULL,
    
    tipo_documento ENUM('BOLETO', 'NOTA_FISCAL', 'RECIBO', 'FATURA', 'OUTROS') DEFAULT 'OUTROS',
    categoria VARCHAR(100), -- Matéria prima, serviço, aluguel, etc
    
    status ENUM('PENDENTE', 'AGRUPADA', 'PAGA', 'CANCELADA', 'ATRASADA') DEFAULT 'PENDENTE',
    
    -- Referência ao lote de pagamento (quando agrupada)
    lote_pagamento_id BIGINT UNSIGNED NULL,
    
    -- Anexos (boleto, nota fiscal)
    arquivo_anexo VARCHAR(500),
    
    observacoes TEXT,
    usuario_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE RESTRICT,
    FOREIGN KEY (lote_pagamento_id) REFERENCES lotes_pagamento(id) ON DELETE SET NULL,
    
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento),
    INDEX idx_lote (lote_pagamento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de lotes de pagamento (agrupamento)
CREATE TABLE IF NOT EXISTS lotes_pagamento (
    id SERIAL PRIMARY KEY,
    fornecedor_id BIGINT UNSIGNED NOT NULL,
    
    quantidade_contas INT NOT NULL, -- Quantas contas foram agrupadas
    valor_total DECIMAL(15,2) NOT NULL, -- Soma de todas as contas
    
    data_agrupamento DATETIME NOT NULL,
    data_pagamento_programada DATE NOT NULL,
    data_pagamento_realizada DATETIME NULL,
    
    metodo_pagamento ENUM('PIX', 'TED', 'BOLETO') DEFAULT 'PIX',
    
    status ENUM('AGUARDANDO', 'PROCESSANDO', 'PAGO', 'ERRO', 'CANCELADO') DEFAULT 'AGUARDANDO',
    
    -- Informações da transação
    transacao_id VARCHAR(255), -- ID retornado pela API (Asaas/Inter)
    transacao_comprovante TEXT, -- JSON com dados do comprovante
    transacao_erro TEXT, -- Mensagem de erro se houver
    
    observacoes TEXT,
    usuario_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE RESTRICT,
    
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_status (status),
    INDEX idx_data_programada (data_pagamento_programada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de histórico de transações
CREATE TABLE IF NOT EXISTS transacoes_pagamento (
    id SERIAL PRIMARY KEY,
    lote_pagamento_id BIGINT UNSIGNED NOT NULL,
    
    tipo_evento ENUM('CRIADO', 'ENVIADO', 'CONFIRMADO', 'ERRO', 'CANCELADO') NOT NULL,
    descricao TEXT,
    dados_json JSON, -- Dados completos da resposta da API
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (lote_pagamento_id) REFERENCES lotes_pagamento(id) ON DELETE CASCADE,
    
    INDEX idx_lote (lote_pagamento_id),
    INDEX idx_tipo (tipo_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do sistema de pagamento
CREATE TABLE IF NOT EXISTS config_pagamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descricao VARCHAR(500),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT INTO config_pagamentos (chave, valor, descricao) VALUES
('api_provider', 'asaas', 'Provedor da API: asaas, banco_inter, cora'),
('api_key', '', 'Token/Chave da API'),
('api_sandbox', '1', 'Usar ambiente de testes: 1=sim, 0=não'),
('agrupamento_ativo', '1', 'Ativar agrupamento de pagamentos: 1=sim, 0=não'),
('agrupamento_periodo', 'diario', 'Período de agrupamento: diario, semanal, mensal, manual'),
('agrupamento_dia_semana', '5', 'Dia da semana para agrupar (1=segunda, 5=sexta)'),
('agrupamento_dia_mes', '10', 'Dia do mês para agrupar (1-31)'),
('agrupamento_valor_minimo', '10.00', 'Valor mínimo para agrupar (abaixo disso, não agrupa)'),
('limite_pix_gratuitos', '30', 'Quantidade de PIX gratuitos no mês (Asaas=30, Inter=ilimitado)'),
('notificar_fornecedor', '1', 'Enviar email para fornecedor quando pagar: 1=sim, 0=não')
ON DUPLICATE KEY UPDATE valor=VALUES(valor);

-- View para relatórios de contas agrupadas
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
    GROUP_CONCAT(
        CONCAT(cp.numero_documento, ' (R$ ', cp.valor_final, ')')
        ORDER BY cp.data_vencimento
        SEPARATOR ', '
    ) as contas_detalhes
FROM lotes_pagamento lp
INNER JOIN fornecedores f ON lp.fornecedor_id = f.id
LEFT JOIN contas_pagar cp ON cp.lote_pagamento_id = lp.id
GROUP BY lp.id;

-- View para dashboard de pagamentos
CREATE OR REPLACE VIEW view_dashboard_pagamentos AS
SELECT 
    (SELECT COUNT(*) FROM contas_pagar WHERE status = 'PENDENTE') as contas_pendentes,
    (SELECT SUM(valor_final) FROM contas_pagar WHERE status = 'PENDENTE') as valor_pendente,
    (SELECT COUNT(*) FROM contas_pagar WHERE status = 'ATRASADA') as contas_atrasadas,
    (SELECT SUM(valor_final) FROM contas_pagar WHERE status = 'ATRASADA') as valor_atrasado,
    (SELECT COUNT(*) FROM lotes_pagamento WHERE status = 'AGUARDANDO') as lotes_aguardando,
    (SELECT SUM(valor_total) FROM lotes_pagamento WHERE status = 'AGUARDANDO') as valor_lotes_aguardando,
    (SELECT COUNT(*) FROM contas_pagar WHERE data_pagamento >= CURDATE() - INTERVAL 30 DAY) as contas_pagas_mes,
    (SELECT SUM(valor_final) FROM contas_pagar WHERE data_pagamento >= CURDATE() - INTERVAL 30 DAY) as valor_pago_mes;

-- Procedure para agrupar pagamentos automaticamente
DELIMITER //

CREATE PROCEDURE sp_agrupar_pagamentos(
    IN p_data_programada DATE
)
BEGIN
    DECLARE v_fornecedor_id BIGINT;
    DECLARE v_total DECIMAL(15,2);
    DECLARE v_quantidade INT;
    DECLARE done INT DEFAULT FALSE;
    
    -- Cursor para iterar pelos fornecedores com contas pendentes
    DECLARE cur_fornecedores CURSOR FOR
        SELECT 
            fornecedor_id,
            SUM(valor_final) as total,
            COUNT(*) as quantidade
        FROM contas_pagar
        WHERE status = 'PENDENTE'
        AND data_vencimento <= p_data_programada
        GROUP BY fornecedor_id
        HAVING COUNT(*) > 0; -- Pode configurar para agrupar só se tiver 2+ contas
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur_fornecedores;
    
    read_loop: LOOP
        FETCH cur_fornecedores INTO v_fornecedor_id, v_total, v_quantidade;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
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
            NOW(),
            p_data_programada,
            'AGUARDANDO'
        );
        
        SET @lote_id = LAST_INSERT_ID();
        
        -- Vincular contas ao lote e atualizar status
        UPDATE contas_pagar
        SET 
            lote_pagamento_id = @lote_id,
            status = 'AGRUPADA'
        WHERE fornecedor_id = v_fornecedor_id
        AND status = 'PENDENTE'
        AND data_vencimento <= p_data_programada;
        
    END LOOP;
    
    CLOSE cur_fornecedores;
    
    -- Retornar quantidade de lotes criados
    SELECT COUNT(*) as lotes_criados FROM lotes_pagamento WHERE data_agrupamento >= NOW() - INTERVAL 1 MINUTE;
END //

DELIMITER ;

-- Trigger para atualizar status de conta atrasada
DELIMITER //

CREATE TRIGGER trg_atualizar_status_atrasada
BEFORE UPDATE ON contas_pagar
FOR EACH ROW
BEGIN
    IF NEW.status = 'PENDENTE' AND NEW.data_vencimento < CURDATE() THEN
        SET NEW.status = 'ATRASADA';
    END IF;
END //

DELIMITER ;

-- Índices para performance
CREATE INDEX idx_contas_pendentes ON contas_pagar(status, data_vencimento, fornecedor_id);
CREATE INDEX idx_lotes_status ON lotes_pagamento(status, data_pagamento_programada);
