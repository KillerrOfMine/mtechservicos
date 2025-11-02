<?php
session_start();
require_once '../db_connect_horarios.php';

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
$atividade_id = $_GET['id'] ?? 0;

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    try {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $valor = floatval($_POST['valor']);
        $data_entrega = $_POST['data_entrega'];
        
        if (empty($titulo)) {
            throw new Exception('O título não pode estar vazio!');
        }
        
        // Verificar se a atividade pertence ao professor
        $stmt = $conn->prepare("
            SELECT a.id 
            FROM atividades a
            JOIN turma_disciplina_professor tdp ON a.turma_id = tdp.turma_id AND a.disciplina_id = tdp.disciplina_id
            WHERE a.id = ? AND tdp.professor_id = ?
        ");
        $stmt->execute([$atividade_id, $professor_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Você não tem permissão para editar esta atividade.');
        }
        
        $stmt = $conn->prepare("
            UPDATE atividades 
            SET titulo = ?, descricao = ?, valor = ?, data_entrega = ?
            WHERE id = ?
        ");
        $stmt->execute([$titulo, $descricao, $valor, $data_entrega, $atividade_id]);
        
        $_SESSION['msg_sucesso'] = 'Atividade atualizada com sucesso!';
        header('Location: atividades.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['msg_erro'] = 'Erro: ' . $e->getMessage();
    }
}

// Buscar dados da atividade
$stmt = $conn->prepare("
    SELECT a.*, t.nome as turma_nome, d.nome as disciplina_nome
    FROM atividades a
    JOIN turmas t ON a.turma_id = t.id
    JOIN disciplinas d ON a.disciplina_id = d.id
    JOIN turma_disciplina_professor tdp ON a.turma_id = tdp.turma_id AND a.disciplina_id = tdp.disciplina_id
    WHERE a.id = ? AND tdp.professor_id = ?
");
$stmt->execute([$atividade_id, $professor_id]);
$atividade = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$atividade) {
    $_SESSION['msg_erro'] = 'Atividade não encontrada ou sem permissão.';
    header('Location: atividades.php');
    exit;
}

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
    <title>Editar Atividade - MTech Escola</title>
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
        
        .info-card {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
            border-left: 4px solid #00c3ff;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .info-label { color: #b0bec5; }
        .info-value { color: #00c3ff; font-weight: 600; }
        
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
        
        .form-section input,
        .form-section textarea {
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
        
        .form-section textarea {
            min-height: 120px;
            resize: vertical;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }
        
        .form-section input:focus,
        .form-section textarea:focus {
            outline: none;
            border-color: #00c3ff;
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
            box-shadow: 0 4px 16px rgba(0, 195, 255, 0.3);
            transition: all 0.2s;
            margin-bottom: 12px;
        }
        
        .btn:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 195, 255, 0.5);
        }
        
        .btn-cancelar {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 2px solid rgba(255,255,255,0.3);
            text-align: center;
            text-decoration: none;
            display: block;
        }
    </style>
</head>
<body>
<?php 
$page_title = 'Editar Atividade';
include 'includes/header_mobile.php'; 
?>

<div class="container">
    <div class="page-header">
        <h1>✏️ Editar Atividade</h1>
        <p>Atualize as informações da atividade</p>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg <?= $msg_tipo === 'error' ? 'msg-error' : '' ?>">
            <?= $msg_tipo === 'error' ? '✗' : '✓' ?> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    
    <div class="info-card">
        <div class="info-row">
            <span class="info-label">Turma:</span>
            <span class="info-value"><?= htmlspecialchars($atividade['turma_nome']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Disciplina:</span>
            <span class="info-value"><?= htmlspecialchars($atividade['disciplina_nome']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Bimestre:</span>
            <span class="info-value"><?= $atividade['bimestre'] ?>º Bimestre</span>
        </div>
        <div class="info-row">
            <span class="info-label">Tipo:</span>
            <span class="info-value"><?= $atividade['tipo'] === 'atividade' ? 'Atividade' : 'Avaliação Bimestral' ?></span>
        </div>
    </div>
    
    <form method="post">
        <div class="form-section">
            <label for="titulo">Título *</label>
            <input 
                type="text" 
                name="titulo" 
                id="titulo" 
                value="<?= htmlspecialchars($atividade['titulo']) ?>"
                required
                placeholder="Ex: Trabalho sobre Fotossíntese"
            >
            
            <label for="descricao">Descrição</label>
            <textarea 
                name="descricao" 
                id="descricao"
                placeholder="Descreva os detalhes da atividade..."
            ><?= htmlspecialchars($atividade['descricao']) ?></textarea>
            
            <label for="valor">Valor (pontos) *</label>
            <input 
                type="number" 
                name="valor" 
                id="valor" 
                value="<?= $atividade['valor'] ?>"
                step="0.1"
                min="0"
                max="100"
                required
            >
            
            <label for="data_entrega">Data de Entrega</label>
            <input 
                type="date" 
                name="data_entrega" 
                id="data_entrega" 
                value="<?= $atividade['data_entrega'] ?>"
            >
        </div>
        
        <button type="submit" name="atualizar" class="btn">💾 Salvar Alterações</button>
    </form>
    
    <a href="atividades.php" class="btn btn-cancelar">✕ Cancelar</a>
</div>
</body>
</html>
