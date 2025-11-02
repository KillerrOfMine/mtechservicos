<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $ml = new MercadoLivreAPI();
    $result = $ml->syncProducts();
    
    // Se sincronizou produtos, busca eles do banco
    if ($result['success'] && $result['synced'] > 0) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT ml_id, titulo, preco, moeda, quantidade_disponivel, thumbnail 
            FROM produtos_ml 
            WHERE usuario_id = ? 
            ORDER BY criado_em DESC 
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        $result['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
