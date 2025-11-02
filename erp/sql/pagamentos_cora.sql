-- Schema de Pagamentos com Cora para o ERP
-- Executar no banco de dados do ERP

-- Tabela de configuração de API Cora
CREATE TABLE IF NOT EXISTS cora_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(255) NOT NULL,
    client_secret TEXT NOT NULL,
    access_token TEXT,
    refresh_token TEXT,
    token_expira_em DATETIME,
    ambiente ENUM('sandbox', 'producao') DEFAULT 'sandbox',
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de fornecedores (se não existir)
CREATE TABLE IF NOT EXISTS fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf_cnpj VARCHAR(18),
    email VARCHAR(255),
    telefone VARCHAR(20),
    chave_pix VARCHAR(255),
    tipo_chave_pix ENUM('cpf', 'cnpj', 'email', 'telefone', 'aleatoria'),
    banco VARCHAR(100),
    agencia VARCHAR(10),
    conta VARCHAR(20),
    tipo_conta ENUM('corrente', 'poupanca'),
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de contas a pagar
CREATE TABLE IF NOT EXISTS contas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fornecedor_id INT,
    tipo_pagamento ENUM('pix', 'boleto', 'ted', 'dinheiro', 'cartao') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('pendente', 'agendado', 'pago', 'cancelado', 'vencido') DEFAULT 'pendente',
    codigo_barras VARCHAR(255),
    chave_pix VARCHAR(255),
    comprovante TEXT,
    observacoes TEXT,
    categoria VARCHAR(100),
    centro_custo VARCHAR(100),
    criado_por INT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id)
);

-- Tabela de transações Cora (log de pagamentos)
CREATE TABLE IF NOT EXISTS cora_transacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conta_pagar_id INT,
    tipo ENUM('pix', 'boleto', 'ted') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    destinatario VARCHAR(255),
    chave_pix VARCHAR(255),
    codigo_barras VARCHAR(255),
    transaction_id VARCHAR(255),
    status ENUM('processando', 'aprovado', 'rejeitado', 'cancelado') DEFAULT 'processando',
    mensagem_retorno TEXT,
    comprovante_url TEXT,
    request_data TEXT,
    response_data TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conta_pagar_id) REFERENCES contas_pagar(id)
);

-- Tabela de saldo e extratos
CREATE TABLE IF NOT EXISTS cora_extratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('entrada', 'saida') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    descricao VARCHAR(255),
    saldo_anterior DECIMAL(10,2),
    saldo_atual DECIMAL(10,2),
    data_transacao DATETIME NOT NULL,
    sincronizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para melhor performance
CREATE INDEX idx_contas_pagar_status ON contas_pagar(status);
CREATE INDEX idx_contas_pagar_vencimento ON contas_pagar(data_vencimento);
CREATE INDEX idx_cora_transacoes_status ON cora_transacoes(status);
CREATE INDEX idx_fornecedores_ativo ON fornecedores(ativo);
