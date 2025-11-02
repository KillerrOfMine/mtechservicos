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

// Verifica mensagens
$message = '';
if (isset($_GET['ml_connected'])) {
    $message = '<div class="alert alert-success">✅ Mercado Livre conectado com sucesso!</div>';
} elseif (isset($_GET['error'])) {
    $message = '<div class="alert alert-error">❌ Erro ao conectar com Mercado Livre. Tente novamente.</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
        
        .btn-secondary {
            background: var(--cor-secundaria);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--cor-primaria);
            color: var(--cor-primaria);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: var(--cor-card);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--cor-primaria);
        }
        
        .stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--cor-secundaria);
        }
        
        .actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        .menu a:hover {
            background: var(--cor-primaria);
            color: white;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .quick-action {
            text-align: center;
            padding: 2rem 1rem;
            background: var(--cor-card);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--cor-texto);
        }
        
        .quick-action:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .quick-action-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
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
        <?php echo $message; ?>
        
        <div class="menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="produtos.php">Produtos</a>
            <a href="carteira.php">Carteira</a>
            <a href="configuracoes.php">Configurações</a>
        </div>
        
        <?php
        // Verifica status do Mercado Livre
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM ml_tokens WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $mlConnected = $stmt->fetch()['total'] > 0;
        
        if ($mlConnected):
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos_ml WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $totalProdutos = $stmt->fetch()['total'];
        ?>
        <div class="alert alert-success" style="margin-bottom: 2rem;">
            ✅ <strong>Mercado Livre Conectado</strong><br>
            <?php if ($totalProdutos > 0): ?>
                <?php echo $totalProdutos; ?> produto(s) sincronizado(s). 
                <a href="sincronizar.php" style="color: #2e7d32; text-decoration: underline;">Sincronizar novamente</a>
            <?php else: ?>
                ⚠️ Nenhum produto encontrado. 
                <a href="https://www.mercadolivre.com.br/vendas/publicar" target="_blank" style="color: #2e7d32; text-decoration: underline;">Criar primeiro anúncio no ML</a> 
                ou <a href="sincronizar.php" style="color: #2e7d32; text-decoration: underline;">tentar sincronizar</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info" style="margin-bottom: 2rem; background: #e3f2fd; color: #1565c0; border: 1px solid #2196f3;">
            ℹ️ <strong>Mercado Livre não conectado</strong><br>
            Conecte sua conta para importar produtos automaticamente. 
            <a href="conectar_ml.php" style="color: #1565c0; text-decoration: underline;">Conectar agora</a>
        </div>
        <?php endif; ?>
        
        <h2 style="margin-bottom: 1.5rem;">Ações Rápidas</h2>
        <div class="quick-actions">
            <a href="conectar_ml.php" class="quick-action">
                <div class="quick-action-icon">🔗</div>
                <div>Conectar ML</div>
            </a>
            <a href="sincronizar.php" class="quick-action">
                <div class="quick-action-icon">🔄</div>
                <div>Sincronizar</div>
            </a>
            <a href="produtos.php" class="quick-action">
                <div class="quick-action-icon">📦</div>
                <div>Produtos</div>
            </a>
            <a href="carteira.php" class="quick-action">
                <div class="quick-action-icon">💰</div>
                <div>Carteira</div>
            </a>
            <a href="configuracoes.php" class="quick-action">
                <div class="quick-action-icon">🎨</div>
                <div>Temas</div>
            </a>
        </div>
        
        <h2 style="margin: 2rem 0 1rem;">Estatísticas</h2>
        <div class="dashboard-grid">
            <div class="card">
                <h2>Produtos</h2>
                <div class="stat-value" id="total-produtos">-</div>
                <div>produtos sincronizados</div>
            </div>
            
            <div class="card">
                <h2>Vendas Totais</h2>
                <div class="stat-value" id="total-vendas">-</div>
                <div>unidades vendidas</div>
            </div>
            
            <div class="card">
                <h2>Saldo</h2>
                <div class="stat-value" id="saldo">R$ -</div>
                <div>disponível na carteira</div>
            </div>
        </div>
    </div>
    
    <script>
        // Carrega estatísticas
        async function loadStats() {
            try {
                const response = await fetch('api_stats.php');
                const data = await response.json();
                
                document.getElementById('total-produtos').textContent = data.total_produtos || 0;
                document.getElementById('total-vendas').textContent = data.total_vendas || 0;
                document.getElementById('saldo').textContent = 'R$ ' + (data.saldo || '0,00');
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
        }
        
        loadStats();
    </script>
</body>
</html>
