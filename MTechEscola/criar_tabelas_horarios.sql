-- Adiciona campo senha à tabela professores
ALTER TABLE professores ADD COLUMN senha character varying(64);

-- Atualiza usuário administrador com senha criptografada
UPDATE professores SET senha = MD5('@123456') WHERE nome = 'Administrador' AND cpf = 'admin';

-- Usuário administrador padrão
INSERT INTO professores (nome, cpf, telefone, ativo)
VALUES ('Administrador', 'admin', 'admin@mtech.com', true);
-- Arquivo movido do projeto de horários