<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Professor - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 500px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; border-radius: 8px; border: none; margin-bottom: 18px; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 32px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-right: 8px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .disciplinas-list { margin-bottom: 18px; }
        .disciplinas-list label { display: inline-block; margin-right: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastrar Professor</h1>
        <?php
        require_once 'db_connect_horarios.php';
        // Busca disciplinas
        $disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome");
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'];
            $login = $_POST['login'];
                $senha_raw = $_POST['senha'];
                $email = isset($_POST['email']) ? $_POST['email'] : null;
                if (strlen($senha_raw) < 6) {
                    echo '<p style="color:#ff3c3c;font-weight:700;">A senha deve ter pelo menos 6 caracteres.</p>';
                } else {
                    $senha = password_hash($senha_raw, PASSWORD_DEFAULT);
            $disciplinas_ids = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];
            $alterar_senha = !empty($_POST['alterar_senha_proximo_login']) ? true : false;
            try {
                $sql = "INSERT INTO professores (nome, email, login, senha, alterar_senha_proximo_login) VALUES (?, ?, ?, ?, ?) RETURNING id";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute([$nome, $email, $login, $senha, $alterar_senha])) {
                    $prof_id = $stmt->fetchColumn();
                    // Vincula disciplinas
                    foreach ($disciplinas_ids as $disc_id) {
                        $sql_vinc = "INSERT INTO professores_disciplinas (professor_id, disciplina_id) VALUES (?, ?)";
                        $stmt_vinc = $conn->prepare($sql_vinc);
                        $stmt_vinc->execute([$prof_id, $disc_id]);
                    }
                    echo '<p style="color:#00ff99;font-weight:700;font-size:1.2em;">Professor cadastrado com sucesso! Redirecionando...</p>';
                    echo '<script>setTimeout(function(){ window.location.href = "professores.php"; }, 1200);</script>';
                    exit;
                } else {
                    echo '<p style="color:#ff3c3c;font-weight:700;">Erro ao cadastrar professor.</p>';
                }
            } catch (PDOException $e) {
                echo '<p style="color:#ff3c3c;font-weight:700;">Erro ao cadastrar professor: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
                }
        }
        ?>
        <form method="post">
            <label for="nome">Nome do Professor:</label>
            <input type="text" id="nome" name="nome" required>
            <label for="login">Login:</label>
            <input type="text" id="login" name="login" required>
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required minlength="6" pattern=".{6,}" title="A senha deve ter pelo menos 6 caracteres" style="width:100%;padding:10px;border-radius:8px;border:none;margin-bottom:18px;background:#eaf2fb;color:#222;font-size:1em;">
            <span id="senha-erro" style="color:#ff3c3c;font-weight:700;display:none;margin-bottom:8px;">A senha deve ter pelo menos 6 caracteres.</span>
            <label><input type="checkbox" name="alterar_senha_proximo_login" value="1"> Alterar senha no próximo login</label>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var senhaInput = document.getElementById('senha');
            var erroSpan = document.getElementById('senha-erro');
            senhaInput.addEventListener('input', function() {
                if (senhaInput.value.length > 0 && senhaInput.value.length < 6) {
                    erroSpan.style.display = 'inline';
                } else {
                    erroSpan.style.display = 'none';
                }
            });
        });
        </script>
            <div class="disciplinas-list">
                <label>Disciplinas:</label><br>
                <?php while ($d = $disciplinas->fetch(PDO::FETCH_ASSOC)) {
                    echo '<label><input type="checkbox" name="disciplinas[]" value="' . $d['id'] . '"> ' . htmlspecialchars($d['nome']) . '</label>';
                } ?>
            </div>
            <button type="submit" class="btn">Cadastrar</button>
            <a href="professores.php" class="btn">Cancelar</a>
        </form>
    </div>
</body>
</html>
