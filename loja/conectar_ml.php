<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$ml = new MercadoLivreAPI($_SESSION['user_id']);
$authUrl = $ml->getAuthUrl();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar Mercado Livre - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FFE600 0%, #FFC700 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        
        .logo {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .ml-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #FFE600;
            color: #333;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .ml-btn:hover {
            background: #FFC700;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .back-link {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #333;
            text-decoration: underline;
        }
        
        .benefits {
            text-align: left;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .benefit {
            display: flex;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .benefit-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .benefit-text {
            flex: 1;
        }
        
        .benefit-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }
        
        .benefit-desc {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📦</div>
        <h1>Conectar Mercado Livre</h1>
        <p class="subtitle">
            Conecte sua conta do Mercado Livre para sincronizar seus anúncios, 
            gerenciar produtos e acompanhar suas vendas em um só lugar.
        </p>
        
        <a href="<?php echo e($authUrl); ?>" class="ml-btn">
            🔗 Conectar Agora
        </a>
        
        <a href="dashboard.php" class="back-link">← Voltar ao Dashboard</a>
        
        <div class="benefits">
            <div class="benefit">
                <div class="benefit-icon">✅</div>
                <div class="benefit-text">
                    <div class="benefit-title">Sincronização Automática</div>
                    <div class="benefit-desc">Seus anúncios são importados automaticamente</div>
                </div>
            </div>
            
            <div class="benefit">
                <div class="benefit-icon">💰</div>
                <div class="benefit-text">
                    <div class="benefit-title">Acompanhe sua Carteira</div>
                    <div class="benefit-desc">Visualize saldo e transações em tempo real</div>
                </div>
            </div>
            
            <div class="benefit">
                <div class="benefit-icon">📊</div>
                <div class="benefit-text">
                    <div class="benefit-title">Relatórios Detalhados</div>
                    <div class="benefit-desc">Estatísticas completas sobre suas vendas</div>
                </div>
            </div>
            
            <div class="benefit">
                <div class="benefit-icon">🔒</div>
                <div class="benefit-text">
                    <div class="benefit-title">100% Seguro</div>
                    <div class="benefit-desc">Conexão oficial via OAuth do Mercado Livre</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
