<?php
require_once 'config.php';
require_once 'classes/GoogleAuth.php';
require_once 'classes/ThemeManager.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$auth = new GoogleAuth();
$user = $auth->getCurrentUser();
$themeManager = new ThemeManager($_SESSION['user_id']);
$theme = $themeManager->getTheme();
$presets = ThemeManager::getPresets();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - <?php echo APP_NAME; ?></title>
    <style>
        <?php echo $themeManager->generateCSS(); ?>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--fonte-principal);
            background: var(--cor-fundo);
            color: var(--cor-texto);
        }
        
        .navbar {
            background: var(--cor-primaria);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--cor-primaria);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .menu {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .menu a {
            padding: 0.75rem 1.5rem;
            background: var(--cor-card);
            color: var(--cor-texto);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .menu a:hover,
        .menu a.active {
            background: var(--cor-primaria);
            color: white;
        }
        
        .card {
            background: var(--cor-card);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: var(--cor-primaria);
            margin-bottom: 1.5rem;
        }
        
        .presets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .preset {
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .preset:hover {
            border-color: var(--cor-primaria);
            transform: translateY(-2px);
        }
        
        .preset-colors {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .preset-color {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .color-input-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .color-input {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .color-text {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .preview-box {
            padding: 2rem;
            border-radius: 12px;
            background: var(--cor-fundo);
            border: 2px solid var(--cor-primaria);
        }
        
        .preview-text {
            color: var(--cor-texto);
            margin-bottom: 1rem;
        }
        
        .preview-buttons {
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🛒 <?php echo APP_NAME; ?></h1>
        <div class="user-info">
            <span><?php echo e($user['nome']); ?></span>
            <?php if ($user['foto_perfil']): ?>
                <img src="<?php echo e($user['foto_perfil']); ?>" alt="Foto" class="user-photo">
            <?php endif; ?>
            <a href="logout.php" class="btn btn-outline">Sair</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="produtos.php">Produtos</a>
            <a href="carteira.php">Carteira</a>
            <a href="configuracoes.php" class="active">Configurações</a>
        </div>
        
        <div id="alert" style="display: none;"></div>
        
        <div class="card">
            <h2>🎨 Temas Pré-definidos</h2>
            <div class="presets">
                <?php foreach ($presets as $key => $preset): ?>
                    <div class="preset" onclick="applyPreset('<?php echo $key; ?>')">
                        <div class="preset-colors">
                            <div class="preset-color" style="background: <?php echo $preset['cor_primaria']; ?>"></div>
                            <div class="preset-color" style="background: <?php echo $preset['cor_secundaria']; ?>"></div>
                        </div>
                        <strong><?php echo $preset['nome']; ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card">
            <h2>🎨 Personalizar Tema</h2>
            
            <form id="themeForm">
                <div class="form-group">
                    <label>Cor Primária</label>
                    <div class="color-input-group">
                        <input type="color" class="color-input" id="cor_primaria" 
                               value="<?php echo e($theme['cor_primaria']); ?>">
                        <input type="text" class="color-text" 
                               value="<?php echo e($theme['cor_primaria']); ?>" 
                               readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cor Secundária</label>
                    <div class="color-input-group">
                        <input type="color" class="color-input" id="cor_secundaria" 
                               value="<?php echo e($theme['cor_secundaria']); ?>">
                        <input type="text" class="color-text" 
                               value="<?php echo e($theme['cor_secundaria']); ?>" 
                               readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cor de Fundo</label>
                    <div class="color-input-group">
                        <input type="color" class="color-input" id="cor_fundo" 
                               value="<?php echo e($theme['cor_fundo']); ?>">
                        <input type="text" class="color-text" 
                               value="<?php echo e($theme['cor_fundo']); ?>" 
                               readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cor do Texto</label>
                    <div class="color-input-group">
                        <input type="color" class="color-input" id="cor_texto" 
                               value="<?php echo e($theme['cor_texto']); ?>">
                        <input type="text" class="color-text" 
                               value="<?php echo e($theme['cor_texto']); ?>" 
                               readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cor dos Cards</label>
                    <div class="color-input-group">
                        <input type="color" class="color-input" id="cor_card" 
                               value="<?php echo e($theme['cor_card']); ?>">
                        <input type="text" class="color-text" 
                               value="<?php echo e($theme['cor_card']); ?>" 
                               readonly>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Salvar Tema</button>
            </form>
        </div>
        
        <div class="card">
            <h2>👁️ Pré-visualização</h2>
            <div class="preview-box">
                <h3 class="preview-text">Este é um exemplo de texto</h3>
                <p class="preview-text">
                    Veja como as cores ficam com seu tema personalizado.
                </p>
                <div class="preview-buttons">
                    <button class="btn btn-primary">Botão Primário</button>
                    <button class="btn" style="background: var(--cor-secundaria); color: white;">Botão Secundário</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const presets = <?php echo json_encode($presets); ?>;
        
        // Atualiza texto ao mudar cor
        document.querySelectorAll('.color-input').forEach(input => {
            input.addEventListener('input', function() {
                this.nextElementSibling.value = this.value;
                updateCSSVariables();
            });
        });
        
        // Atualiza variáveis CSS em tempo real
        function updateCSSVariables() {
            const root = document.documentElement;
            root.style.setProperty('--cor-primaria', document.getElementById('cor_primaria').value);
            root.style.setProperty('--cor-secundaria', document.getElementById('cor_secundaria').value);
            root.style.setProperty('--cor-fundo', document.getElementById('cor_fundo').value);
            root.style.setProperty('--cor-texto', document.getElementById('cor_texto').value);
            root.style.setProperty('--cor-card', document.getElementById('cor_card').value);
        }
        
        // Aplica preset
        function applyPreset(presetKey) {
            const preset = presets[presetKey];
            document.getElementById('cor_primaria').value = preset.cor_primaria;
            document.getElementById('cor_secundaria').value = preset.cor_secundaria;
            document.getElementById('cor_fundo').value = preset.cor_fundo;
            document.getElementById('cor_texto').value = preset.cor_texto;
            document.getElementById('cor_card').value = preset.cor_card;
            
            document.querySelectorAll('.color-input').forEach(input => {
                input.nextElementSibling.value = input.value;
            });
            
            updateCSSVariables();
        }
        
        // Salva tema
        document.getElementById('themeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                cor_primaria: document.getElementById('cor_primaria').value,
                cor_secundaria: document.getElementById('cor_secundaria').value,
                cor_fundo: document.getElementById('cor_fundo').value,
                cor_texto: document.getElementById('cor_texto').value,
                cor_card: document.getElementById('cor_card').value
            };
            
            try {
                const response = await fetch('api_theme.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Tema salvo com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Erro ao salvar tema', 'error');
                }
            } catch (error) {
                showAlert('Erro ao salvar tema', 'error');
            }
        });
        
        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>
