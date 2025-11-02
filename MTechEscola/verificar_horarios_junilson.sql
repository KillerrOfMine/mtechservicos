-- Verificar horários do professor Junilson (ID 44)
SELECT 
    h.id,
    h.dia_semana,
    h.hora_inicio,
    h.hora_fim,
    t.nome as turma_nome,
    d.nome as disciplina_nome,
    h.sala,
    h.professor_id
FROM horarios_aulas h
JOIN turmas t ON h.turma_id = t.id
JOIN disciplinas d ON h.disciplina_id = d.id
WHERE h.professor_id = 44
ORDER BY 
    CASE h.dia_semana
        WHEN 'Segunda' THEN 1
        WHEN 'Terça' THEN 2
        WHEN 'Quarta' THEN 3
        WHEN 'Quinta' THEN 4
        WHEN 'Sexta' THEN 5
        WHEN 'Sábado' THEN 6
    END,
    h.hora_inicio;
