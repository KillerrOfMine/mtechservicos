<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>ERP MTECH - Portal</title>
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
        .header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 64px;
            background: rgba(20,30,50,0.95);
            box-shadow: 0 2px 12px #0005;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            z-index: 1000;
        }
        .header-logo {
            font-size: 1.5em;
            font-weight: 700;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 8px #00c3ff, 0 0 16px #ffff1c;
        }
        .header-link {
            color: #fff;
            font-size: 1em;
            text-decoration: none;
            font-weight: 500;
            margin-left: 24px;
            transition: color 0.2s;
        }
        .header-link:hover {
            color: #00c3ff;
        }
        .container {
            background: rgba(20, 30, 50, 0.85);
            border-radius: 24px;
            box-shadow: 0 8px 32px #000a;
            padding: 48px 32px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            margin-top: 80px;
        }
        h1 {
            font-size: 2.6em;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 16px;
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 1.2em;
            color: #cfd8dc;
            margin-bottom: 32px;
        }
        .btn {
            background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%);
            color: #222;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            padding: 16px 40px;
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
        .glow {
            text-shadow: 0 0 8px #00c3ff, 0 0 16px #ffff1c;
        }
        .footer {
            margin-top: 32px;
            font-size: 0.9em;
            color: #b0bec5;
        }
    </style>
</head>
<body>
    <div class="header">
        <span class="header-logo">ERP MTECH</span>
        <nav>
            <a href="erp/login.php" class="header-link">Login ERP</a>
            <a href="MTechEscola/login.php" class="header-link">Login MTech Escola</a>
            <a href="MTechEscola/professor/login.php" class="header-link">Login Professor</a>
        </nav>
    </div>
    <div class="container">
        <h1 class="glow">ERP MTECH</h1>
        <p>Bem-vindo ao portal futurista do sistema de gestão escolar.<br>Gerencie horários, turmas, professores e muito mais com tecnologia de ponta.</p>
        <a href="erp/login.php" class="btn">Acessar ERP</a>
        <div class="footer">&copy; 2025 MTECH Serviços - Todos os direitos reservados</div>
    </div>
</body>
</html>
