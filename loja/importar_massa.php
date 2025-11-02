<?php
require_once 'config.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die('Login necessário: <a href="login.php">Fazer Login</a>');
}

// IDs dos produtos do usuário
$productIds = [
    'MLB3971294269', // Kit 10und Cakeboard Redondo 3mm Branco - 35 Cm
    'MLB3971325055', // Caixinha Com Coração Vazado Em Mdf 10x10x3cm
    'MLB5281493874', // Quadro Café Com Vinho 20x25cm
    'MLB5281559512', // Kit 10und Cakeboard Redondo 3mm Branco - 29 Cm
    'MLB5281559516', // Kit 10und Cakeboard Redondo 3mm Branco - 30 Cm
    'MLB5281566454', // Capela Portuguesa Nossa Senhora Do Carmo
    'MLB5281604938', // Quadro Vazado De Jesus Em Mdf 19x27cm
    'MLB5281611010', // Kit 10und Cakeboard Redondo 3mm Branco - 40 Cm
    'MLB5281611016', // Kit 10und Cakeboard Redondo 3mm Branco - 45 Cm
    'MLB5281618158', // Organizador Com Gavetas 32x22x10cm
    'MLB5281633192', // Kit 10und Cakeboard Redondo 3mm Branco - 50 Cm
    'MLB5633671010', // Maquina Para Recorte Scanncut Brother Sdx85
    'MLB3971265187', // Quadro Decorativo Família É Amor 25x20cm
    'MLB5281604950'  // Caixa Branca Desafio Dos 100 Depósitos 15x15x15
];

header('Content-Type: application/json');

try {
    require_once 'classes/MercadoLivreAPI.php';
    
    // IMPORTANTE: Passa o user_id para o construtor
    $ml = new MercadoLivreAPI($_SESSION['user_id']);
    $db = getDB();
    
    $imported = 0;
    $errors = [];
    $products = [];
    
    foreach ($productIds as $productId) {
        try {
            // Busca detalhes do produto
            $itemDetails = $ml->getItem($productId);
            
            if (!$itemDetails || isset($itemDetails['error'])) {
                $errors[] = "Produto $productId: " . ($itemDetails['message'] ?? 'não encontrado');
                continue;
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
            
            // Verifica se já existe
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
                
                $dbProductId = $existingProduct['id'];
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
                    $_SESSION['user_id'],
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
                $dbProductId = $result['id'];
            }
            
            // Sincroniza imagens
            if (isset($itemDetails['pictures'])) {
                $stmt = $db->prepare("DELETE FROM produto_imagens WHERE produto_id = ?");
                $stmt->execute([$dbProductId]);
                
                $stmt = $db->prepare("INSERT INTO produto_imagens (produto_id, url, ordem) VALUES (?, ?, ?)");
                
                foreach ($itemDetails['pictures'] as $index => $picture) {
                    $stmt->execute([
                        $dbProductId,
                        $picture['secure_url'],
                        $index
                    ]);
                }
            }
            
            $products[] = [
                'id' => $itemDetails['id'],
                'titulo' => $itemDetails['title'],
                'preco' => $itemDetails['price']
            ];
            
            $imported++;
            
        } catch (Exception $e) {
            $errors[] = "Produto $productId: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'total' => count($productIds),
        'imported' => $imported,
        'errors' => $errors,
        'products' => $products,
        'message' => "Importados $imported de " . count($productIds) . " produtos"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
