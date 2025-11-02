<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>MTech Escola - Início</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .header { position: fixed; top: 0; left: 0; right: 0; height: 64px; background: rgba(20,30,50,0.95); box-shadow: 0 2px 12px #0005; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; z-index: 1000; }
        .header-logo { font-size: 1.5em; font-weight: 700; letter-spacing: 2px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 0 8px #00c3ff, 0 0 16px #ffff1c; }
        .header-link { color: #fff; font-size: 1em; text-decoration: none; font-weight: 500; margin-left: 24px; transition: color 0.2s; }
        .header-link:hover { color: #00c3ff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 48px 32px; max-width: 520px; width: 100%; text-align: center; margin: 100px auto 0 auto; }
        h1 { font-size: 2.6em; font-weight: 700; letter-spacing: 2px; margin-bottom: 16px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        p { font-size: 1.2em; color: #cfd8dc; margin-bottom: 32px; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 16px 40px; font-size: 1.1em; cursor: pointer; box-shadow: 0 2px 8px #0005; transition: transform 0.2s, box-shadow 0.2s; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .footer { margin-top: 32px; font-size: 0.9em; color: #b0bec5; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Bem-vindo ao MTech Escola</h1>
        <p>Gerencie professores, horários, turmas e muito mais.<br>Escolha uma opção no menu acima para começar.</p>
        <div class="footer">&copy; 2025 MTech Escola</div>
    </div>
</body>
</html>
