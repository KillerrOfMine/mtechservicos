<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

// Session já foi iniciada no config.php

header('Content-Type: application/json');

// Log de debug
error_log("=== IMPORTAR PRODUTO ===");
error_log("Session ID: " . session_id());
error_log("Session usuario_id: " . ($_SESSION['usuario_id'] ?? 'não definido'));
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'não definido'));
error_log("All session: " . print_r($_SESSION, true));

if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado. Faça login primeiro.']);
    exit;
}

// Usa o ID correto da sessão
$userId = $_SESSION['usuario_id'] ?? $_SESSION['user_id'];

$input = file_get_contents('php://input');
error_log("Input RAW recebido: " . $input);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));

$data = json_decode($input, true);
error_log("Data decodificado: " . print_r($data, true));

$productId = $data['product_id'] ?? '';

error_log("Product ID extraído: " . $productId);

if (empty($productId)) {
    error_log("ERRO: Product ID vazio!");
    echo json_encode(['success' => false, 'message' => 'ID do produto não fornecido', 'debug' => ['input' => $input, 'data' => $data]]);
    exit;
}

try {
    $ml = new MercadoLivreAPI();
    
    error_log("Buscando item: " . $productId);
    
    // Busca detalhes do item diretamente
    $itemDetails = $ml->getItem($productId);
    
    error_log("Item details: " . json_encode($itemDetails));
    
    if (!$itemDetails || isset($itemDetails['error'])) {
        $errorMsg = isset($itemDetails['message']) ? $itemDetails['message'] : 'Produto não encontrado';
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar produto: ' . $errorMsg]);
        exit;
    }
    
    // Busca descrição
    $descricao = '';
    try {
        $accessToken = $ml->getValidToken();
        $descUrl = ML_API_URL . '/items/' . $productId . '/description';
        
        $ch = curl_init($descUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $descResponse = curl_exec($ch);
        curl_close($ch);
        
        $descData = json_decode($descResponse, true);
        $descricao = $descData['plain_text'] ?? '';
    } catch (Exception $e) {
        // Ignora erro de descrição
    }
    
    $db = getDB();
    
    // Verifica se produto já existe
    $stmt = $db->prepare("SELECT id FROM produtos_ml WHERE ml_id = ?");
    $stmt->execute([$itemDetails['id']]);
    $existingProduct = $stmt->fetch();
    
    if ($existingProduct) {
        // Atualiza
        $stmt = $db->prepare("
            UPDATE produtos_ml SET
                titulo = ?,
                descricao = ?,
                preco = ?,
                moeda = ?,
                quantidade_disponivel = ?,
                quantidade_vendida = ?,
                condicao = ?,
                categoria_id = ?,
                thumbnail = ?,
                permalink = ?,
                status = ?,
                data_sincronizacao = CURRENT_TIMESTAMP
            WHERE ml_id = ?
        ");
        
        $stmt->execute([
            $itemDetails['title'],
            $descricao,
            $itemDetails['price'],
            $itemDetails['currency_id'],
            $itemDetails['available_quantity'],
            $itemDetails['sold_quantity'],
            $itemDetails['condition'],
            $itemDetails['category_id'],
            $itemDetails['thumbnail'],
            $itemDetails['permalink'],
            $itemDetails['status'],
            $itemDetails['id']
        ]);
        
        $productId = $existingProduct['id'];
        $message = 'Produto atualizado com sucesso';
    } else {
        // Insere
        $stmt = $db->prepare("
            INSERT INTO produtos_ml 
            (usuario_id, ml_id, titulo, descricao, preco, moeda, quantidade_disponivel, 
             quantidade_vendida, condicao, categoria_id, thumbnail, permalink, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            RETURNING id
        ");
        
        $stmt->execute([
            $userId,
            $itemDetails['id'],
            $itemDetails['title'],
            $descricao,
            $itemDetails['price'],
            $itemDetails['currency_id'],
            $itemDetails['available_quantity'],
            $itemDetails['sold_quantity'],
            $itemDetails['condition'],
            $itemDetails['category_id'],
            $itemDetails['thumbnail'],
            $itemDetails['permalink'],
            $itemDetails['status']
        ]);
        
        $result = $stmt->fetch();
        $productId = $result['id'];
        $message = 'Produto importado com sucesso';
    }
    
    // Sincroniza imagens
    if (isset($itemDetails['pictures'])) {
        // Remove imagens antigas
        $stmt = $db->prepare("DELETE FROM produto_imagens WHERE produto_id = ?");
        $stmt->execute([$productId]);
        
        // Insere novas imagens
        $stmt = $db->prepare("
            INSERT INTO produto_imagens (produto_id, url, ordem) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($itemDetails['pictures'] as $index => $picture) {
            $stmt->execute([
                $productId,
                $picture['secure_url'],
                $index
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'product' => [
            'id' => $productId,
            'ml_id' => $itemDetails['id'],
            'titulo' => $itemDetails['title'],
            'preco' => $itemDetails['price'],
            'moeda' => $itemDetails['currency_id'],
            'quantidade_disponivel' => $itemDetails['available_quantity'],
            'thumbnail' => $itemDetails['thumbnail'],
            'permalink' => $itemDetails['permalink'],
            'status' => $itemDetails['status']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
