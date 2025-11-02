-- Unificar disciplinas Arte/Artes para apenas "Arte"

BEGIN;

-- 1. Identificar os IDs das disciplinas
SELECT id, nome FROM disciplinas WHERE UPPER(TRIM(nome)) IN ('ARTE', 'ARTES') ORDER BY nome;

-- 2. Escolher o ID que será mantido (menor ID) e o que será removido
DO $$
DECLARE
    id_manter INTEGER;
    id_remover INTEGER;
BEGIN
    -- Pegar o menor ID (será mantido)
    SELECT MIN(id) INTO id_manter FROM disciplinas WHERE UPPER(TRIM(nome)) IN ('ARTE', 'ARTES');
    
    -- Pegar o maior ID (será removido)
    SELECT MAX(id) INTO id_remover FROM disciplinas WHERE UPPER(TRIM(nome)) IN ('ARTE', 'ARTES') AND id != id_manter;
    
    -- Se encontrou duplicata
    IF id_remover IS NOT NULL THEN
        RAISE NOTICE 'Mantendo disciplina ID % e removendo ID %', id_manter, id_remover;
        
        -- 3. Atualizar todas as referências para o ID que será mantido
        
        -- Atualizar horarios_aulas
        UPDATE horarios_aulas 
        SET disciplina_id = id_manter 
        WHERE disciplina_id = id_remover;
        RAISE NOTICE '  - horarios_aulas: % registros atualizados', FOUND;
        
        -- Atualizar turma_disciplina_professor
        UPDATE turma_disciplina_professor 
        SET disciplina_id = id_manter 
        WHERE disciplina_id = id_remover;
        RAISE NOTICE '  - turma_disciplina_professor: % registros atualizados', FOUND;
        
        -- Atualizar atividades
        UPDATE atividades 
        SET disciplina_id = id_manter 
        WHERE disciplina_id = id_remover;
        RAISE NOTICE '  - atividades: % registros atualizados', FOUND;
        
        -- Nota: tabela 'notas' não tem disciplina_id, relaciona-se via atividades.atividade_id
        -- Nota: tabela 'presencas' não tem disciplina_id, relaciona-se via atividades.atividade_id
        
        -- 4. Deletar a disciplina duplicada
        DELETE FROM disciplinas WHERE id = id_remover;
        RAISE NOTICE '  - Disciplina ID % deletada', id_remover;
        
        -- 5. Garantir que o nome está padronizado como "Arte"
        UPDATE disciplinas 
        SET nome = 'Arte' 
        WHERE id = id_manter;
        RAISE NOTICE '  - Nome padronizado para "Arte"';
        
    ELSE
        RAISE NOTICE 'Não há duplicatas para unificar';
    END IF;
END $$;

-- 6. Verificar resultado final
SELECT id, nome FROM disciplinas WHERE UPPER(TRIM(nome)) = 'ARTE' ORDER BY nome;

-- 7. Verificar quantas aulas existem com Arte
SELECT COUNT(*) as total_aulas FROM horarios_aulas ha
JOIN disciplinas d ON ha.disciplina_id = d.id
WHERE UPPER(TRIM(d.nome)) = 'ARTE';

COMMIT;

-- Mensagem de sucesso
DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '✓ Unificação concluída com sucesso!';
    RAISE NOTICE '  - Disciplina padronizada: Arte';
    RAISE NOTICE '  - Todas as referências atualizadas';
END $$;
