<?php
// AJAX para exibir limites dinamicamente - deve ser o PRIMEIRO bloco do arquivo
require_once 'db_connect_horarios.php';
$limites = [1 => 10.0, 2 => 20.0, 3 => 30.0, 4 => 40.0];
if (isset($_POST['ajax_limite']) && $_POST['ajax_limite'] == '1') {
    $turma_id = $_POST['turma_id'] ?? '';
    $disciplina_id = $_POST['disciplina_id'] ?? '';
    $bimestre = $_POST['bimestre'] ?? '';
    $limite = $limites[$bimestre] ?? null;
    $ja_utilizado = 0.0;
    $disponivel = null;
    if ($turma_id && $disciplina_id && $bimestre) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ?");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
        $ja_utilizado = (float)$stmt->fetchColumn();
        $disponivel = $limite - $ja_utilizado;
    }
    echo "<!--LIMITE-INFO--><strong>Limite do bimestre:</strong> ".number_format($limite,2,',','.')." pontos<br>";
    echo "<strong>Já utilizado:</strong> ".number_format($ja_utilizado,2,',','.')." pontos<br>";
    echo "<strong>Disponível para novas atividades:</strong> ".number_format($disponivel,2,',','.')." pontos<!--LIMITE-INFO-->";
    exit;
}
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$bimestres = [1,2,3,4];
$tipos_atividade = ['Bimestral' => 'Avaliação Bimestral', 'Semanal' => 'Atividade Semanal'];
$msg = '';
$limite = null;
$ja_utilizado = 0.0;
$disponivel = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_limite'])) {
    $turma_id = $_POST['turma_id'];
    $disciplina_id = $_POST['disciplina_id'];
    $bimestre = $_POST['bimestre'];
    $nome = $_POST['nome'];
    $valor = (float)$_POST['valor'];
    $tipo = $_POST['tipo'] ?? '';
    
    $limite = $limites[$bimestre] ?? null;
    
    // Validação 1: Verifica se já existe uma avaliação bimestral
    if ($tipo === 'Bimestral') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ? AND tipo = 'Bimestral'");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
        $ja_tem_bimestral = $stmt->fetchColumn() > 0;
        
        if ($ja_tem_bimestral) {
            $msg = "Erro: Já existe uma Avaliação Bimestral para este bimestre/disciplina/turma!";
        } elseif ($valor != $limite) {
            $msg = "Erro: A Avaliação Bimestral deve valer exatamente $limite pontos (limite do bimestre)!";
        }
    }
    
    // Validação 2: Soma das semanais não pode ultrapassar o valor da bimestral
    if (empty($msg) && $tipo === 'Semanal') {
        // Busca valor da bimestral
        $stmt = $conn->prepare("SELECT valor FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ? AND tipo = 'Bimestral'");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
        $valor_bimestral = (float)$stmt->fetchColumn();
        
        if ($valor_bimestral > 0) {
            // Soma das semanais já cadastradas
            $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ? AND tipo = 'Semanal'");
            $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
            $soma_semanais = (float)$stmt->fetchColumn();
            
            if (($soma_semanais + $valor) > $valor_bimestral) {
                $disponivel_semanal = $valor_bimestral - $soma_semanais;
                $msg = "Erro: A soma das atividades semanais não pode ultrapassar o valor da bimestral ($valor_bimestral pontos)! Já utilizado: $soma_semanais. Disponível: $disponivel_semanal.";
            }
        }
    }
    
    // Validação 3: Soma total não pode ultrapassar o limite do bimestre
    if (empty($msg)) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ?");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
        $ja_utilizado = (float)$stmt->fetchColumn();
        $disponivel = $limite - $ja_utilizado;
        
        if (($ja_utilizado + $valor) > $limite) {
            $msg = "Erro: A soma das atividades para este bimestre não pode ultrapassar $limite pontos! Já utilizado: $ja_utilizado. Disponível: $disponivel.";
        }
    }
    
    // Validação 4: Verifica se tem pelo menos 2 atividades (após inserção)
    // Esta validação é informativa, não bloqueia o cadastro
    
    // Se passou todas as validações, insere
    if (empty($msg)) {
        try {
            $stmt = $conn->prepare("INSERT INTO atividades (turma_id, disciplina_id, bimestre, nome, valor, tipo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$turma_id, $disciplina_id, $bimestre, $nome, $valor, $tipo]);
            $msg = "Atividade cadastrada com sucesso!";
            $ja_utilizado += $valor;
            $disponivel = $limite - $ja_utilizado;
            
            // Verifica quantidade de atividades cadastradas
            $stmt = $conn->prepare("SELECT COUNT(*) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ?");
            $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
            $qtd_atividades = $stmt->fetchColumn();
            
            if ($qtd_atividades < 2) {
                $msg .= " <strong>Atenção:</strong> Esta disciplina/bimestre precisa de pelo menos 2 atividades avaliativas.";
            }
        } catch (PDOException $e) {
            $msg = '<span style="color:#ff3c3c;">Erro ao salvar no banco: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
    }
}
// Exibe valores ao selecionar turma/disciplina/bimestre
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['turma_id'], $_GET['disciplina_id'], $_GET['bimestre']))) {
    $turma_id = $_POST['turma_id'] ?? $_GET['turma_id'];
    $disciplina_id = $_POST['disciplina_id'] ?? $_GET['disciplina_id'];
    $bimestre = $_POST['bimestre'] ?? $_GET['bimestre'];
    $limite = $limites[$bimestre] ?? null;
    if ($turma_id && $disciplina_id && $bimestre) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ?");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
        $ja_utilizado = (float)$stmt->fetchColumn();
        $disponivel = $limite - $ja_utilizado;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Atividades Avaliativas - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 600px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display:block; margin-top:16px; font-weight:700; }
        input, select { width:100%; padding:8px; border-radius:8px; border:none; margin-top:4px; font-size:1em; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 24px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 24px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .msg { background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;text-align:center; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1>Cadastro de Atividades Avaliativas</h1>
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
    <div id="limite-info" style="background:#22334a;color:#ffff1c;padding:10px;border-radius:8px;margin-bottom:12px;font-weight:700;display:none;"></div>
    <!-- Bloco PHP removido do HTML -->
    <label for="turma_id">Turma:</label>
        <select name="turma_id" id="turma_id" required>
            <option value="">Selecione</option>
            <?php foreach ($turmas as $turma): ?>
                <option value="<?= $turma['id'] ?>"
                <?= (isset($_POST['turma_id']) && $_POST['turma_id'] == $turma['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turma['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="disciplina_id">Disciplina:</label>
        <select name="disciplina_id" id="disciplina_id" required>
            <option value="">Selecione</option>
            <?php foreach ($disciplinas as $disc): ?>
                <option value="<?= $disc['id'] ?>"
                <?= (isset($_POST['disciplina_id']) && $_POST['disciplina_id'] == $disc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($disc['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="bimestre">Bimestre:</label>
        <select name="bimestre" id="bimestre" required>
            <option value="">Selecione</option>
            <?php foreach ($bimestres as $b): ?>
                <option value="<?= $b ?>"
                <?= (isset($_POST['bimestre']) && $_POST['bimestre'] == $b) ? 'selected' : '' ?>><?= $b ?>º Bimestre</option>
            <?php endforeach; ?>
        </select>
        <label for="nome">Nome da Atividade:</label>
        <input type="text" name="nome" id="nome" required>
        <label for="tipo">Tipo de Atividade:</label>
        <select name="tipo" id="tipo" required>
            <option value="">Selecione</option>
            <?php foreach ($tipos_atividade as $key => $label): ?>
                <option value="<?= $key ?>"
                <?= (isset($_POST['tipo']) && $_POST['tipo'] == $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="valor">Valor Máximo:</label>
        <input type="number" name="valor" id="valor" min="0" step="0.01" required>
        <button type="submit" class="btn">Cadastrar Atividade</button>
    </form>
    <script>
    function fetchLimite() {
        var turma = document.getElementById('turma_id').value;
        var disciplina = document.getElementById('disciplina_id').value;
        var bimestre = document.getElementById('bimestre').value;
        var info = document.getElementById('limite-info');
        if (turma && disciplina && bimestre) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'atividades.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var res = xhr.responseText;
                    var match = res.match(/<!--LIMITE-INFO-->([\s\S]*?)<!--LIMITE-INFO-->/);
                    if (match) {
                        info.innerHTML = match[1];
                        info.style.display = 'block';
                    }
                }
            };
            xhr.send('ajax_limite=1&turma_id='+turma+'&disciplina_id='+disciplina+'&bimestre='+bimestre);
        } else {
            info.style.display = 'none';
        }
    }
    document.getElementById('turma_id').addEventListener('change', fetchLimite);
    document.getElementById('disciplina_id').addEventListener('change', fetchLimite);
    document.getElementById('bimestre').addEventListener('change', fetchLimite);
    window.onload = fetchLimite;
    </script>
<?php
// AJAX para exibir limites dinamicamente
if (isset($_POST['ajax_limite']) && $_POST['ajax_limite'] == '1') {
    $turma_id = $_POST['turma_id'] ?? '';
    $disciplina_id = $_POST['disciplina_id'] ?? '';
    $bimestre = $_POST['bimestre'] ?? '';
    $limite = $limites[$bimestre] ?? null;
    $ja_utilizado = 0.0;
    $disponivel = null;
    if ($turma_id && $disciplina_id && $bimestre) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ?");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre]);
        $ja_utilizado = (float)$stmt->fetchColumn();
        $disponivel = $limite - $ja_utilizado;
    }
    echo "<!--LIMITE-INFO--><strong>Limite do bimestre:</strong> ".number_format($limite,2,',','.')." pontos<br>";
    echo "<strong>Já utilizado:</strong> ".number_format($ja_utilizado,2,',','.')." pontos<br>";
    echo "<strong>Disponível para novas atividades:</strong> ".number_format($disponivel,2,',','.')." pontos<!--LIMITE-INFO-->";
    exit;
}
?>
</div>
</body>
</html>
