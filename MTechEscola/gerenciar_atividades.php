<?php
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// Exclusão de atividade
$msg = '';
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM atividades WHERE id = ?");
    if ($stmt->execute([$id])) {
        $msg = '<span style="color:#00c3ff;font-weight:700;">Atividade excluída com sucesso!</span>';
    } else {
        $msg = '<span style="color:#ff3c3c;font-weight:700;">Erro ao excluir atividade.</span>';
    }
}
$turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$bimestres = [1,2,3,4];
// Filtros
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$bimestre = $_GET['bimestre'] ?? '';
// Busca atividades
$sql = "SELECT a.id, a.nome, a.valor, a.tipo, t.nome AS turma, d.nome AS disciplina, a.bimestre FROM atividades a JOIN turmas t ON a.turma_id = t.id JOIN disciplinas d ON a.disciplina_id = d.id WHERE 1=1";
$params = [];
if ($turma_id) { $sql .= " AND a.turma_id = ?"; $params[] = $turma_id; }
if ($disciplina_id) { $sql .= " AND a.disciplina_id = ?"; $params[] = $disciplina_id; }
if ($bimestre) { $sql .= " AND a.bimestre = ?"; $params[] = $bimestre; }
$sql .= " ORDER BY a.bimestre, t.nome, d.nome, a.tipo DESC, a.nome";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Atividades - MTech Escola</title>
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
        .filter { margin-bottom: 16px; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1>Gerenciar Atividades</h1>
    <?php if ($msg): ?><div style="margin-bottom:16px;"><?= $msg ?></div><?php endif; ?>
    <form method="get" class="filter">
        <label for="turma_id">Turma:</label>
        <select name="turma_id" id="turma_id">
            <option value="">Todas</option>
            <?php foreach ($turmas as $turma): ?>
                <option value="<?= $turma['id'] ?>" <?= ($turma_id == $turma['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turma['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="disciplina_id">Disciplina:</label>
        <select name="disciplina_id" id="disciplina_id">
            <option value="">Todas</option>
            <?php foreach ($disciplinas as $disc): ?>
                <option value="<?= $disc['id'] ?>" <?= ($disciplina_id == $disc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($disc['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="bimestre">Bimestre:</label>
        <select name="bimestre" id="bimestre">
            <option value="">Todos</option>
            <?php foreach ($bimestres as $b): ?>
                <option value="<?= $b ?>" <?= ($bimestre == $b) ? 'selected' : '' ?>><?= $b ?>º Bimestre</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Filtrar</button>
    </form>
    <table>
        <tr>
            <th>Turma</th>
            <th>Disciplina</th>
            <th>Bimestre</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Ações</th>
        </tr>
        <?php if (!$atividades): ?>
            <tr><td colspan="7" style="text-align:center; color:#ff3c3c; font-weight:700;">Nenhuma atividade encontrada.</td></tr>
        <?php else: foreach ($atividades as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['turma']) ?></td>
                <td><?= htmlspecialchars($a['disciplina']) ?></td>
                <td><?= $a['bimestre'] ?>º</td>
                <td><?= htmlspecialchars($a['nome']) ?></td>
                <td><?= $a['tipo'] === 'Bimestral' ? 'Avaliação Bimestral' : 'Atividade Semanal' ?></td>
                <td><?= number_format($a['valor'],2,',','.') ?></td>
                <td>
                  <a href="editar_atividade.php?id=<?= $a['id'] ?>" class="btn">Editar</a>
                  <a href="gerenciar_atividades.php?excluir=<?= $a['id'] ?>" class="btn" style="background:linear-gradient(90deg,#ff3c3c 40%,#ffff1c 100%);color:#fff;" onclick="return confirm('Confirma a exclusão desta atividade?');">Excluir</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </table>
</div>
</body>
</html>
