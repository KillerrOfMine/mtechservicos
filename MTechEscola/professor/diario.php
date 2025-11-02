<?php
session_start();
require_once '../db_connect_horarios.php';
require_once '../includes/auth.php';

$professor_id = $_SESSION['professor_id'];
$professor_nome = $_SESSION['professor_nome'];

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_conteudo'])) {
    try {
        $turma_id = $_POST['turma_id'];
        $disciplina_id = $_POST['disciplina_id'];
        $data_aula = $_POST['data_aula'];
        $conteudo = trim($_POST['conteudo']);
        
        if (empty($conteudo)) {
            throw new Exception('O conteúdo não pode estar vazio!');
        }
        
        // Verificar se já existe conteúdo para esta aula
        $stmt = $conn->prepare("
            SELECT id FROM conteudos_aula 
            WHERE turma_id = ? AND disciplina_id = ? AND data_aula = ?
        ");
        $stmt->execute([$turma_id, $disciplina_id, $data_aula]);
        
        if ($stmt->fetch()) {
            $_SESSION['msg_erro'] = 'Já existe conteúdo cadastrado para esta aula!';
        } else {
            // Usar usuario_id = 1 (admin) para satisfazer FK constraint
            $stmt = $conn->prepare("
                INSERT INTO conteudos_aula (turma_id, disciplina_id, data_aula, conteudo, usuario_id) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$turma_id, $disciplina_id, $data_aula, $conteudo]);
            $_SESSION['msg_sucesso'] = 'Conteúdo cadastrado com sucesso!';
        }
        
        header("Location: diario.php?turma_id=$turma_id&disciplina_id=$disciplina_id&data_aula=$data_aula");
        exit;
    } catch (Exception $e) {
        $_SESSION['msg_erro'] = 'Erro: ' . $e->getMessage();
        header("Location: diario.php");
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

// Pré-preencher campos se vier de outro form
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$data_aula = $_GET['data_aula'] ?? date('Y-m-d');

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
    <title>Diário de Classe - MTech Escola</title>
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
        
        .form-section select,
        .form-section input[type="date"],
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
        
        .form-section select option {
            background: #1a2636;
            color: #fff;
            padding: 10px;
        }
        
        .form-section textarea {
            min-height: 150px;
            resize: vertical;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }
        
        .form-section select:focus,
        .form-section input:focus,
        .form-section textarea:focus {
            outline: none;
            border-color: #00c3ff;
        }
        
        .char-count {
            text-align: right;
            font-size: 0.8em;
            color: #b0bec5;
            margin-top: -12px;
            margin-bottom: 16px;
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
        }
        
        .btn:active {
            transform: scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 195, 255, 0.5);
        }
        
        .info-box {
            background: rgba(0, 195, 255, 0.1);
            border-left: 4px solid #00c3ff;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #b0bec5;
        }
        
        .info-box strong {
            color: #00c3ff;
        }
    </style>
</head>
<body>
<?php 
$page_title = 'Diário de Classe';
include 'includes/header_mobile.php'; 
?>

<div class="container">
    <div class="page-header">
        <h1>📖 Diário de Classe</h1>
        <p>Registre o conteúdo ministrado na aula</p>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg <?= $msg_tipo === 'error' ? 'msg-error' : '' ?>">
            <?= $msg_tipo === 'error' ? '✗' : '✓' ?> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    
    <div class="info-box">
        <strong>💡 Dica:</strong> Descreva de forma clara e objetiva o conteúdo ministrado, os tópicos abordados e observações relevantes sobre a aula.
    </div>
    
    <form method="post">
        <div class="form-section">
            <label for="turma_id">Turma *</label>
            <select name="turma_id" id="turma_id" required>
                <option value="">Selecione a turma</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $turma_id == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="disciplina_id">Disciplina *</label>
            <select name="disciplina_id" id="disciplina_id" required>
                <option value="">Selecione a disciplina</option>
                <?php foreach ($disciplinas as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $disciplina_id == $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="data_aula">Data da Aula *</label>
            <input type="date" name="data_aula" id="data_aula" value="<?= htmlspecialchars($data_aula) ?>" required>
            
            <label for="conteudo">Conteúdo da Aula *</label>
            <textarea 
                name="conteudo" 
                id="conteudo" 
                placeholder="Ex: Introdução à fotossíntese. Explicação sobre as fases do processo (clara e escura). Atividade prática em grupo sobre plantas. Dever de casa: pesquisa sobre clorofila."
                required
                oninput="atualizarContador()"
            ></textarea>
            <div class="char-count">
                <span id="charCount">0</span> caracteres
            </div>
        </div>
        
        <button type="submit" name="cadastrar_conteudo" class="btn">💾 Salvar Conteúdo</button>
    </form>
</div>

<script>
function atualizarContador() {
    const textarea = document.getElementById('conteudo');
    const contador = document.getElementById('charCount');
    contador.textContent = textarea.value.length;
}
</script>
</body>
</html>
