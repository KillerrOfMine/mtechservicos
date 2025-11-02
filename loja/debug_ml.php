<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

// Verifica se está logado
if (!isLoggedIn()) {
    die("Você precisa estar logado");
}

echo "<h1>Debug Mercado Livre API</h1>";

$ml = new MercadoLivreAPI($_SESSION['user_id']);

echo "<h2>1. Verificando Token</h2>";
try {
    $token = $ml->getValidToken();
    if ($token) {
        echo "✅ Token válido encontrado<br>";
        echo "Token: " . substr($token, 0, 20) . "...<br>";
    } else {
        echo "❌ Nenhum token válido. <a href='conectar_ml.php'>Conecte sua conta primeiro</a><br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erro ao obter token: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>2. Buscando User ID do Mercado Livre</h2>";
try {
    $url = ML_API_URL . '/users/me';
    echo "URL: $url<br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode<br>";
    echo "Response:<br><pre>" . htmlspecialchars($response) . "</pre>";
    
    $userData = json_decode($response, true);
    if (isset($userData['id'])) {
        $userId = $userData['id'];
        echo "✅ User ID: $userId<br>";
        
        echo "<h2>3. Buscando Produtos (API Pública - SEM Token)</h2>";
        $itemsUrl = ML_API_URL . '/sites/MLB/search?seller_id=' . $userId . '&limit=50';
        echo "URL: $itemsUrl<br>";
        
        $ch = curl_init($itemsUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode<br>";
        if ($error) echo "CURL Error: $error<br>";
        echo "Response:<br><pre>" . htmlspecialchars(substr($response, 0, 2000)) . "</pre>";
        
        $itemsData = json_decode($response, true);
        if (isset($itemsData['results']) && is_array($itemsData['results'])) {
            echo "✅ Total de produtos encontrados: " . count($itemsData['results']) . "<br>";
            
            if (count($itemsData['results']) > 0) {
                echo "<h3>Primeiros 5 Produtos:</h3>";
                foreach (array_slice($itemsData['results'], 0, 5) as $item) {
                    echo "- ID: " . ($item['id'] ?? 'N/A') . " | Título: " . ($item['title'] ?? 'N/A') . "<br>";
                }
            } else {
                echo "⚠️ Você não tem produtos ativos no Mercado Livre<br>";
            }
        }
        
        echo "<h2>4. Testando API de Items Diretamente</h2>";
        $itemsUrl2 = ML_API_URL . '/users/' . $userId . '/items/search';
        echo "URL: $itemsUrl2<br>";
        
        $ch2 = curl_init($itemsUrl2);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        
        $response2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        
        echo "HTTP Code: $httpCode2<br>";
        echo "Response:<br><pre>" . htmlspecialchars(substr($response2, 0, 1000)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<a href='dashboard.php'>Voltar ao Dashboard</a>";
