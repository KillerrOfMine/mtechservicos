<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db_connect_horarios.php';

// Debug temporário
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

// Exclusão de atividade
$msg = '';
$msg_tipo = 'success';
if (isset($_GET['excluir'])) {
    try {
        $id = $_GET['excluir'];
        // Verificar se a atividade pertence a uma turma/disciplina do professor
        $stmt = $conn->prepare("
            SELECT a.id 
            FROM atividades a
            JOIN turma_disciplina_professor tdp ON a.turma_id = tdp.turma_id AND a.disciplina_id = tdp.disciplina_id
            WHERE a.id = ? AND tdp.professor_id = ?
        ");
        $stmt->execute([$id, $professor_id]);
        
        if ($stmt->fetch()) {
            $stmt = $conn->prepare("DELETE FROM atividades WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['msg_sucesso'] = 'Atividade excluída com sucesso!';
            } else {
                $_SESSION['msg_erro'] = 'Erro ao excluir atividade.';
            }
        } else {
            $_SESSION['msg_erro'] = 'Você não tem permissão para excluir esta atividade.';
        }
        
        header('Location: atividades.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['msg_erro'] = 'Erro: ' . $e->getMessage();
        header('Location: atividades.php');
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

$bimestres = [1, 2, 3, 4];

// Filtros
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$bimestre = $_GET['bimestre'] ?? '';

// Buscar atividades do professor através da relação turma_disciplina_professor
$sql = "SELECT 
    a.id, 
    a.nome, 
    a.valor, 
    a.tipo, 
    t.nome AS turma, 
    d.nome AS disciplina, 
    a.bimestre 
FROM atividades a 
JOIN turmas t ON a.turma_id = t.id 
JOIN disciplinas d ON a.disciplina_id = d.id 
JOIN turma_disciplina_professor tdp ON a.turma_id = tdp.turma_id AND a.disciplina_id = tdp.disciplina_id
WHERE tdp.professor_id = ?";

$params = [$professor_id];
if ($turma_id) { 
    $sql .= " AND a.turma_id = ?"; 
    $params[] = $turma_id; 
}
if ($disciplina_id) { 
    $sql .= " AND a.disciplina_id = ?"; 
    $params[] = $disciplina_id; 
}
if ($bimestre) { 
    $sql .= " AND a.bimestre = ?"; 
    $params[] = $bimestre; 
}
$sql .= " ORDER BY a.bimestre DESC, t.nome, d.nome, a.tipo DESC, a.nome";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar mensagem
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
    <title>Minhas Atividades - MTech Escola</title>
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
        
        /* Header */
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
        .page-header p {
            font-size: 0.9em;
            color: #b0bec5;
        }
        
        /* Messages */
        .msg { 
            background: #00c3ff;
            color: #222;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-weight: 700;
            text-align: center;
            font-size: 0.95em;
        }
        
        .msg-error {
            background: #ff4444;
            color: #fff;
        }
        
        /* Form Section */
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
        
        .form-section select:focus {
            outline: none;
            border-color: #00c3ff;
        }
        
        /* Button Filtrar */
        .btn-filtrar {
            width: 100%;
            padding: 14px;
            background: rgba(0, 195, 255, 0.2);
            border: 2px solid #00c3ff;
            border-radius: 12px;
            color: #00c3ff;
            font-family: 'Orbitron', Arial, sans-serif;
            font-size: 0.95em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-filtrar:active {
            transform: scale(0.98);
            background: rgba(0, 195, 255, 0.3);
        }
        
        /* Atividades Grid */
        .atividades-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .atividade-card {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 12px;
            padding: 16px;
            border-left: 4px solid #00c3ff;
        }
        
        .atividade-card.bimestral {
            border-left-color: #ffff1c;
        }
        
        .atividade-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .atividade-nome {
            font-size: 1.05em;
            font-weight: 700;
            color: #fff;
            flex: 1;
        }
        
        .atividade-valor {
            font-size: 1.1em;
            font-weight: 700;
            color: #ffff1c;
            margin-left: 12px;
        }
        
        .atividade-info {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .info-tag {
            background: rgba(0, 195, 255, 0.2);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75em;
            color: #00c3ff;
        }
        
        .info-tag.bimestre {
            background: rgba(255, 255, 28, 0.2);
            color: #ffff1c;
        }
        
        .atividade-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-family: 'Orbitron', Arial, sans-serif;
            font-size: 0.85em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-editar {
            background: rgba(0, 195, 255, 0.2);
            color: #00c3ff;
            border: 2px solid #00c3ff;
        }
        
        .btn-excluir {
            background: rgba(255, 68, 68, 0.2);
            color: #ff4444;
            border: 2px solid #ff4444;
        }
        
        .btn-action:active {
            transform: scale(0.95);
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
            opacity: 0.5;
        }
        
        /* Summary */
        .summary {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 16px;
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
            font-size: 0.85em;
            color: #b0bec5;
        }
    </style>
</head>
<body>
<?php 
$page_title = 'Atividades';
include 'includes/header_mobile.php'; 
?>

<div class="container">
    <div class="page-header">
        <h1>📝 Minhas Atividades</h1>
        <p>Gerencie avaliações e atividades</p>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg <?= $msg_tipo === 'error' ? 'msg-error' : '' ?>">
            <?= $msg_tipo === 'error' ? '✗' : '✓' ?> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    
    <form method="get" id="formFiltros">
        <div class="form-section">
            <label for="turma_id">Turma</label>
            <select name="turma_id" id="turma_id">
                <option value="">Todas as turmas</option>
                <?php foreach ($turmas as $turma): ?>
                    <option value="<?= $turma['id'] ?>" <?= ($turma_id == $turma['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($turma['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="disciplina_id">Disciplina</label>
            <select name="disciplina_id" id="disciplina_id">
                <option value="">Todas as disciplinas</option>
                <?php foreach ($disciplinas as $disc): ?>
                    <option value="<?= $disc['id'] ?>" <?= ($disciplina_id == $disc['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($disc['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="bimestre">Bimestre</label>
            <select name="bimestre" id="bimestre">
                <option value="">Todos os bimestres</option>
                <?php foreach ($bimestres as $b): ?>
                    <option value="<?= $b ?>" <?= ($bimestre == $b) ? 'selected' : '' ?>><?= $b ?>º Bimestre</option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn-filtrar">🔍 Filtrar Atividades</button>
        </div>
    </form>
    
    <?php if ($atividades): ?>
    
    <div class="summary">
        <div class="summary-value"><?= count($atividades) ?></div>
        <div class="summary-label">Atividades Cadastradas</div>
    </div>
    
    <div class="atividades-grid">
        <?php foreach ($atividades as $a): ?>
        <div class="atividade-card <?= $a['tipo'] === 'Bimestral' ? 'bimestral' : '' ?>">
            <div class="atividade-header">
                <div class="atividade-nome"><?= htmlspecialchars($a['nome']) ?></div>
                <div class="atividade-valor"><?= number_format($a['valor'], 1, ',', '.') ?></div>
            </div>
            
            <div class="atividade-info">
                <span class="info-tag"><?= htmlspecialchars($a['turma']) ?></span>
                <span class="info-tag"><?= htmlspecialchars($a['disciplina']) ?></span>
                <span class="info-tag bimestre"><?= $a['bimestre'] ?>º Bim</span>
                <span class="info-tag"><?= $a['tipo'] === 'Bimestral' ? '📊 Avaliação' : '📝 Atividade' ?></span>
            </div>
            
            <div class="atividade-actions">
                <a href="editar_atividade.php?id=<?= $a['id'] ?>" class="btn-action btn-editar" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">✏️ Editar</a>
                <button class="btn-action btn-excluir" onclick="confirmarExclusao(<?= $a['id'] ?>)">🗑️ Excluir</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    
    <div class="empty-state">
        <div class="empty-state-icon">📝</div>
        <p>Nenhuma atividade encontrada.</p>
        <p style="font-size: 0.85em; margin-top: 8px;">
            <?= ($turma_id || $disciplina_id || $bimestre) ? 'Tente ajustar os filtros.' : 'Cadastre atividades para suas turmas.' ?>
        </p>
    </div>
    
    <?php endif; ?>
</div>

<script>
// Confirmar exclusão
function confirmarExclusao(id) {
    if (confirm('Tem certeza que deseja excluir esta atividade?')) {
        window.location.href = 'atividades.php?excluir=' + id;
    }
}
</script>
</body>
</html>
