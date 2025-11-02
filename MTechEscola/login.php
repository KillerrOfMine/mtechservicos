<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #0f2027, #2c5364 80%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Orbitron', Arial, sans-serif;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: rgba(20, 30, 50, 0.85);
            border-radius: 24px;
            box-shadow: 0 8px 32px #000a;
            padding: 48px 32px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        h1 {
            font-size: 2em;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 24px;
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 12px;
            margin: 12px 0;
            border-radius: 8px;
            border: none;
            font-size: 1em;
        }
        .btn {
            background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%);
            color: #222;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 12px 32px;
            font-size: 1.1em;
            cursor: pointer;
            box-shadow: 0 2px 8px #0005;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            transform: scale(1.07);
            box-shadow: 0 4px 16px #00c3ff55;
        }
        .footer {
            margin-top: 32px;
            font-size: 0.9em;
            color: #b0bec5;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login MTech Escola</h1>
        <form method="post" action="autenticar.php">
            <input type="text" name="usuario" placeholder="Usuário" required><br>
            <div style="position:relative; display:inline-block; width:90%;">
                <input type="password" name="senha" id="senha" placeholder="Senha" required style="width:100%;">
                <span onclick="toggleSenha()" style="position:absolute; right:10px; top:14px; cursor:pointer; color:#00c3ff; font-size:1.1em;">👁️</span>
            </div><br>
            <button type="submit" class="btn">Entrar</button>
        </form>
        <div class="footer">&copy; 2025 MTech Escola</div>
    </div>
    <script>
    function toggleSenha() {
        var senhaInput = document.getElementById('senha');
        if (senhaInput.type === 'password') {
            senhaInput.type = 'text';
        } else {
            senhaInput.type = 'password';
        }
    }
    </script>
</body>
</html>
