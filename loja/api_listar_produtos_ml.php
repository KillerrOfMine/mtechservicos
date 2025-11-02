<?php
require_once 'config.php';

// Não precisa de autenticação - usa API pública
header('Content-Type: application/json');

$nickname = 'MARCOSVINCIUSMARTINSRIBEIRO'; // Seu nickname do ML

try {
    // Busca produtos pelo nickname usando API pública (não precisa token)
    $url = 'https://api.mercadolibre.com/sites/MLB/search?nickname=' . urlencode($nickname) . '&limit=50';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao buscar produtos (HTTP ' . $httpCode . ')',
            'http_code' => $httpCode,
            'response' => $response
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['results']) || empty($data['results'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhum produto encontrado',
            'total' => 0,
            'products' => []
        ]);
        exit;
    }
    
    $products = [];
    foreach ($data['results'] as $item) {
        $products[] = [
            'id' => $item['id'],
            'title' => $item['title'],
            'price' => $item['price'],
            'currency' => $item['currency_id'],
            'thumbnail' => $item['thumbnail'],
            'permalink' => $item['permalink'],
            'status' => $item['status'],
            'available_quantity' => $item['available_quantity'],
            'sold_quantity' => $item['sold_quantity']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($products),
        'products' => $products,
        'message' => 'Encontrados ' . count($products) . ' produtos'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
