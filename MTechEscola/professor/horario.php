<?php
session_start();
require_once '../db_connect_horarios.php';

if (!isset($_SESSION['professor_id'])) {
    header('Location: login.php');
    exit;
}

$professor_id = $_SESSION['professor_id'];
$professor_nome = $_SESSION['professor_nome'];

// Buscar horários do professor
$sql = "SELECT 
    h.dia_semana,
    h.hora_inicio,
    h.hora_fim,
    t.nome as turma_nome,
    d.nome as disciplina_nome
FROM horarios_aulas h
JOIN turmas t ON h.turma_id = t.id
JOIN disciplinas d ON h.disciplina_id = d.id
WHERE h.professor_id = ?
ORDER BY h.hora_inicio";

$stmt = $conn->prepare($sql);
$stmt->execute([$professor_id]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapear números para nomes dos dias
$mapa_dias = [
    1 => 'Segunda',
    2 => 'Terça',
    3 => 'Quarta',
    4 => 'Quinta',
    5 => 'Sexta',
    6 => 'Sábado',
    7 => 'Domingo'
];

// Converter dia_semana de número para texto
foreach ($horarios as &$horario) {
    if (is_numeric($horario['dia_semana'])) {
        $horario['dia_semana'] = $mapa_dias[$horario['dia_semana']] ?? 'Segunda';
    }
}
unset($horario);

// Definir ordem dos dias
$ordem_dias = [
    'Segunda' => 1,
    'Terça' => 2,
    'Quarta' => 3,
    'Quinta' => 4,
    'Sexta' => 5,
    'Sábado' => 6,
    'Domingo' => 7
];

// Ordenar horários por dia da semana usando PHP
usort($horarios, function($a, $b) use ($ordem_dias) {
    $ordem_a = $ordem_dias[$a['dia_semana']] ?? 7;
    $ordem_b = $ordem_dias[$b['dia_semana']] ?? 7;
    
    if ($ordem_a == $ordem_b) {
        return strcmp($a['hora_inicio'], $b['hora_inicio']);
    }
    return $ordem_a - $ordem_b;
});

// Organizar por dia da semana
$dias_semana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
$horarios_por_dia = [];
foreach ($dias_semana as $dia) {
    $horarios_por_dia[$dia] = array_filter($horarios, function($h) use ($dia) {
        return $h['dia_semana'] === $dia;
    });
}

// Dia da semana atual
$dia_atual = date('N'); // 1=Segunda, 5=Sexta
$dias_map = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
$dia_hoje = $dias_map[$dia_atual] ?? 'Segunda';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Horário - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #0f2027, #2c5364); 
            min-height: 100vh; 
            font-family: 'Orbitron', Arial, sans-serif; 
            color: #fff;
            padding-top: 70px;
            padding-bottom: 20px;
        }
        
        <?php include 'includes/header_styles.css'; ?>
        
        /* Container */
        .container { 
            padding: 20px 16px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Welcome */
        .welcome {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .welcome h1 { 
            font-size: 1.5em; 
            font-weight: 700;
            background: linear-gradient(90deg, #00c3ff, #ffff1c); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .welcome p {
            font-size: 0.9em;
            color: #b0bec5;
        }
        
        /* Tabs Dias */
        .tabs-dias {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding: 0 0 10px 0;
            margin-bottom: 20px;
            -webkit-overflow-scrolling: touch;
        }
        .tabs-dias::-webkit-scrollbar {
            height: 4px;
        }
        .tabs-dias::-webkit-scrollbar-thumb {
            background: #00c3ff;
            border-radius: 2px;
        }
        .tab-dia {
            flex-shrink: 0;
            background: rgba(20, 30, 50, 0.6);
            border: 2px solid #22334a;
            border-radius: 12px;
            padding: 12px 20px;
            color: #b0bec5;
            font-weight: 600;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            min-width: 100px;
        }
        .tab-dia.active {
            background: rgba(0, 195, 255, 0.2);
            border-color: #00c3ff;
            color: #00c3ff;
        }
        .tab-dia.hoje {
            border-color: #ffff1c;
        }
        
        /* Aulas List */
        .dia-content {
            display: none;
        }
        .dia-content.active {
            display: block;
        }
        .aula-card {
            background: rgba(20, 30, 50, 0.8);
            border-left: 4px solid #00c3ff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .aula-horario {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .aula-hora {
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            color: #222;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9em;
        }
        .aula-duracao {
            color: #b0bec5;
            font-size: 0.8em;
        }
        .aula-info {
            margin-bottom: 8px;
        }
        .aula-turma {
            font-size: 1.1em;
            font-weight: 700;
            color: #00c3ff;
            margin-bottom: 4px;
        }
        .aula-disciplina {
            font-size: 0.95em;
            color: #fff;
            margin-bottom: 4px;
        }
        .aula-sala {
            display: inline-block;
            background: rgba(255, 255, 28, 0.2);
            color: #ffff1c;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #b0bec5;
        }
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 16px;
        }
        .empty-state-text {
            font-size: 1em;
        }
        
        /* Summary */
        .summary {
            background: rgba(20, 30, 50, 0.8);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
        }
        .summary-value {
            font-size: 2em;
            font-weight: 700;
            color: #00c3ff;
            margin-bottom: 4px;
        }
        .summary-label {
            font-size: 0.9em;
            color: #b0bec5;
        }
        
        /* Desktop */
        @media (min-width: 768px) {
            .header-mobile { padding: 20px 32px; }
            .logo-mobile { font-size: 1.5em; }
            .container { padding: 32px 24px; }
            .welcome h1 { font-size: 2em; }
            .aula-card { padding: 20px; }
        }
    </style>
</head>
<body>
<?php 
$page_title = 'Meu Horário';
include 'includes/header_mobile.php'; 
?>
    
    <div class="container">
        <div class="welcome">
            <h1><?= htmlspecialchars($professor_nome) ?></h1>
            <p>Seu horário semanal de aulas</p>
        </div>
        
        <?php 
        $total_aulas = count($horarios);
        if ($total_aulas > 0):
        ?>
        <div class="summary">
            <div class="summary-value"><?= $total_aulas ?></div>
            <div class="summary-label">aulas semanais</div>
        </div>
        <?php endif; ?>
        
        <div class="tabs-dias">
            <?php foreach ($dias_semana as $dia): ?>
                <?php $tem_aulas = count($horarios_por_dia[$dia]) > 0; ?>
                <button class="tab-dia <?= $dia === $dia_hoje ? 'active hoje' : '' ?>" 
                        onclick="mostrarDia('<?= $dia ?>')"
                        <?= !$tem_aulas ? 'style="opacity:0.5"' : '' ?>>
                    <div><?= $dia ?></div>
                    <div style="font-size:0.75em;margin-top:4px;"><?= count($horarios_por_dia[$dia]) ?> aulas</div>
                </button>
            <?php endforeach; ?>
        </div>
        
        <?php foreach ($dias_semana as $dia): ?>
        <div class="dia-content <?= $dia === $dia_hoje ? 'active' : '' ?>" id="dia-<?= $dia ?>">
            <?php if (count($horarios_por_dia[$dia]) > 0): ?>
                <?php foreach ($horarios_por_dia[$dia] as $aula): ?>
                <div class="aula-card">
                    <div class="aula-horario">
                        <span class="aula-hora"><?= substr($aula['hora_inicio'], 0, 5) ?> - <?= substr($aula['hora_fim'], 0, 5) ?></span>
                    </div>
                    <div class="aula-info">
                        <div class="aula-turma"><?= htmlspecialchars($aula['turma_nome']) ?></div>
                        <div class="aula-disciplina"><?= htmlspecialchars($aula['disciplina_nome']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">Nenhuma aula neste dia</div>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        function mostrarDia(dia) {
            // Remover classe active de todas as tabs
            document.querySelectorAll('.tab-dia').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Adicionar classe active na tab clicada
            event.target.closest('.tab-dia').classList.add('active');
            
            // Esconder todos os conteúdos
            document.querySelectorAll('.dia-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Mostrar conteúdo do dia selecionado
            document.getElementById('dia-' + dia).classList.add('active');
        }
    </script>
</body>
</html>
