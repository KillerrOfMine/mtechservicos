-- Resetar senha do professor Junilson para @Mar1401a
UPDATE professores 
SET senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    alterar_senha_proximo_login = false
WHERE login = 'junilson.augusto';

-- Verificar atualização
SELECT id, nome, login, 
       CASE WHEN senha IS NOT NULL THEN 'Senha definida' ELSE 'Sem senha' END as status
FROM professores 
WHERE login = 'junilson.augusto';
