<?php
require_once 'config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Necessário</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 60px 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 { font-size: 48px; margin-bottom: 20px; }
            p { color: #666; margin-bottom: 30px; font-size: 16px; }
            .btn {
                display: inline-block;
                padding: 15px 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                font-size: 16px;
                transition: transform 0.3s;
            }
            .btn:hover { transform: translateY(-3px); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒</h1>
            <h2>Login Necessário</h2>
            <p>Você precisa fazer login para importar produtos do Mercado Livre.</p>
            <a href="login.php" class="btn">🔑 Fazer Login com Google</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Todos os Produtos - Marketplace</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; font-size: 14px; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info { background: #e3f2fd; color: #1565c0; border: 1px solid #2196f3; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #f44336; }
        .alert-warning { background: #fff3e0; color: #e65100; border: 1px solid #ff9800; }
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .btn-success { background: #4caf50; color: white; }
        .btn-small { padding: 8px 16px; font-size: 14px; }
        .loading { text-align: center; padding: 40px; }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .product-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            position: relative;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .product-card:hover { border-color: #667eea; transform: translateY(-5px); }
        .product-card.imported { opacity: 0.6; border-color: #4caf50; }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .product-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price {
            font-size: 18px;
            color: #4caf50;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .product-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .product-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-paused { background: #fff3e0; color: #e65100; }
        .badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4caf50;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Importar Todos os Produtos do ML</h1>
        <p class="subtitle">Seus anúncios ativos no Mercado Livre</p>
        
        <div id="message"></div>
        
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Buscando seus anúncios do Mercado Livre...</p>
        </div>
        
        <div id="controls" style="display: none;">
            <button onclick="importAll()" class="btn btn-primary" id="importAllBtn">
                📥 Importar Todos os Produtos
            </button>
            <button onclick="importSelected()" class="btn btn-success" id="importSelectedBtn" style="display:none;">
                ✅ Importar Selecionados
            </button>
            <a href="produtos.php" class="btn btn-secondary">
                📋 Ver Produtos Importados
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                🏠 Dashboard
            </a>
        </div>
        
        <div id="progress" style="display: none;">
            <h3>Importando produtos...</h3>
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar">0%</div>
            </div>
            <p id="progressText">0 de 0 produtos importados</p>
        </div>
        
        <div id="products" class="products-grid"></div>
    </div>
    
    <script>
        let allProducts = [];
        let importedCount = 0;
        
        async function loadProducts() {
            try {
                const response = await fetch('api_listar_produtos_ml.php');
                const result = await response.json();
                
                document.getElementById('loading').style.display = 'none';
                
                if (result.success) {
                    allProducts = result.products;
                    displayProducts(result.products);
                    document.getElementById('controls').style.display = 'block';
                    
                    document.getElementById('message').innerHTML = `
                        <div class="alert alert-success">
                            ✅ <strong>${result.total} produtos encontrados!</strong><br>
                            Clique em "Importar Todos" para sincronizar com seu marketplace.
                        </div>
                    `;
                } else {
                    document.getElementById('message').innerHTML = `
                        <div class="alert alert-error">
                            ❌ <strong>Erro ao buscar produtos</strong><br>
                            ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('message').innerHTML = `
                    <div class="alert alert-error">
                        ❌ <strong>Erro de conexão</strong><br>
                        ${error.message}
                    </div>
                `;
            }
        }
        
        function displayProducts(products) {
            const grid = document.getElementById('products');
            grid.innerHTML = '';
            
            products.forEach(product => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.id = 'product-' + product.id;
                
                const statusClass = product.status === 'active' ? 'status-active' : 'status-paused';
                const statusText = product.status === 'active' ? 'Ativo' : 'Pausado';
                
                card.innerHTML = `
                    <img src="${product.thumbnail}" alt="${product.title}">
                    <div class="product-title">${product.title}</div>
                    <div class="product-price">${product.currency} ${parseFloat(product.price).toFixed(2)}</div>
                    <div class="product-meta">
                        📦 Estoque: ${product.available_quantity} | 
                        💰 Vendidos: ${product.sold_quantity}
                    </div>
                    <span class="product-status ${statusClass}">${statusText}</span>
                    <div class="product-meta" style="font-family: monospace; font-size: 10px;">ID: ${product.id}</div>
                `;
                
                grid.appendChild(card);
            });
        }
        
        async function importAll() {
            if (!confirm(`Deseja importar todos os ${allProducts.length} produtos?`)) {
                return;
            }
            
            document.getElementById('importAllBtn').disabled = true;
            document.getElementById('progress').style.display = 'block';
            document.getElementById('products').style.pointerEvents = 'none';
            
            importedCount = 0;
            const total = allProducts.length;
            
            for (let i = 0; i < allProducts.length; i++) {
                const product = allProducts[i];
                await importProduct(product.id);
                
                importedCount++;
                const percent = Math.round((importedCount / total) * 100);
                document.getElementById('progressBar').style.width = percent + '%';
                document.getElementById('progressBar').textContent = percent + '%';
                document.getElementById('progressText').textContent = `${importedCount} de ${total} produtos importados`;
            }
            
            document.getElementById('message').innerHTML = `
                <div class="alert alert-success">
                    ✅ <strong>Importação concluída!</strong><br>
                    ${importedCount} produtos foram importados com sucesso.
                    <a href="produtos.php">Ver produtos</a>
                </div>
            `;
            
            document.getElementById('progress').style.display = 'none';
            document.getElementById('products').style.pointerEvents = 'auto';
        }
        
        async function importProduct(productId) {
            try {
                const response = await fetch('api_importar_produto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });
                
                const result = await response.json();
                
                const card = document.getElementById('product-' + productId);
                if (result.success && card) {
                    card.classList.add('imported');
                    const badge = document.createElement('div');
                    badge.className = 'badge';
                    badge.textContent = '✓ Importado';
                    card.appendChild(badge);
                }
                
                return result.success;
            } catch (error) {
                console.error('Erro ao importar', productId, error);
                return false;
            }
        }
        
        // Carrega produtos ao abrir a página
        loadProducts();
    </script>
</body>
</html>
