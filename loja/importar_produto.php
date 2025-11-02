<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

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

$ml = new MercadoLivreAPI();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Produto por ID - Marketplace</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
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
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .product-preview {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            display: none;
        }
        .product-preview img { max-width: 200px; border-radius: 8px; margin-bottom: 15px; }
        .product-title { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
        .product-price { font-size: 24px; color: #4caf50; font-weight: bold; margin-bottom: 10px; }
        .product-meta { font-size: 14px; color: #666; margin-bottom: 5px; }
        .loading { text-align: center; padding: 20px; display: none; }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Importar Produto do ML</h1>
        <p class="subtitle">Importe produtos individualmente usando o ID do anúncio</p>
        
        <div class="alert alert-info">
            ℹ️ <strong>Como encontrar o ID do produto?</strong><br>
            Na sua lista de anúncios, o ID aparece como <code>SKU</code> ou <code>#</code>. Exemplo:<br>
            <code>MLB3971294269</code> ou apenas <code>3971294269</code>
        </div>
        
        <div id="message"></div>
        
        <form id="importForm" onsubmit="importProduct(event)">
            <div class="form-group">
                <label for="productId">ID do Produto (MLB ID)</label>
                <input 
                    type="text" 
                    id="productId" 
                    name="productId" 
                    placeholder="MLB3971294269 ou 3971294269"
                    required
                >
                <div class="help-text">Cole o ID completo ou apenas os números</div>
            </div>
            
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>Buscando produto do Mercado Livre...</p>
            </div>
            
            <div id="preview" class="product-preview"></div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary" id="importBtn">
                    📥 Importar Produto
                </button>
                <a href="produtos.php" class="btn btn-secondary">
                    ← Ver Produtos
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    🏠 Dashboard
                </a>
            </div>
        </form>
    </div>
    
    <script>
        async function importProduct(e) {
            e.preventDefault();
            
            const productId = document.getElementById('productId').value.trim();
            const loadingDiv = document.getElementById('loading');
            const previewDiv = document.getElementById('preview');
            const messageDiv = document.getElementById('message');
            const importBtn = document.getElementById('importBtn');
            
            // Limpa ID (remove MLB se houver)
            const cleanId = productId.replace(/^MLB/i, '');
            const fullId = 'MLB' + cleanId;
            
            messageDiv.innerHTML = '';
            previewDiv.style.display = 'none';
            loadingDiv.style.display = 'block';
            importBtn.disabled = true;
            
            try {
                const response = await fetch('api_importar_produto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: fullId })
                });
                
                const result = await response.json();
                
                loadingDiv.style.display = 'none';
                
                if (result.success) {
                    messageDiv.innerHTML = `
                        <div class="alert alert-success">
                            ✅ <strong>Produto importado com sucesso!</strong><br>
                            ${result.product.titulo}<br>
                            <a href="produtos.php">Ver todos os produtos</a>
                        </div>
                    `;
                    
                    // Mostra preview
                    previewDiv.innerHTML = `
                        <img src="${result.product.thumbnail}" alt="${result.product.titulo}">
                        <div class="product-title">${result.product.titulo}</div>
                        <div class="product-price">${result.product.moeda} ${parseFloat(result.product.preco).toFixed(2)}</div>
                        <div class="product-meta">📦 Estoque: ${result.product.quantidade_disponivel}</div>
                        <div class="product-meta">✅ Status: ${result.product.status}</div>
                        <div class="product-meta">🔗 <a href="${result.product.permalink}" target="_blank">Ver no ML</a></div>
                    `;
                    previewDiv.style.display = 'block';
                    
                    document.getElementById('productId').value = '';
                } else {
                    messageDiv.innerHTML = `
                        <div class="alert alert-error">
                            ❌ <strong>Erro ao importar</strong><br>
                            ${result.message}
                        </div>
                    `;
                }
            } catch (error) {
                loadingDiv.style.display = 'none';
                messageDiv.innerHTML = `
                    <div class="alert alert-error">
                        ❌ <strong>Erro de conexão</strong><br>
                        ${error.message}
                    </div>
                `;
            }
            
            importBtn.disabled = false;
        }
    </script>
</body>
</html>
