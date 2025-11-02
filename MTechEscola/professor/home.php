<?php
session_start();
if (!isset($_SESSION['professor_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Home do Professor - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #0f2027, #2c5364); 
            min-height: 100vh; 
            font-family: 'Orbitron', Arial, sans-serif; 
            color: #fff;
            padding-top: 70px;
            padding-bottom: 20px;
        }
        
        /* Header fixo com menu hambúrguer */
        .header-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(10, 15, 25, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .header-nav h1 {
            font-size: 1.2em;
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: #00c3ff;
            font-size: 1.8em;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .menu-lateral {
            position: fixed;
            top: 0;
            right: -300px;
            width: 280px;
            height: 100vh;
            background: rgba(10, 15, 25, 0.98);
            backdrop-filter: blur(10px);
            transition: right 0.3s ease;
            z-index: 2000;
            padding: 20px;
            overflow-y: auto;
        }
        
        .menu-lateral.ativo {
            right: 0;
        }
        
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1500;
        }
        
        .menu-overlay.ativo {
            opacity: 1;
            visibility: visible;
        }
        
        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 195, 255, 0.3);
        }
        
        .menu-header h2 {
            font-size: 1.3em;
            color: #00c3ff;
        }
        
        .menu-close {
            background: none;
            border: none;
            color: #00c3ff;
            font-size: 1.8em;
            cursor: pointer;
            padding: 4px;
        }
        
        .menu-item {
            display: block;
            padding: 14px 16px;
            margin-bottom: 8px;
            background: rgba(0, 195, 255, 0.1);
            border-radius: 10px;
            text-decoration: none;
            color: #fff;
            font-size: 0.95em;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .menu-item:hover {
            background: rgba(0, 195, 255, 0.2);
            border-color: #00c3ff;
            transform: translateX(5px);
        }
        
        /* Container */
        .container { 
            padding: 20px 16px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Welcome Section */
        .welcome {
            background: rgba(20, 30, 50, 0.7);
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }
        .welcome h1 { 
            font-size: 1.5em; 
            font-weight: 700; 
            margin-bottom: 8px;
            background: linear-gradient(90deg, #00c3ff, #ffff1c); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
        }
        .welcome p { 
            font-size: 0.9em; 
            color: #cfd8dc; 
            line-height: 1.4;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .card {
            background: rgba(20, 30, 50, 0.8);
            border-radius: 16px;
            padding: 20px 16px;
            text-align: center;
            text-decoration: none;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .card:active {
            transform: scale(0.95);
            border-color: #00c3ff;
        }
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 8px;
        }
        .card-title {
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .card-desc {
            font-size: 0.7em;
            color: #b0bec5;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            font-size: 0.8em;
            color: #b0bec5;
            margin-top: 24px;
        }
        
        /* Desktop View */
        @media (min-width: 768px) {
            .container { padding: 32px 24px; }
            .welcome h1 { font-size: 2em; }
            .welcome p { font-size: 1.1em; }
            .cards-grid { grid-template-columns: repeat(3, 1fr); gap: 16px; }
            .card-icon { font-size: 3em; }
            .card-title { font-size: 1em; }
            .card-desc { font-size: 0.8em; }
        }
    </style>
</head>
<body>
<!-- Header com menu hambúrguer -->
<div class="header-nav">
    <h1>MTech Escola - Professor</h1>
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
</div>

<!-- Overlay do menu -->
<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

<!-- Menu lateral -->
<div class="menu-lateral" id="menuLateral">
    <div class="menu-header">
        <h2>Menu</h2>
        <button class="menu-close" onclick="toggleMenu()">✕</button>
    </div>
    
    <a href="home.php" class="menu-item">🏠 Início</a>
    <a href="horario.php" class="menu-item">📅 Meu Horário</a>
    <a href="../presenca.php" class="menu-item">📋 Frequência</a>
    <a href="diario.php" class="menu-item">📖 Diário</a>
    <a href="notas.php" class="menu-item">📊 Notas</a>
    <a href="atividades.php" class="menu-item">📝 Atividades</a>
    <a href="login.php" class="menu-item">🚪 Sair</a>
</div>
    
<div class="container">
    <div class="welcome">
        <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['professor_nome']); ?>!</h1>
        <p>Gerencie suas turmas e atividades de forma prática e rápida</p>
    </div>
        
        <div class="cards-grid">
            <a href="horario.php" class="card">
                <div class="card-icon">📅</div>
                <div class="card-title">Horários</div>
                <div class="card-desc">Minhas turmas</div>
            </a>
            
            <a href="../presenca.php" class="card">
                <div class="card-icon">✓</div>
                <div class="card-title">Frequência</div>
                <div class="card-desc">Chamada</div>
            </a>
            
            <a href="notas.php" class="card">
                <div class="card-icon">📝</div>
                <div class="card-title">Notas</div>
                <div class="card-desc">Lançar notas</div>
            </a>
            
            <a href="atividades.php" class="card">
                <div class="card-icon">📋</div>
                <div class="card-title">Atividades</div>
                <div class="card-desc">Avaliações</div>
            </a>
            
            <a href="diario.php" class="card">
                <div class="card-icon">📚</div>
                <div class="card-title">Diário</div>
                <div class="card-desc">Conteúdo</div>
            </a>
            
            <a href="../folha_chamada.php" class="card">
                <div class="card-icon">📄</div>
                <div class="card-title">Folha</div>
                <div class="card-desc">Imprimir</div>
            </a>
        </div>
        
        <div class="footer">&copy; 2025 MTech Escola</div>
    </div>
    
    <script>
        // Controle do menu hambúrguer
        function toggleMenu() {
            const menu = document.getElementById('menuLateral');
            const overlay = document.getElementById('menuOverlay');
            menu.classList.toggle('ativo');
            overlay.classList.toggle('ativo');
        }
    </script>
</body>
</html>
