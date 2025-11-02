<?php
session_start();
require_once 'includes/header.php';
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// Busca disciplinas e professores
$disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$professores = $conn->query("SELECT id, nome FROM professores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $stmt = $conn->prepare("INSERT INTO turmas (nome) VALUES (?) RETURNING id");
    $stmt->execute([$nome]);
    $turma_id = $stmt->fetchColumn();
    if ($turma_id) {
        if (isset($_POST['prof_disc'])) {
            foreach ($_POST['prof_disc'] as $disciplina_id => $dados) {
                $professor_id = $dados['professor_id'] ?? null;
                $aulas_semana = $dados['aulas_semana'] ?? null;
                
                // Só insere se professor E aulas foram preenchidos
                if (!empty($professor_id) && !empty($aulas_semana)) {
                    $stmt2 = $conn->prepare("INSERT INTO turma_disciplina_professor (turma_id, disciplina_id, professor_id, aulas_semana) VALUES (?, ?, ?, ?)");
                    $stmt2->execute([$turma_id, $disciplina_id, $professor_id, $aulas_semana]);
                }
            }
        }
        $msg = 'Turma cadastrada com sucesso!';
    } else {
        $msg = 'Erro ao cadastrar turma.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Turma - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 700px; margin: 40px auto; }
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
    <h1>Cadastrar Turma</h1>
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="nome">Nome da Turma:</label>
        <input type="text" name="nome" id="nome">
        <h2 style="margin-top:32px; color:#ffff1c;">Disciplinas, Professores e Aulas Semanais</h2>
        <table style="width:100%;margin-bottom:24px;border-collapse:collapse;">
            <tr style="background:#22334a;color:#ffff1c;">
                <th>Disciplina</th>
                <th>Professor</th>
                <th>Aulas/semana</th>
            </tr>
            <?php foreach ($disciplinas as $disc): ?>
            <tr>
                <td><?= htmlspecialchars($disc['nome']) ?></td>
                <td>
                    <select name="prof_disc[<?= $disc['id'] ?>][professor_id]">
                        <option value="">Selecione</option>
                        <?php foreach ($professores as $prof): ?>
                            <option value="<?= $prof['id'] ?>"><?= htmlspecialchars($prof['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" name="prof_disc[<?= $disc['id'] ?>][aulas_semana]" min="1" max="10" style="width:80px;">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button type="submit" class="btn">Cadastrar Turma</button>
        <a href="turmas.php" class="btn" style="background:linear-gradient(90deg,#ffff1c 40%,#00c3ff 100%);color:#222;margin-left:8px;">Voltar</a>
    </form>
</div>
</body>
</html>
