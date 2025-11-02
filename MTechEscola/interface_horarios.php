<?php
session_start();
require_once 'db_connect_horarios.php';

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$turma_id = $_GET['turma_id'] ?? '';
$view_type = $_GET['view'] ?? 'turma'; // 'turma' ou 'professor'
$professor_id = $_GET['professor_id'] ?? '';

try {
    // Busca turmas
    $turmas = $conn->query("SELECT id, nome, COALESCE(horario_fixo, FALSE) as horario_fixo FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $professores = $conn->query("SELECT id, nome FROM professores WHERE ativo = TRUE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

    // Busca intervalos/horários disponíveis ÚNICOS (sem duplicar por dia da semana)
    $intervalos = $conn->query("SELECT DISTINCT hora_inicio, hora_fim FROM intervalos ORDER BY hora_inicio")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

// Dias da semana
$dias_semana = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta'];

// Busca horários da turma selecionada
$horarios = [];
if ($turma_id) {
    try {
        $stmt = $conn->prepare("
            SELECT ha.*, d.nome AS disciplina, p.nome AS professor, ha.dia_semana, ha.hora_inicio, ha.hora_fim
            FROM horarios_aulas ha
            LEFT JOIN disciplinas d ON ha.disciplina_id = d.id
            LEFT JOIN professores p ON ha.professor_id = p.id
            WHERE ha.turma_id = ? AND COALESCE(ha.ativo, TRUE) = TRUE
            ORDER BY ha.dia_semana, ha.hora_inicio
        ");
        $stmt->execute([$turma_id]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $horarios = [];
    }
}

// Busca horários do professor selecionado
$horarios_professor = [];
if ($professor_id && $view_type === 'professor') {
    try {
        $stmt = $conn->prepare("
            SELECT ha.*, d.nome AS disciplina, t.nome AS turma, ha.dia_semana, ha.hora_inicio, ha.hora_fim
            FROM horarios_aulas ha
            LEFT JOIN disciplinas d ON ha.disciplina_id = d.id
            LEFT JOIN turmas t ON ha.turma_id = t.id
            WHERE ha.professor_id = ? AND COALESCE(ha.ativo, TRUE) = TRUE
            ORDER BY ha.dia_semana, ha.hora_inicio
        ");
        $stmt->execute([$professor_id]);
        $horarios_professor = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $horarios_professor = [];
    }
}

$msg = '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Horários - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 1200px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .tabs { display: flex; gap: 8px; margin-bottom: 24px; }
        .tab { background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .tab.active { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; }
        .filters { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin-bottom: 24px; }
        select, input { width: 100%; max-width: 300px; padding: 8px; border-radius: 8px; border: none; margin-top: 8px; font-size: 1em; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 24px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 8px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.05); box-shadow: 0 4px 16px #00c3ff55; }
        .btn-secondary { background: linear-gradient(90deg, #666 40%, #999 100%); }
        .horario-grid { overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); }
        th, td { padding: 12px; border: 1px solid rgba(255,255,255,0.1); text-align: center; }
        th { background: rgba(0,195,255,0.2); color: #ffff1c; font-weight: 700; }
        td { background: rgba(255,255,255,0.03); }
        td:hover { background: rgba(0,195,255,0.1); }
        .aula-cell { cursor: pointer; min-height: 60px; }
        .aula-info { font-size: 0.9em; }
        .aula-disciplina { font-weight: 700; color: #00c3ff; }
        .aula-professor { color: #ffff1c; font-size: 0.85em; }
        .empty-cell { color: #999; font-style: italic; }
        .fixed-badge { background: #ff3c3c; color: #fff; font-size: 0.7em; padding: 2px 6px; border-radius: 4px; margin-left: 8px; }
        .msg { background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;text-align:center; }
        @media print {
            @page {
                size: A4 portrait;
                margin: 0.5cm;
            }
            
            body { 
                background: #fff !important; 
                color: #000 !important;
                font-family: Arial, sans-serif;
                padding: 0;
                margin: 0;
            }
            
            /* Ocultar header do sistema */
            header, nav, .navbar, [class*="header"], [id*="header"] {
                display: none !important;
            }
            
            .container { 
                box-shadow: none !important; 
                background: #fff !important; 
                padding: 5px !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            
            .filters, .tabs, .btn, .msg { 
                display: none !important; 
            }
            
            h1 {
                font-size: 14pt;
                color: #000 !important;
                margin: 0 0 5px 0;
                text-align: center;
                background: none !important;
                -webkit-text-fill-color: #000 !important;
            }
            
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 5px;
                padding-bottom: 5px;
                border-bottom: 1px solid #000;
            }
            
            .print-header h2 {
                margin: 2px 0;
                font-size: 12pt;
                font-weight: bold;
            }
            
            .print-header p {
                margin: 1px 0;
                font-size: 9pt;
            }
            
            table { 
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1px solid #000 !important;
                page-break-inside: auto;
                font-size: 7pt;
                margin: 0;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            th { 
                background: #e0e0e0 !important; 
                color: #000 !important;
                border: 1px solid #000 !important;
                padding: 3px 2px !important;
                font-weight: bold;
                text-align: center;
                font-size: 8pt;
            }
            
            td { 
                background: #fff !important; 
                color: #000 !important;
                border: 1px solid #666 !important;
                padding: 2px 1px !important;
                vertical-align: top;
                min-height: 25px;
                line-height: 1.2;
            }
            
            td:first-child {
                font-weight: bold;
                text-align: center;
                background: #f5f5f5 !important;
                font-size: 7pt;
            }
            
            .aula-info {
                font-size: 6.5pt;
                line-height: 1.1;
            }
            
            .aula-disciplina {
                font-weight: bold;
                color: #000 !important;
                margin-bottom: 1px;
            }
            
            .aula-professor {
                color: #444 !important;
                font-size: 6pt;
            }
            
            .empty-cell {
                color: #ccc !important;
                font-size: 8pt;
            }
            
            .print-footer {
                display: block !important;
                text-align: center;
                margin-top: 5px;
                padding-top: 3px;
                border-top: 1px solid #000;
                font-size: 6pt;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<!-- Cabeçalho para impressão -->
<div class="print-header" style="display:none;">
    <h2>CEM - Centro Educacional</h2>
    <p><strong>Horário de Aulas - <?= date('Y') ?></strong></p>
    <?php if ($turma_id): 
        $turma_nome = '';
        foreach ($turmas as $t) {
            if ($t['id'] == $turma_id) {
                $turma_nome = $t['nome'];
                break;
            }
        }
    ?>
        <p>Turma: <strong><?= htmlspecialchars($turma_nome) ?></strong></p>
    <?php elseif ($professor_id): 
        $professor_nome = '';
        foreach ($professores as $p) {
            if ($p['id'] == $professor_id) {
                $professor_nome = $p['nome'];
                break;
            }
        }
    ?>
        <p>Professor(a): <strong><?= htmlspecialchars($professor_nome) ?></strong></p>
    <?php endif; ?>
</div>

<div class="container">
    <h1>Gerenciamento de Horários de Aulas</h1>
    
    <div class="tabs">
        <div class="tab <?= $view_type === 'turma' ? 'active' : '' ?>" onclick="location.href='?view=turma'">Por Turma</div>
        <div class="tab <?= $view_type === 'professor' ? 'active' : '' ?>" onclick="location.href='?view=professor'">Por Professor</div>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="filters">
        <?php if ($view_type === 'turma'): ?>
            <form method="get">
                <input type="hidden" name="view" value="turma">
                <label for="turma_id">Selecione a Turma:</label>
                <select name="turma_id" id="turma_id" onchange="this.form.submit()">
                    <option value="">Selecione uma turma</option>
                    <?php foreach ($turmas as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $turma_id == $t['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['nome']) ?>
                            <?= $t['horario_fixo'] ? ' (Horário Fixo)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($turma_id): ?>
                    <a href="editar_horario_turma.php?turma_id=<?= $turma_id ?>" class="btn">Editar Horário</a>
                    <a href="gerar_horario_auto.php?turma_id=<?= $turma_id ?>" class="btn btn-secondary" onclick="return confirm('Gerar novo horário automaticamente?');">Gerar Automaticamente</a>
                    <button onclick="imprimirHorario()" class="btn">🖨️ Imprimir/PDF</button>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <form method="get">
                <input type="hidden" name="view" value="professor">
                <label for="professor_id">Selecione o Professor:</label>
                <select name="professor_id" id="professor_id" onchange="this.form.submit()">
                    <option value="">Selecione um professor</option>
                    <?php foreach ($professores as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $professor_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($professor_id): ?>
                    <a href="disponibilidade_professor.php?professor_id=<?= $professor_id ?>" class="btn">Gerenciar Disponibilidade</a>
                    <button onclick="imprimirHorario()" class="btn">🖨️ Imprimir/PDF</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
    
    <?php if ($turma_id && $view_type === 'turma'): ?>
        <div class="horario-grid">
            <table>
                <thead>
                    <tr>
                        <th>Horário</th>
                        <?php foreach ($dias_semana as $dia): ?>
                            <th><?= $dia ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intervalos as $intervalo): ?>
                        <tr>
                            <td><strong><?= substr($intervalo['hora_inicio'], 0, 5) ?> - <?= substr($intervalo['hora_fim'], 0, 5) ?></strong></td>
                            <?php foreach ($dias_semana as $dia_num => $dia_nome): ?>
                                <td class="aula-cell">
                                    <?php
                                    // Busca aula para este horário
                                    $aula_encontrada = false;
                                    foreach ($horarios as $h) {
                                        if ($h['dia_semana'] == $dia_num && $h['hora_inicio'] == $intervalo['hora_inicio']) {
                                            $aula_encontrada = true;
                                            echo '<div class="aula-info">';
                                            echo '<div class="aula-disciplina">' . htmlspecialchars($h['disciplina'] ?? '-') . '</div>';
                                            echo '<div class="aula-professor">' . htmlspecialchars($h['professor'] ?? 'Sem professor') . '</div>';
                                            echo '</div>';
                                            break;
                                        }
                                    }
                                    if (!$aula_encontrada) {
                                        echo '<span class="empty-cell">-</span>';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($professor_id && $view_type === 'professor'): ?>
        <div class="horario-grid">
            <table>
                <thead>
                    <tr>
                        <th>Horário</th>
                        <?php foreach ($dias_semana as $dia): ?>
                            <th><?= $dia ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intervalos as $intervalo): ?>
                        <tr>
                            <td><strong><?= substr($intervalo['hora_inicio'], 0, 5) ?> - <?= substr($intervalo['hora_fim'], 0, 5) ?></strong></td>
                            <?php foreach ($dias_semana as $dia_num => $dia_nome): ?>
                                <td class="aula-cell">
                                    <?php
                                    $aula_encontrada = false;
                                    foreach ($horarios_professor as $h) {
                                        if ($h['dia_semana'] == $dia_num && $h['hora_inicio'] == $intervalo['hora_inicio']) {
                                            $aula_encontrada = true;
                                            echo '<div class="aula-info">';
                                            echo '<div class="aula-disciplina">' . htmlspecialchars($h['disciplina'] ?? '-') . '</div>';
                                            echo '<div class="aula-professor">' . htmlspecialchars($h['turma'] ?? '-') . '</div>';
                                            echo '</div>';
                                            break;
                                        }
                                    }
                                    if (!$aula_encontrada) {
                                        echo '<span class="empty-cell">-</span>';
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- Rodapé para impressão -->
    <div class="print-footer" style="display:none;">
        <p>Documento gerado em: <?= date('d/m/Y H:i') ?> | CEM - Centro Educacional | <?= date('Y') ?></p>
    </div>
</div>

<script>
function imprimirHorario() {
    // Ocultar elementos desnecessários
    document.querySelectorAll('.btn, .filters, .tabs').forEach(el => {
        el.style.display = 'none';
    });
    
    // Mostrar cabeçalho e rodapé de impressão
    document.querySelectorAll('.print-header, .print-footer').forEach(el => {
        el.style.display = 'block';
    });
    
    // Configurar título da página para o PDF
    const originalTitle = document.title;
    const turmaSelect = document.getElementById('turma_id');
    const professorSelect = document.getElementById('professor_id');
    
    if (turmaSelect && turmaSelect.value) {
        const turmaNome = turmaSelect.options[turmaSelect.selectedIndex].text;
        document.title = 'Horário - ' + turmaNome;
    } else if (professorSelect && professorSelect.value) {
        const professorNome = professorSelect.options[professorSelect.selectedIndex].text;
        document.title = 'Horário - ' + professorNome;
    }
    
    // Imprimir
    window.print();
    
    // Restaurar após impressão
    setTimeout(() => {
        document.title = originalTitle;
        document.querySelectorAll('.btn, .filters, .tabs').forEach(el => {
            el.style.display = '';
        });
        document.querySelectorAll('.print-header, .print-footer').forEach(el => {
            el.style.display = 'none';
        });
    }, 500);
}

// Atalho de teclado: Ctrl+P
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        imprimirHorario();
    }
});
</script>
</body>
</html>
