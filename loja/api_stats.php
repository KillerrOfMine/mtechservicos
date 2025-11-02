<?php
require_once 'config.php';

// Verifica se está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$db = getDB();
$userId = $_SESSION['user_id'];

try {
    // Total de produtos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos_ml WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $totalProdutos = $stmt->fetch()['total'];
    
    // Total de vendas
    $stmt = $db->prepare("SELECT SUM(quantidade_vendida) as total FROM produtos_ml WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $totalVendas = $stmt->fetch()['total'] ?? 0;
    
    // Produtos ativos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos_ml WHERE usuario_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $produtosAtivos = $stmt->fetch()['total'];
    
    // Valor total em estoque
    $stmt = $db->prepare("
        SELECT SUM(preco * quantidade_disponivel) as total 
        FROM produtos_ml 
        WHERE usuario_id = ? AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $valorEstoque = $stmt->fetch()['total'] ?? 0;
    
    // Saldo (mock - requer integração real)
    $saldo = '0,00';
    
    echo json_encode([
        'total_produtos' => $totalProdutos,
        'total_vendas' => $totalVendas,
        'produtos_ativos' => $produtosAtivos,
        'valor_estoque' => number_format($valorEstoque, 2, ',', '.'),
        'saldo' => $saldo
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar estatísticas']);
}
