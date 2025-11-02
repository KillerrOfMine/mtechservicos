<?php
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// Busca turmas, disciplinas e aulas
$turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turma_id = $_POST['turma_id'];
    $disciplina_id = $_POST['disciplina_id'];
    $data_aula = $_POST['data_aula'];
    $conteudo = $_POST['conteudo'];
    $stmt = $conn->prepare("INSERT INTO conteudos_aula (turma_id, disciplina_id, data_aula, conteudo, usuario_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$turma_id, $disciplina_id, $data_aula, $conteudo, $_SESSION['usuario_id']]);
    $msg = 'Conteúdo cadastrado com sucesso!';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Conteúdo por Aula - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; padding-top: 80px; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 600px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display:block; margin-top:16px; font-weight:700; }
        input, select, textarea { width:100%; padding:8px; border-radius:8px; border:none; margin-top:4px; font-size:1em; }
        textarea { min-height:100px; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 24px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 24px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .msg { background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;text-align:center; }
    </style>
</head>
<body>
<?php require_once 'includes/header.php'; ?>
<div class="container">
    <h1>Cadastrar Conteúdo por Aula</h1>
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="turma_id">Turma:</label>
        <select name="turma_id" id="turma_id" required>
            <option value="">Selecione</option>
            <?php foreach ($turmas as $turma): ?>
                <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="disciplina_id">Disciplina:</label>
        <select name="disciplina_id" id="disciplina_id" required>
            <option value="">Selecione</option>
            <?php foreach ($disciplinas as $disc): ?>
                <option value="<?= $disc['id'] ?>"><?= htmlspecialchars($disc['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="data_aula">Data da Aula:</label>
        <input type="date" name="data_aula" id="data_aula" required>
        <label for="conteudo">Conteúdo:</label>
        <textarea name="conteudo" id="conteudo" required></textarea>
        <button type="submit" class="btn">Cadastrar Conteúdo</button>
    </form>
</div>
</body>
</html>
