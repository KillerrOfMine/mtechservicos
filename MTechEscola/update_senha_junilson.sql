UPDATE professores 
SET senha = '$2y$10$ba3rVy5Kk4huGdPuBrpTsequoSRdTw0pZPxGIC2oCPw5xrQy3rcpW', 
    alterar_senha_proximo_login = false 
WHERE login = 'junilson.augusto';

SELECT id, nome, login, 'Senha atualizada para @Mar1401a' as status
FROM professores 
WHERE login = 'junilson.augusto';
