<?php
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// Busca turmas
$turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
// Busca atividades filtradas pela turma
$atividades = [];
if (isset($_GET['turma_id'])) {
    $stmt = $conn->prepare("SELECT a.id, a.nome FROM atividades a WHERE a.turma_id = ? ORDER BY a.nome");
    $stmt->execute([$_GET['turma_id']]);
    $atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Inicializa alunos
$alunos = [];
if (isset($_GET['turma_id']) && isset($_GET['atividade_id'])) {
    $stmt = $conn->prepare("SELECT id, nome FROM alunos WHERE turma_id = ? ORDER BY nome");
    $stmt->execute([$_GET['turma_id']]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$msg = '';
// Backend para salvar notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['turma_id'], $_GET['atividade_id'])) {
    $atividade_id = $_GET['atividade_id'];
    $notas = $_POST['notas'] ?? [];
    $salvos = 0;
    foreach ($notas as $aluno_id => $nota) {
        $stmt = $conn->prepare("INSERT INTO notas (aluno_id, atividade_id, nota) VALUES (?, ?, ?) ON CONFLICT (aluno_id, atividade_id) DO UPDATE SET nota = EXCLUDED.nota");
        $stmt->execute([$aluno_id, $atividade_id, $nota]);
        $salvos++;
    }
    $msg = "$salvos notas salvas com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançamento de Notas - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 900px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 12px; border-bottom: 1px solid #2c5364; text-align: left; }
        th { background: #1a2636; color: #ffff1c; }
        tr:hover { background: #22334a; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 8px 24px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-right: 8px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .selects { margin-bottom: 24px; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1>Lançamento de Notas</h1>
    <?php if ($msg): ?>
        <div style="background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;text-align:center;">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    <form method="get" class="selects">
        <label for="turma_id">Turma:</label>
        <select name="turma_id" id="turma_id" required onchange="this.form.submit()">
            <option value="">Selecione</option>
            <?php foreach ($turmas as $turma): ?>
                <option value="<?= $turma['id'] ?>" <?= (isset($_GET['turma_id']) && $_GET['turma_id'] == $turma['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turma['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="atividade_id">Atividade:</label>
        <select name="atividade_id" id="atividade_id" required onchange="this.form.submit()">
            <option value="">Selecione</option>
            <?php foreach ($atividades as $atv): ?>
                <option value="<?= $atv['id'] ?>" <?= (isset($_GET['atividade_id']) && $_GET['atividade_id'] == $atv['id']) ? 'selected' : '' ?>><?= htmlspecialchars($atv['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($alunos && isset($_GET['atividade_id'])): ?>
        <?php
        // Busca o valor máximo da atividade selecionada
        $stmt = $conn->prepare("SELECT valor FROM atividades WHERE id = ? LIMIT 1");
        $stmt->execute([$_GET['atividade_id']]);
        $valor_max = $stmt->fetchColumn();
        ?>
    <form method="post" action="notas.php?turma_id=<?= $_GET['turma_id'] ?>&atividade_id=<?= $_GET['atividade_id'] ?>">
        <table>
            <tr>
                <th>Aluno</th>
                <th>Nota (máx: <?= htmlspecialchars($valor_max) ?>)</th>
            </tr>
            <?php foreach ($alunos as $aluno): ?>
            <tr>
                <td><?= htmlspecialchars($aluno['nome']) ?></td>
                <td><input type="number" name="notas[<?= $aluno['id'] ?>]" min="0" max="<?= htmlspecialchars($valor_max) ?>" step="0.01" required></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit" class="btn">Salvar Notas</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
