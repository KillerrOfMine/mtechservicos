-- Configuração da API Asaas (PostgreSQL)
INSERT INTO config_pagamentos (chave, valor, descricao) VALUES
('api_key', '$aact_prod_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjM3OGM1MTUxLWIwOTItNDgzZC05NDYwLTNhZmExM2YyNzJjNDo6JGFhY2hfNTIyNmU2ZmEtYmJiZC00OTA2LWExZDgtNzg1NzFhNmM3OGJj', 'Chave de API do Asaas'),
('api_sandbox', '0', 'Ambiente: 0=Produção, 1=Sandbox'),
('api_provider', 'asaas', 'Provedor de pagamentos'),
('agrupamento_ativo', '1', 'Ativar agrupamento de pagamentos'),
('agrupamento_periodo', 'manual', 'Período de agrupamento'),
('limite_pix_gratuitos', '30', 'Limite de PIX gratuitos (Asaas=30)')
ON CONFLICT (chave) DO UPDATE SET valor = EXCLUDED.valor;
