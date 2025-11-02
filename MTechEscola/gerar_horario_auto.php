<?php
session_start();
require_once 'db_connect_horarios.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$turma_id = $_GET['turma_id'] ?? null;

if (!$turma_id) {
    die("Turma não especificada.");
}

// Verifica se a turma tem horário fixo
$stmt = $conn->prepare("SELECT nome, horario_fixo FROM turmas WHERE id = ?");
$stmt->execute([$turma_id]);
$turma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    die("Turma não encontrada.");
}

if ($turma['horario_fixo']) {
    die("Esta turma tem horário fixo e não pode ser alterada automaticamente.");
}

$msg = '';
$erro = '';

try {
    // Busca disciplinas e professores vinculados à turma com carga horária
    $stmt = $conn->prepare("
        SELECT d.id AS disciplina_id, d.nome AS disciplina, 
               p.id AS professor_id, p.nome AS professor,
               tdp.aulas_semana
        FROM turma_disciplina_professor tdp
        JOIN disciplinas d ON tdp.disciplina_id = d.id
        JOIN professores p ON tdp.professor_id = p.id
        WHERE tdp.turma_id = ? AND tdp.aulas_semana > 0
    ");
    $stmt->execute([$turma_id]);
    $disciplinas_turma = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($disciplinas_turma)) {
        throw new Exception("Nenhuma disciplina/professor vinculado à turma com carga horária definida.");
    }
    
    // Busca intervalos disponíveis
    $intervalos = $conn->query("SELECT DISTINCT hora_inicio, hora_fim FROM intervalos ORDER BY hora_inicio")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($intervalos)) {
        throw new Exception("Nenhum intervalo de horário cadastrado no sistema.");
    }
    
    // Dias da semana (segunda a sexta)
    $dias_semana = [1, 2, 3, 4, 5];
    
    // Remove horários existentes da turma (exceto se fixo)
    $conn->prepare("DELETE FROM horarios_aulas WHERE turma_id = ?")->execute([$turma_id]);
    
    // Cria lista de slots disponíveis
    $slots_disponiveis = [];
    foreach ($dias_semana as $dia) {
        foreach ($intervalos as $intervalo) {
            $slots_disponiveis[] = [
                'dia' => $dia,
                'hora_inicio' => $intervalo['hora_inicio'],
                'hora_fim' => $intervalo['hora_fim']
            ];
        }
    }
    
    // Embaralha slots para distribuição aleatória
    shuffle($slots_disponiveis);
    
    $aulas_inseridas = 0;
    $conflitos = [];
    
    // Para cada disciplina, tenta alocar a quantidade de aulas necessárias
    foreach ($disciplinas_turma as $disc) {
        $aulas_alocadas = 0;
        $aulas_necessarias = (int)$disc['aulas_semana'];
        
        foreach ($slots_disponiveis as $key => $slot) {
            if ($aulas_alocadas >= $aulas_necessarias) {
                break;
            }
            
            // Verifica se o professor está disponível neste horário
            $stmt = $conn->prepare("
                SELECT disponivel 
                FROM horarios_disponiveis_professor 
                WHERE professor_id = ? AND dia_semana = ? AND hora_inicio = ?
            ");
            $stmt->execute([$disc['professor_id'], $slot['dia'], $slot['hora_inicio']]);
            $disponibilidade = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não há registro, considera disponível
            $professor_disponivel = !$disponibilidade || $disponibilidade['disponivel'];
            
            if (!$professor_disponivel) {
                continue; // Professor não disponível neste horário
            }
            
            // Verifica se o professor já tem aula neste horário em outra turma
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM horarios_aulas 
                WHERE professor_id = ? 
                AND dia_semana = ? 
                AND hora_inicio = ? 
                AND turma_id != ?
                AND COALESCE(ativo, TRUE) = TRUE
            ");
            $stmt->execute([$disc['professor_id'], $slot['dia'], $slot['hora_inicio'], $turma_id]);
            $tem_conflito = $stmt->fetchColumn() > 0;
            
            if ($tem_conflito) {
                $conflitos[] = "Prof. {$disc['professor']} - Dia {$slot['dia']} às {$slot['hora_inicio']}";
                continue; // Professor ocupado neste horário
            }
            
            // Aloca a aula neste slot
            $stmt = $conn->prepare("
                INSERT INTO horarios_aulas (turma_id, disciplina_id, professor_id, dia_semana, hora_inicio, hora_fim, ativo)
                VALUES (?, ?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([
                $turma_id,
                $disc['disciplina_id'],
                $disc['professor_id'],
                $slot['dia'],
                $slot['hora_inicio'],
                $slot['hora_fim']
            ]);
            
            $aulas_alocadas++;
            $aulas_inseridas++;
            
            // Remove este slot da lista de disponíveis
            unset($slots_disponiveis[$key]);
        }
        
        if ($aulas_alocadas < $aulas_necessarias) {
            $faltaram = $aulas_necessarias - $aulas_alocadas;
            $msg .= "⚠️ {$disc['disciplina']}: Alocadas {$aulas_alocadas} de {$aulas_necessarias} aulas (faltaram {$faltaram})<br>";
        }
    }
    
    $msg = "✅ Horário gerado com sucesso!<br>";
    $msg .= "📊 Total de aulas inseridas: {$aulas_inseridas}<br>";
    
    if (!empty($conflitos)) {
        $msg .= "<br>⚠️ Conflitos detectados (professores ocupados):<br>";
        $msg .= implode("<br>", array_unique($conflitos));
    }
    
} catch (Exception $e) {
    $erro = "❌ Erro ao gerar horário: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerar Horário Automático - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; padding: 20px; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 800px; margin: 0 auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; }
        .msg { background: #00c3ff; color: #222; padding: 16px; border-radius: 12px; margin: 24px 0; font-weight: 600; line-height: 1.6; }
        .erro { background: #ff3366; color: #fff; padding: 16px; border-radius: 12px; margin: 24px 0; font-weight: 700; text-align: center; }
        .btn { 
            background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); 
            color: #222; 
            font-weight: 700; 
            border: none; 
            border-radius: 12px; 
            padding: 12px 32px; 
            font-size: 1em; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            margin: 8px;
        }
        .btn:hover { transform: scale(1.05); box-shadow: 0 4px 16px #00c3ff77; }
        .btn-secondary {
            background: linear-gradient(90deg, #6c757d 40%, #5a6268 100%);
            color: #fff;
        }
        .info-turma {
            background: rgba(0, 195, 255, 0.1);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
        }
        .info-turma h2 {
            margin: 0;
            color: #ffff1c;
            font-size: 1.5em;
        }
        .acoes {
            text-align: center;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Geração Automática de Horário</h1>
        
        <div class="info-turma">
            <h2><?= htmlspecialchars($turma['nome']) ?></h2>
        </div>
        
        <?php if ($msg): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
        
        <div class="acoes">
            <a href="interface_horarios.php?turma_id=<?= $turma_id ?>" class="btn">📋 Ver Horário Gerado</a>
            <a href="editar_horario_turma.php?turma_id=<?= $turma_id ?>" class="btn btn-secondary">✏️ Editar Horário</a>
            <a href="interface_horarios.php" class="btn btn-secondary">← Voltar</a>
        </div>
    </div>
</body>
</html>
