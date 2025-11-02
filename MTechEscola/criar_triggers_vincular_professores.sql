-- Trigger para vincular automaticamente professores aos horários
-- Quando um professor é vinculado a uma turma+disciplina, 
-- automaticamente atualiza todos os horários correspondentes

-- Função que será executada pelo trigger
CREATE OR REPLACE FUNCTION vincular_professor_horarios()
RETURNS TRIGGER AS $$
BEGIN
    -- Atualiza horários que não têm professor atribuído
    UPDATE horarios_aulas 
    SET professor_id = NEW.professor_id 
    WHERE turma_id = NEW.turma_id 
    AND disciplina_id = NEW.disciplina_id 
    AND professor_id IS NULL;
    
    -- Log para debug (opcional)
    RAISE NOTICE 'Professor % vinculado automaticamente aos horários da turma % disciplina %', 
        NEW.professor_id, NEW.turma_id, NEW.disciplina_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Remove trigger se já existir
DROP TRIGGER IF EXISTS trigger_vincular_professor ON turma_disciplina_professor;

-- Cria trigger que executa APÓS inserir um novo vínculo
CREATE TRIGGER trigger_vincular_professor
AFTER INSERT ON turma_disciplina_professor
FOR EACH ROW 
EXECUTE FUNCTION vincular_professor_horarios();

-- Também atualiza quando o professor é alterado
CREATE OR REPLACE FUNCTION atualizar_professor_horarios()
RETURNS TRIGGER AS $$
BEGIN
    -- Se o professor mudou, atualiza os horários
    IF OLD.professor_id != NEW.professor_id THEN
        UPDATE horarios_aulas 
        SET professor_id = NEW.professor_id 
        WHERE turma_id = NEW.turma_id 
        AND disciplina_id = NEW.disciplina_id;
        
        RAISE NOTICE 'Professor alterado de % para % na turma % disciplina %', 
            OLD.professor_id, NEW.professor_id, NEW.turma_id, NEW.disciplina_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Remove trigger se já existir
DROP TRIGGER IF EXISTS trigger_atualizar_professor ON turma_disciplina_professor;

-- Cria trigger para UPDATE
CREATE TRIGGER trigger_atualizar_professor
AFTER UPDATE ON turma_disciplina_professor
FOR EACH ROW 
EXECUTE FUNCTION atualizar_professor_horarios();

-- Trigger para remover professor dos horários quando vínculo é deletado
CREATE OR REPLACE FUNCTION desvincular_professor_horarios()
RETURNS TRIGGER AS $$
BEGIN
    -- Remove professor dos horários quando o vínculo é deletado
    UPDATE horarios_aulas 
    SET professor_id = NULL 
    WHERE turma_id = OLD.turma_id 
    AND disciplina_id = OLD.disciplina_id 
    AND professor_id = OLD.professor_id;
    
    RAISE NOTICE 'Professor % desvinculado dos horários da turma % disciplina %', 
        OLD.professor_id, OLD.turma_id, OLD.disciplina_id;
    
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- Remove trigger se já existir
DROP TRIGGER IF EXISTS trigger_desvincular_professor ON turma_disciplina_professor;

-- Cria trigger para DELETE
CREATE TRIGGER trigger_desvincular_professor
AFTER DELETE ON turma_disciplina_professor
FOR EACH ROW 
EXECUTE FUNCTION desvincular_professor_horarios();

-- Verificar se os triggers foram criados
SELECT 
    trigger_name,
    event_manipulation,
    event_object_table,
    action_statement
FROM information_schema.triggers
WHERE trigger_name LIKE '%professor%'
ORDER BY trigger_name;

-- Mensagem de sucesso
DO $$
BEGIN
    RAISE NOTICE '✓ Triggers criados com sucesso!';
    RAISE NOTICE '  - INSERT: vincular_professor_horarios()';
    RAISE NOTICE '  - UPDATE: atualizar_professor_horarios()';
    RAISE NOTICE '  - DELETE: desvincular_professor_horarios()';
    RAISE NOTICE '';
    RAISE NOTICE 'Agora os professores serão vinculados automaticamente aos horários!';
END $$;
