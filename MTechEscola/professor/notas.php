<?php
session_start();
require_once '../db_connect_horarios.php';

// Verificar sessão do professor
if (!isset($_SESSION['professor_id'])) {
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['is_professor']) && $_SESSION['is_professor']) {
        $_SESSION['professor_id'] = $_SESSION['usuario_id'];
        $_SESSION['professor_nome'] = $_SESSION['usuario_nome'];
    } else {
        header('Location: login.php');
        exit;
    }
}

$professor_id = $_SESSION['professor_id'];
$professor_nome = $_SESSION['professor_nome'];

// Processar salvamento de notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_notas'])) {
    try {
        $atividade_id = $_POST['atividade_id'];
        
        if (isset($_POST['notas']) && is_array($_POST['notas'])) {
            foreach ($_POST['notas'] as $aluno_id => $nota) {
                if ($nota !== '' && $nota !== null) {
                    // Verificar se já existe nota
                    $stmt = $conn->prepare("SELECT id FROM notas WHERE aluno_id = ? AND atividade_id = ?");
                    $stmt->execute([$aluno_id, $atividade_id]);
                    $nota_existente = $stmt->fetch();
                    
                    if ($nota_existente) {
                        $stmt = $conn->prepare("UPDATE notas SET nota = ? WHERE id = ?");
                        $stmt->execute([$nota, $nota_existente['id']]);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO notas (aluno_id, atividade_id, nota) VALUES (?, ?, ?)");
                        $stmt->execute([$aluno_id, $atividade_id, $nota]);
                    }
                }
            }
            $_SESSION['msg_sucesso'] = 'Notas lançadas com sucesso!';
        } else {
            $_SESSION['msg_erro'] = 'Nenhuma nota foi informada!';
        }
        
        header("Location: notas.php?atividade_id=$atividade_id");
        exit;
    } catch (Exception $e) {
        $_SESSION['msg_erro'] = 'Erro: ' . $e->getMessage();
        header("Location: notas.php");
        exit;
    }
}

// Buscar turmas do professor
$stmt = $conn->prepare("
    SELECT DISTINCT t.id, t.nome 
    FROM turmas t
    JOIN turma_disciplina_professor tdp ON t.id = tdp.turma_id
    WHERE tdp.professor_id = ?
    ORDER BY t.nome
");
$stmt->execute([$professor_id]);
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar disciplinas do professor
$stmt = $conn->prepare("
    SELECT DISTINCT d.id, d.nome 
    FROM disciplinas d
    JOIN turma_disciplina_professor tdp ON d.id = tdp.disciplina_id
    WHERE tdp.professor_id = ?
    ORDER BY d.nome
");
$stmt->execute([$professor_id]);
$disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$bimestre = $_GET['bimestre'] ?? '';
$atividade_id = $_GET['atividade_id'] ?? '';

// Buscar atividades se turma, disciplina e bimestre selecionados
$atividades = [];
if ($turma_id && $disciplina_id && $bimestre) {
    $stmt = $conn->prepare("
        SELECT id, nome, valor, tipo
        FROM atividades
        WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ?
        ORDER BY tipo DESC, nome
    ");
    $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
    $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar alunos com notas se atividade selecionada
$alunos = [];
$atividade_info = null;
if ($atividade_id) {
    // Info da atividade
    $stmt = $conn->prepare("
        SELECT a.*, t.nome as turma_nome, d.nome as disciplina_nome
        FROM atividades a
        JOIN turmas t ON a.turma_id = t.id
        JOIN disciplinas d ON a.disciplina_id = d.id
        WHERE a.id = ?
    ");
    $stmt->execute([$atividade_id]);
    $atividade_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($atividade_info) {
        // Alunos com notas
        $stmt = $conn->prepare("
            SELECT a.id, a.nome, n.nota
            FROM alunos a
            LEFT JOIN notas n ON a.id = n.aluno_id AND n.atividade_id = ?
            WHERE a.turma_id = ?
            ORDER BY a.nome
        ");
        $stmt->execute([$atividade_id, $atividade_info['turma_id']]);
        $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Atualizar filtros
        $turma_id = $atividade_info['turma_id'];
        $disciplina_id = $atividade_info['disciplina_id'];
        $bimestre = $atividade_info['bimestre'];
    }
}

// Mensagens
$msg = '';
$msg_tipo = 'success';
if (isset($_SESSION['msg_sucesso'])) {
    $msg = $_SESSION['msg_sucesso'];
    unset($_SESSION['msg_sucesso']);
} elseif (isset($_SESSION['msg_erro'])) {
    $msg = $_SESSION['msg_erro'];
    $msg_tipo = 'error';
    unset($_SESSION['msg_erro']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançamento de Notas - MTech Escola</title>
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
        
        .container { padding: 20px 16px; max-width: 600px; margin: 0 auto; }
        
        .page-header {
            background: rgba(20, 30, 50, 0.9);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .page-header h1 { 
            font-size: 1.8em; 
            font-weight: 700;
            background: linear-gradient(90deg, #00c3ff, #ffff1c); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .page-header p { font-size: 0.9em; color: #b0bec5; }
        
        .msg { 
            background: #00c3ff;
            color: #222;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-weight: 700;
            text-align: center;
        }
        .msg-error { background: #ff4444; color: #fff; }
        
        .form-section {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-section label {
            display: block;
            font-size: 0.9em;
            color: #00c3ff;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-section select {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(0,195,255,0.3);
            border-radius: 10px;
            color: #fff;
            font-family: 'Orbitron', Arial, sans-serif;
            font-size: 0.95em;
            margin-bottom: 16px;
        }
        
        .form-section select option {
            background: #1a2636;
            color: #fff;
            padding: 10px;
        }
        
        .form-section select:focus {
            outline: none;
            border-color: #00c3ff;
        }
        
        .atividade-info {
            background: rgba(0, 195, 255, 0.1);
            border-left: 4px solid #00c3ff;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .atividade-info h3 {
            color: #00c3ff;
            font-size: 1.1em;
            margin-bottom: 8px;
        }
        
        .atividade-info p {
            font-size: 0.85em;
            color: #b0bec5;
            margin-bottom: 4px;
        }
        
        .alunos-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .aluno-card {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        
        .aluno-info { flex: 1; }
        .aluno-nome { font-size: 0.95em; font-weight: 600; color: #fff; }
        
        .nota-input {
            width: 80px;
            padding: 10px;
            background: rgba(0, 195, 255, 0.1);
            border: 2px solid rgba(0, 195, 255, 0.3);
            border-radius: 8px;
            color: #fff;
            font-family: 'Orbitron', Arial, sans-serif;
            font-size: 1.1em;
            font-weight: 700;
            text-align: center;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            border: none;
            border-radius: 12px;
            color: #222;
            font-family: 'Orbitron', Arial, sans-serif;
            font-size: 1em;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .summary {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .summary-value { font-size: 2em; font-weight: 700; color: #00c3ff; }
        .summary-label { font-size: 0.75em; color: #b0bec5; }
    </style>
</head>
<body>
<?php 
$page_title = 'Notas';
include 'includes/header_mobile.php'; 
?>

<div class="container">
    <div class="page-header">
        <h1>📊 Lançamento de Notas</h1>
        <p>Registre as notas das atividades</p>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg <?= $msg_tipo === 'error' ? 'msg-error' : '' ?>">
            <?= $msg_tipo === 'error' ? '✗' : '✓' ?> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    
    <form method="get">
        <div class="form-section">
            <label>Turma</label>
            <select name="turma_id" onchange="this.form.submit()">
                <option value="">Selecione</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $turma_id == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Disciplina</label>
            <select name="disciplina_id" onchange="this.form.submit()">
                <option value="">Selecione</option>
                <?php foreach ($disciplinas as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $disciplina_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Bimestre</label>
            <select name="bimestre" onchange="this.form.submit()">
                <option value="">Selecione</option>
                <option value="1" <?= $bimestre == 1 ? 'selected' : '' ?>>1º Bimestre</option>
                <option value="2" <?= $bimestre == 2 ? 'selected' : '' ?>>2º Bimestre</option>
                <option value="3" <?= $bimestre == 3 ? 'selected' : '' ?>>3º Bimestre</option>
                <option value="4" <?= $bimestre == 4 ? 'selected' : '' ?>>4º Bimestre</option>
            </select>
            
            <?php if ($atividades): ?>
                <label>Atividade</label>
                <select name="atividade_id" onchange="this.form.submit()">
                    <option value="">Selecione uma atividade</option>
                    <?php foreach ($atividades as $atv): ?>
                        <option value="<?= $atv['id'] ?>" <?= $atividade_id == $atv['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($atv['nome']) ?> (<?= $atv['tipo'] ?> - <?= number_format($atv['valor'], 1) ?> pts)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
    </form>
    
    <?php if ($alunos && $atividade_info): ?>
    
    <div class="atividade-info">
        <h3><?= htmlspecialchars($atividade_info['nome']) ?></h3>
        <p><strong>Turma:</strong> <?= htmlspecialchars($atividade_info['turma_nome']) ?></p>
        <p><strong>Disciplina:</strong> <?= htmlspecialchars($atividade_info['disciplina_nome']) ?></p>
        <p><strong>Valor:</strong> <?= number_format($atividade_info['valor'], 1) ?> pontos | <strong>Tipo:</strong> <?= $atividade_info['tipo'] ?></p>
    </div>
    
    <div class="summary">
        <div>
            <div class="summary-value"><?= count($alunos) ?></div>
            <div class="summary-label">Alunos</div>
        </div>
        <div>
            <div class="summary-value" id="mediaGeral">0.0</div>
            <div class="summary-label">Média</div>
        </div>
    </div>
    
    <form method="post">
        <input type="hidden" name="atividade_id" value="<?= $atividade_id ?>">
        
        <div class="alunos-grid">
            <?php foreach ($alunos as $aluno): ?>
            <div class="aluno-card">
                <div class="aluno-info">
                    <div class="aluno-nome"><?= htmlspecialchars($aluno['nome']) ?></div>
                </div>
                <input 
                    type="number" 
                    name="notas[<?= $aluno['id'] ?>]" 
                    class="nota-input" 
                    min="0" 
                    max="<?= $atividade_info['valor'] ?>" 
                    step="0.1"
                    value="<?= $aluno['nota'] !== null ? number_format($aluno['nota'], 1, '.', '') : '' ?>"
                    placeholder="0.0"
                    onchange="calcularMedia()"
                >
            </div>
            <?php endforeach; ?>
        </div>
        
        <button type="submit" name="salvar_notas" class="btn">💾 Salvar Notas</button>
    </form>
    
    <?php else: ?>
        <div class="form-section" style="text-align:center; color:#b0bec5;">
            <p>Selecione turma, disciplina, bimestre e atividade para lançar as notas.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function calcularMedia() {
    const inputs = document.querySelectorAll('.nota-input');
    let soma = 0, count = 0;
    inputs.forEach(input => {
        const valor = parseFloat(input.value);
        if (!isNaN(valor) && valor >= 0) {
            soma += valor;
            count++;
        }
    });
    document.getElementById('mediaGeral').textContent = count > 0 ? (soma / count).toFixed(1) : '0.0';
}

document.addEventListener('DOMContentLoaded', calcularMedia);
</script>
</body>
</html>
