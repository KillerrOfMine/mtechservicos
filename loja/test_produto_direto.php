<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Teste Direto de Produto</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;}</style>";

$productId = 'MLB3971294269';

echo "<h2>Testando produto: $productId</h2>";

// Teste 1: Sem autenticação
echo "<h3>Teste 1: API Pública (sem token)</h3>";
$url1 = 'https://api.mercadolibre.com/items/' . $productId;
echo "<p>URL: <code>$url1</code></p>";

$ch = curl_init($url1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'User-Agent: Mozilla/5.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode1</p>";
echo "<pre>" . htmlspecialchars(substr($response1, 0, 2000)) . "</pre>";

// Teste 2: Com token
echo "<h3>Teste 2: Com Token ML</h3>";

try {
    $db = getDB();
    $stmt = $db->query("SELECT access_token FROM ml_tokens ORDER BY data_criacao DESC LIMIT 1");
    $tokenData = $stmt->fetch();
    
    if ($tokenData) {
        $token = $tokenData['access_token'];
        echo "<p>✅ Token encontrado</p>";
        
        $ch = curl_init($url1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response2 = curl_exec($ch);
        $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p><strong>HTTP Code:</strong> $httpCode2</p>";
        echo "<pre>" . htmlspecialchars(substr($response2, 0, 2000)) . "</pre>";
    } else {
        echo "<p>❌ Token não encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

// Teste 3: Busca pelo título no Google
echo "<h3>Teste 3: Verificar se produto existe</h3>";
echo "<p>Busque no Google: <a href='https://www.google.com/search?q=site:mercadolivre.com.br+MLB3971294269' target='_blank'>site:mercadolivre.com.br MLB3971294269</a></p>";
echo "<p>Ou direto no ML: <a href='https://produto.mercadolivre.com.br/MLB-3971294269' target='_blank'>https://produto.mercadolivre.com.br/MLB-3971294269</a></p>";
