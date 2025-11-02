<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Turma - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 500px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input[type="text"] { width: 100%; padding: 10px; border-radius: 8px; border: none; margin-bottom: 18px; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 32px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-right: 8px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>
    <div class="container">
        <h1>Editar Turma</h1>
        <?php
        require_once 'db_connect_horarios.php';
        if (!isset($_GET['id'])) {
            echo '<p>Turma não encontrada.</p>';
            echo '<a href="turmas.php" class="btn">Voltar</a>';
            exit;
        }
        $id = $_GET['id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'];
            $sql = "UPDATE turmas SET nome = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $ok = $stmt->execute([$nome, $id]);
            // Salvar vínculos disciplinas/professores/aulas semanais
            if (isset($_POST['prof_disc'])) {
                // Remove vínculos antigos
                $conn->prepare("DELETE FROM turma_disciplina_professor WHERE turma_id = ?")->execute([$id]);
                foreach ($_POST['prof_disc'] as $disciplina_id => $dados) {
                    $professor_id = $dados['professor_id'] ?? null;
                    $aulas_semana = $dados['aulas_semana'] ?? null;
                    if ($professor_id && $aulas_semana) {
                        $conn->prepare("INSERT INTO turma_disciplina_professor (turma_id, disciplina_id, professor_id, aulas_semana) VALUES (?, ?, ?, ?)")
                            ->execute([$id, $disciplina_id, $professor_id, $aulas_semana]);
                    }
                }
            }
            if ($ok) {
                echo '<p>Turma atualizada com sucesso!</p>';
                echo '<a href="turmas.php" class="btn">Voltar</a>';
                exit;
            } else {
                echo '<p>Erro ao atualizar turma.</p>';
            }
        }
        $sql = "SELECT nome FROM turmas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $turma = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$turma) {
            echo '<p>Turma não encontrada.</p>';
            echo '<a href="turmas.php" class="btn">Voltar</a>';
            exit;
        }
        ?>
        <form method="post">
            <label for="nome">Nome da Turma:</label>
            <input type="text" name="nome" id="nome" required value="<?= htmlspecialchars($turma['nome']) ?>">
            <h2 style="margin-top:32px; color:#ffff1c;">Disciplinas, Professores e Aulas Semanais</h2>
            <table style="width:100%;margin-bottom:24px;border-collapse:collapse;">
                <tr style="background:#22334a;color:#ffff1c;">
                    <th>Disciplina</th>
                    <th>Professor</th>
                    <th>Aulas/semana</th>
                </tr>
                <?php
                // Busca disciplinas e professores
                $disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                $professores = $conn->query("SELECT id, nome FROM professores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                // Busca vínculos atuais
                $stmt = $conn->prepare("SELECT disciplina_id, professor_id, aulas_semana FROM turma_disciplina_professor WHERE turma_id = ?");
                $stmt->execute([$id]);
                $vinculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $vinc_map = [];
                foreach ($vinculos as $v) {
                    $vinc_map[$v['disciplina_id']] = $v;
                }
                foreach ($disciplinas as $disc): ?>
                <tr>
                    <td><?= htmlspecialchars($disc['nome']) ?></td>
                    <td>
                        <select name="prof_disc[<?= $disc['id'] ?>][professor_id]">
                            <option value="">Selecione</option>
                            <?php foreach ($professores as $prof): ?>
                                <option value="<?= $prof['id'] ?>" <?= (isset($vinc_map[$disc['id']]) && $vinc_map[$disc['id']]['professor_id'] == $prof['id']) ? 'selected' : '' ?>><?= htmlspecialchars($prof['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="prof_disc[<?= $disc['id'] ?>][aulas_semana]" min="1" max="10" style="width:80px;" value="<?= isset($vinc_map[$disc['id']]) ? $vinc_map[$disc['id']]['aulas_semana'] : '' ?>">
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <button type="submit" class="btn">Salvar</button>
            <a href="turmas.php" class="btn" style="background:linear-gradient(90deg,#ffff1c 40%,#00c3ff 100%);color:#222;margin-left:8px;">Cancelar</a>
        </form>
    </div>
</body>
</html>
