-- Criação da tabela de permissões de usuários
CREATE TABLE IF NOT EXISTS permissoes (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    pagina VARCHAR(100) NOT NULL
);

-- Exemplo de inserção de permissões iniciais (opcional)
-- INSERT INTO permissoes (usuario_id, pagina) VALUES (1, 'dashboard.php');
