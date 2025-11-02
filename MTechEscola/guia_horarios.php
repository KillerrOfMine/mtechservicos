<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Guia Rápido - Sistema de Horários</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; padding: 40px; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 900px; margin: 0 auto; }
        h1 { font-size: 2.5em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; }
        h2 { color: #00c3ff; font-size: 1.8em; margin-top: 32px; border-bottom: 2px solid #00c3ff; padding-bottom: 8px; }
        h3 { color: #ffff1c; font-size: 1.3em; margin-top: 24px; }
        .step { background: rgba(0, 195, 255, 0.1); border-left: 4px solid #00c3ff; padding: 16px; margin: 16px 0; border-radius: 8px; }
        .step-number { background: #00c3ff; color: #222; font-weight: 700; padding: 4px 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .link { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin: 8px 4px; box-shadow: 0 2px 8px #0005; }
        .link:hover { transform: scale(1.05); box-shadow: 0 4px 16px #00c3ff55; }
        .success { background: rgba(76, 175, 80, 0.2); border-left: 4px solid #4caf50; padding: 16px; margin: 16px 0; border-radius: 8px; }
        .info { background: rgba(255, 255, 28, 0.1); border-left: 4px solid #ffff1c; padding: 16px; margin: 16px 0; border-radius: 8px; }
        ul { line-height: 2; }
        code { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
<div class="container">
    <h1>🎓 Sistema de Horários MTech Escola</h1>
    
    <div class="success">
        <strong>✓ Sistema instalado e configurado com sucesso!</strong><br>
        Todas as tabelas foram criadas e estão prontas para uso.
    </div>
    
    <h2>📋 Funcionalidades Disponíveis</h2>
    
    <h3>1. Visualização de Horários</h3>
    <div class="step">
        <span class="step-number">🔍</span>
        <strong>Visualizar horários por turma ou professor</strong><br>
        Grade semanal completa (Segunda a Sexta) com disciplinas e professores<br>
        <a href="interface_horarios.php" class="link">Acessar Visualização</a>
    </div>
    
    <h3>2. Configurar Disponibilidade de Professores</h3>
    <div class="step">
        <span class="step-number">📅</span>
        <strong>Marcar horários disponíveis/ocupados</strong><br>
        Cada professor pode configurar seus horários livres e ocupados<br>
        <a href="disponibilidade_professor.php" class="link">Configurar Disponibilidade</a>
    </div>
    
    <h3>3. Editar Horários das Turmas</h3>
    <div class="step">
        <span class="step-number">✏️</span>
        <strong>Criar e editar grade de horários</strong><br>
        Selecione disciplinas e professores para cada horário<br>
        Sistema valida conflitos automaticamente<br>
        <a href="interface_horarios.php?view=turma" class="link">Gerenciar Horários</a>
    </div>
    
    <h2>🚀 Passo a Passo para Começar</h2>
    
    <div class="step">
        <span class="step-number">1</span>
        <strong>Configure os Intervalos de Horário</strong><br>
        Certifique-se de que a tabela <code>intervalos</code> tem os horários de aula cadastrados.<br>
        Exemplo: 07:00-07:50, 07:50-08:40, etc.
    </div>
    
    <div class="step">
        <span class="step-number">2</span>
        <strong>Configure Disponibilidade dos Professores</strong><br>
        Acesse <a href="disponibilidade_professor.php" class="link">Disponibilidade</a><br>
        Marque os horários como "Livre" (verde) ou "Ocupado" (vermelho)
    </div>
    
    <div class="step">
        <span class="step-number">3</span>
        <strong>Crie os Horários das Turmas</strong><br>
        Acesse <a href="interface_horarios.php" class="link">Horários</a><br>
        Selecione uma turma → Clique em "Editar Horário"<br>
        Configure disciplinas e professores para cada slot
    </div>
    
    <div class="step">
        <span class="step-number">4</span>
        <strong>Visualize e Imprima</strong><br>
        Use o botão "Imprimir/PDF" para gerar versão impressa<br>
        Alterne entre visualização por turma e por professor
    </div>
    
    <h2>💡 Recursos Especiais</h2>
    
    <div class="info">
        <strong>Horário Fixo:</strong> Marque turmas como "horário fixo" para que não sejam alteradas na geração automática futura.
    </div>
    
    <div class="info">
        <strong>Validação de Conflitos:</strong> O sistema detecta automaticamente quando um professor já está alocado em outra turma no mesmo horário.
    </div>
    
    <div class="info">
        <strong>Professores Disponíveis:</strong> Ao editar horários, o sistema mostra apenas professores disponíveis. Ocupados aparecem marcados em vermelho.
    </div>
    
    <h2>🔗 Links Rápidos</h2>
    
    <div style="text-align: center; margin-top: 32px;">
        <a href="interface_horarios.php" class="link">📊 Visualizar Horários</a>
        <a href="disponibilidade_professor.php" class="link">📅 Disponibilidade</a>
        <a href="home.php" class="link">🏠 Dashboard</a>
    </div>
    
    <h2>📚 Documentação</h2>
    
    <ul>
        <li><strong>Banco de Dados:</strong> Todas as tabelas foram configuradas automaticamente</li>
        <li><strong>Tabelas principais:</strong> <code>horarios_aulas</code>, <code>horarios_disponiveis_professor</code>, <code>intervalos</code></li>
        <li><strong>Campos especiais:</strong> <code>turmas.horario_fixo</code>, <code>horarios_aulas.ativo</code></li>
    </ul>
    
    <div style="text-align: center; margin-top: 48px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.2); color: #999;">
        <p>Sistema de Horários MTech Escola v1.0</p>
        <p>Desenvolvido em Novembro de 2025</p>
    </div>
</div>
</body>
</html>
