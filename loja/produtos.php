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

// Busca produtos
$db = getDB();
$stmt = $db->prepare("
    SELECT * FROM produtos_ml 
    WHERE usuario_id = ? 
    ORDER BY data_sincronizacao DESC
");
$stmt->execute([$_SESSION['user_id']]);
$produtos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - <?php echo APP_NAME; ?></title>
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
            max-width: 1400px;
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
        
        .produtos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .produto-card {
            background: var(--cor-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .produto-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .produto-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .produto-info {
            padding: 1rem;
        }
        
        .produto-titulo {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .produto-preco {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--cor-secundaria);
            margin-bottom: 0.5rem;
        }
        
        .produto-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-paused {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-closed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .empty-text {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .search-box {
            padding: 0.75rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            width: 300px;
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
            <a href="produtos.php" class="active">Produtos</a>
            <a href="carteira.php">Carteira</a>
            <a href="configuracoes.php">Configurações</a>
        </div>
        
        <div class="header-actions">
            <h2>Meus Produtos (<?php echo count($produtos); ?>)</h2>
            <div>
                <input type="text" class="search-box" placeholder="Buscar produtos..." id="searchBox">
            </div>
        </div>
        
        <?php if (empty($produtos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3 class="empty-title">Nenhum produto encontrado</h3>
                <p class="empty-text">Conecte sua conta do Mercado Livre para sincronizar seus anúncios</p>
                <a href="conectar_ml.php" class="btn btn-primary">Conectar Mercado Livre</a>
            </div>
        <?php else: ?>
            <div class="produtos-grid" id="produtosGrid">
                <?php foreach ($produtos as $produto): ?>
                    <div class="produto-card" data-titulo="<?php echo strtolower(e($produto['titulo'])); ?>">
                        <img src="<?php echo e($produto['thumbnail']); ?>" 
                             alt="<?php echo e($produto['titulo']); ?>" 
                             class="produto-image"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2220%22 fill=%22%23999%22%3ESem imagem%3C/text%3E%3C/svg%3E'">
                        
                        <div class="produto-info">
                            <h3 class="produto-titulo"><?php echo e($produto['titulo']); ?></h3>
                            <div class="produto-preco">
                                <?php echo $produto['moeda']; ?> 
                                <?php echo number_format($produto['preco'], 2, ',', '.'); ?>
                            </div>
                            <div class="produto-stats">
                                <span>Disponível: <?php echo $produto['quantidade_disponivel']; ?></span>
                                <span>Vendidos: <?php echo $produto['quantidade_vendida']; ?></span>
                            </div>
                            <span class="status-badge status-<?php echo $produto['status']; ?>">
                                <?php 
                                $statusMap = [
                                    'active' => 'Ativo',
                                    'paused' => 'Pausado',
                                    'closed' => 'Fechado'
                                ];
                                echo $statusMap[$produto['status']] ?? $produto['status'];
                                ?>
                            </span>
                            <div style="margin-top: 1rem;">
                                <a href="<?php echo e($produto['permalink']); ?>" 
                                   target="_blank" 
                                   class="btn btn-primary" 
                                   style="width: 100%; text-align: center;">
                                    Ver no ML
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Busca em tempo real
        document.getElementById('searchBox')?.addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.produto-card');
            
            cards.forEach(card => {
                const titulo = card.getAttribute('data-titulo');
                if (titulo.includes(search)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
