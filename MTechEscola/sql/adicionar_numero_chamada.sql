-- Adicionar coluna numero_chamada na tabela alunos
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS numero_chamada INTEGER;

-- Comentário da coluna
COMMENT ON COLUMN alunos.numero_chamada IS 'Número da chamada do aluno na turma';

-- Atualizar números da chamada - 6º ANO MATUTINO
UPDATE alunos SET numero_chamada = 1 WHERE nome ILIKE '%Alice Vilela Cruvinel%';
UPDATE alunos SET numero_chamada = 2 WHERE nome ILIKE '%Ana Laura Daniel Carneiro%';
UPDATE alunos SET numero_chamada = 3 WHERE nome ILIKE '%Anna Julia Mendonça de Almeida%' OR nome ILIKE '%Anna Julia Mendonca de Almeida%';
UPDATE alunos SET numero_chamada = 4 WHERE nome ILIKE '%Brayann Raphael Souza Morais%';
UPDATE alunos SET numero_chamada = 5 WHERE nome ILIKE '%Elisa Machado Assunção%' OR nome ILIKE '%Elisa Machado Assuncao%';
UPDATE alunos SET numero_chamada = 6 WHERE nome ILIKE '%Estevão Carvalho Lopes%' OR nome ILIKE '%Estevao Carvalho Lopes%';
UPDATE alunos SET numero_chamada = 7 WHERE nome ILIKE '%Gabriela Mendonça Tomaz%' OR nome ILIKE '%Gabriela Mendonca Tomaz%';
UPDATE alunos SET numero_chamada = 8 WHERE nome ILIKE '%Guilherme Gomes de Morais%';
UPDATE alunos SET numero_chamada = 9 WHERE nome ILIKE '%Luca William Ford Oliveira%';
UPDATE alunos SET numero_chamada = 10 WHERE nome ILIKE '%Manuella Inácio%' OR nome ILIKE '%Manuella Inacio%';
UPDATE alunos SET numero_chamada = 11 WHERE nome ILIKE '%Maria Alice Araujo Palla%';
UPDATE alunos SET numero_chamada = 12 WHERE nome ILIKE '%Maria Eduarda Santos Ferreira%';
UPDATE alunos SET numero_chamada = 13 WHERE nome ILIKE '%Maria Valentina Souza Inácio%' OR nome ILIKE '%Maria Valentina Souza Inacio%';
UPDATE alunos SET numero_chamada = 14 WHERE nome ILIKE '%Marianny Kasbaum do Amaral Silva%';
UPDATE alunos SET numero_chamada = 15 WHERE nome ILIKE '%Matheus Mattos Clementino%';
UPDATE alunos SET numero_chamada = 16 WHERE nome ILIKE '%Miguel Vitor Peres e Silva%';
UPDATE alunos SET numero_chamada = 17 WHERE nome ILIKE '%Nicolle Desiree Pereira Lopes%';
UPDATE alunos SET numero_chamada = 18 WHERE nome ILIKE '%Sarah Rodrigues Garcia%';
UPDATE alunos SET numero_chamada = 19 WHERE nome ILIKE '%Sophia Riccelli Felippe Ferreira%';
UPDATE alunos SET numero_chamada = 20 WHERE nome ILIKE '%Valentina Pimenta Textor%';
UPDATE alunos SET numero_chamada = 21 WHERE nome ILIKE '%Katheriny Bernardes Barros%';
UPDATE alunos SET numero_chamada = 22 WHERE nome ILIKE '%Sofia Fernandes Fonseca%';

-- Verificar resultados
SELECT nome, numero_chamada, turma_id 
FROM alunos 
WHERE numero_chamada IS NOT NULL 
ORDER BY numero_chamada;
