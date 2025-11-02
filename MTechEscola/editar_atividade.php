<?php
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$limites = [1 => 10.0, 2 => 20.0, 3 => 30.0, 4 => 40.0];
$tipos_atividade = ['Bimestral' => 'Avaliação Bimestral', 'Semanal' => 'Atividade Semanal'];

$id = $_GET['id'] ?? '';
if (!$id) { echo 'ID inválido.'; exit; }
// Busca atividade
$stmt = $conn->prepare("SELECT * FROM atividades WHERE id = ?");
$stmt->execute([$id]);
$atividade = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$atividade) { echo 'Atividade não encontrada.'; exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $valor = (float)$_POST['valor'];
    $tipo = $_POST['tipo'] ?? '';
    
    $turma_id = $atividade['turma_id'];
    $disciplina_id = $atividade['disciplina_id'];
    $bimestre = $atividade['bimestre'];
    $limite = $limites[$bimestre] ?? null;
    
    // Validação 1: Verifica se já existe outra avaliação bimestral (exceto a atual)
    if ($tipo === 'Bimestral') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ? AND tipo = 'Bimestral' AND id != ?");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre, $id]);
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
            // Soma das semanais já cadastradas (exceto a atual)
            $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ? AND tipo = 'Semanal' AND id != ?");
            $stmt->execute([$turma_id, $disciplina_id, $bimestre, $id]);
            $soma_semanais = (float)$stmt->fetchColumn();
            
            if (($soma_semanais + $valor) > $valor_bimestral) {
                $disponivel_semanal = $valor_bimestral - $soma_semanais;
                $msg = "Erro: A soma das atividades semanais não pode ultrapassar o valor da bimestral ($valor_bimestral pontos)! Já utilizado: $soma_semanais. Disponível: $disponivel_semanal.";
            }
        }
    }
    
    // Validação 3: Soma total não pode ultrapassar o limite do bimestre
    if (empty($msg)) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM atividades WHERE turma_id = ? AND disciplina_id = ? AND bimestre = ? AND id != ?");
        $stmt->execute([$turma_id, $disciplina_id, $bimestre, $id]);
        $ja_utilizado = (float)$stmt->fetchColumn();
        $disponivel = $limite - $ja_utilizado;
        
        if (($ja_utilizado + $valor) > $limite) {
            $msg = "Erro: A soma das atividades para este bimestre não pode ultrapassar $limite pontos! Já utilizado: $ja_utilizado. Disponível: $disponivel.";
        }
    }
    
    // Se passou todas as validações, atualiza
    if (empty($msg)) {
        try {
            $stmt = $conn->prepare("UPDATE atividades SET nome = ?, valor = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$nome, $valor, $tipo, $id]);
            $msg = 'Atividade atualizada com sucesso!';
            // Atualiza dados
            $atividade['nome'] = $nome;
            $atividade['valor'] = $valor;
            $atividade['tipo'] = $tipo;
        } catch (PDOException $e) {
            $msg = '<span style="color:#ff3c3c;">Erro ao salvar: ' . htmlspecialchars($e->getMessage()) . '</span>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Atividade - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 500px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display:block; margin-top:16px; font-weight:700; }
        input, select { width:100%; padding:8px; border-radius:8px; border:none; margin-top:4px; font-size:1em; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 24px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 24px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .msg { background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;text-align:center; }
    </style>
</head>
<body>
<div class="container">
    <h1>Editar Atividade</h1>
    <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
    <form method="post">
        <label for="nome">Nome da Atividade:</label>
        <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($atividade['nome']) ?>" required>
        <label for="tipo">Tipo de Atividade:</label>
        <select name="tipo" id="tipo" required>
            <option value="">Selecione</option>
            <?php foreach ($tipos_atividade as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($atividade['tipo'] == $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="valor">Valor Máximo:</label>
        <input type="number" name="valor" id="valor" min="0" step="0.01" value="<?= htmlspecialchars($atividade['valor']) ?>" required>
        <button type="submit" class="btn">Salvar Alterações</button>
        <a href="gerenciar_atividades.php" class="btn">Voltar</a>
    </form>
</div>
</body>
</html>
