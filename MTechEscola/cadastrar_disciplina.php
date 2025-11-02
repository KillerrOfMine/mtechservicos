<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Disciplina - MTech Escola</title>
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
    <div class="container">
        <h1>Cadastrar Disciplina</h1>
        <?php
        require_once 'db_connect_horarios.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = $_POST['nome'];
            $sql = "INSERT INTO disciplinas (nome) VALUES (?)";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$nome])) {
                echo '<p>Disciplina cadastrada com sucesso!</p>';
                echo '<a href="disciplinas.php" class="btn">Ver Disciplinas</a>';
                exit;
            } else {
                echo '<p>Erro ao cadastrar disciplina.</p>';
            }
        }
        ?>
        <form method="post">
            <label for="nome">Nome da Disciplina:</label>
            <input type="text" id="nome" name="nome" required>
            <button type="submit" class="btn">Cadastrar</button>
            <a href="disciplinas.php" class="btn">Cancelar</a>
        </form>
    </div>
</body>
</html>
