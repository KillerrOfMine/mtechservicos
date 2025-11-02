<?php
require_once '../db_connect_horarios.php';
session_start();
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $senha = $_POST['senha'];
    $sql = "SELECT id, nome, senha FROM professores WHERE login = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$login]);
    $prof = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($prof && password_verify($senha, $prof['senha'])) {
        $_SESSION['professor_id'] = $prof['id'];
        $_SESSION['professor_nome'] = $prof['nome'];
        header('Location: home.php');
        exit;
    } else {
        if (!$prof) {
            $erro = 'Login não encontrado!';
        } else {
            $erro = 'Senha incorreta!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login do Professor - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 400px; margin: 60px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border-radius: 8px; border: none; margin-bottom: 18px; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 10px 32px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; text-decoration: none; display: inline-block; width: 100%; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .error { color: #ff3c3c; font-weight: 700; margin-bottom: 16px; text-align: center; background: rgba(255, 60, 60, 0.1); padding: 10px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login do Professor</h1>
        <?php if ($erro): ?>
            <div class="error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="login">Login:</label>
            <input type="text" id="login" name="login" required>
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</body>
</html>
