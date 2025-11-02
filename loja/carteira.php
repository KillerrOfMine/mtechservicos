<?php
require_once 'config.php';
require_once 'classes/GoogleAuth.php';
require_once 'classes/ThemeManager.php';
require_once 'classes/MercadoPago.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$auth = new GoogleAuth();
$user = $auth->getCurrentUser();
$themeManager = new ThemeManager($_SESSION['user_id']);
$theme = $themeManager->getTheme();

// Busca informações do MercadoPago
$mp = new MercadoPago();
$mpUser = $mp->getUserInfo();
$balance = $mp->getBalance();
$transactions = $mp->getTransactions(20);

// Saldo disponível
$saldoDisponivel = isset($balance['available_balance']) ? $balance['available_balance'] : 0;
$saldoBloqueado = isset($balance['unavailable_balance']) ? $balance['unavailable_balance'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteira - <?php echo APP_NAME; ?></title>
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
        
        .saldo-card {
            background: linear-gradient(135deg, var(--cor-primaria), var(--cor-secundaria));
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        .saldo-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .saldo-valor {
            font-size: 3rem;
            font-weight: bold;
        }
        
        .card {
            background: var(--cor-card);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: var(--cor-primaria);
            margin-bottom: 1.5rem;
        }
        
        .transacao {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .transacao:last-child {
            border-bottom: none;
        }
        
        .transacao-info {
            flex: 1;
        }
        
        .transacao-desc {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .transacao-data {
            font-size: 0.85rem;
            color: #666;
        }
        
        .transacao-valor {
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .valor-positivo {
            color: #28a745;
        }
        
        .valor-negativo {
            color: #dc3545;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 1rem;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
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
            <a href="carteira.php" class="active">Carteira</a>
            <a href="configuracoes.php">Configurações</a>
        </div>
        
        <div class="saldo-card">
            <div class="saldo-label">💰 Saldo Disponível</div>
            <div class="saldo-valor">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></div>
        </div>
        
        <div class="card">
            <h2>Últimas Transações</h2>
            
            <?php if (empty($transacoes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💳</div>
                    <h3>Nenhuma transação encontrada</h3>
                    <p style="color: #666;">Conecte sua conta do Mercado Livre para sincronizar suas transações</p>
                </div>
            <?php else: ?>
                <?php foreach ($transacoes as $trans): ?>
                    <div class="transacao">
                        <div class="transacao-info">
                            <div class="transacao-desc">
                                <?php echo e($trans['descricao'] ?: 'Transação'); ?>
                                <span class="status-badge status-<?php echo $trans['status']; ?>">
                                    <?php echo ucfirst($trans['status']); ?>
                                </span>
                            </div>
                            <div class="transacao-data">
                                <?php echo date('d/m/Y H:i', strtotime($trans['data_criacao'])); ?>
                            </div>
                        </div>
                        <div class="transacao-valor <?php echo $trans['valor'] >= 0 ? 'valor-positivo' : 'valor-negativo'; ?>">
                            <?php echo $trans['valor'] >= 0 ? '+' : ''; ?>
                            <?php echo $trans['moeda']; ?> 
                            <?php echo number_format(abs($trans['valor']), 2, ',', '.'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
