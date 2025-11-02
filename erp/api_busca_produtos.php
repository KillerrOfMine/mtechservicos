<?php
require_once '../includes/db_connect.php';
header('Content-Type: application/json');
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$logPath = __DIR__ . '/busca_debug.log';
file_put_contents($logPath, date('Y-m-d H:i:s') . " | q: $q\n", FILE_APPEND);
if ($q === '') {
    echo json_encode(['debug' => 'query vazia']);
    exit;
}
try {
    $qLike = "%$q%";
    file_put_contents($logPath, date('Y-m-d H:i:s') . " | qLike: $qLike\n", FILE_APPEND);
    $stmt = $pdo->prepare('SELECT id, nome, valor, unidade_medida FROM produtos WHERE nome ILIKE :q1 OR codigo_barras ILIKE :q2 OR ncm ILIKE :q3 ORDER BY nome LIMIT 10');
    $stmt->execute([':q1' => $qLike, ':q2' => $qLike, ':q3' => $qLike]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($logPath, date('Y-m-d H:i:s') . " | resultados: " . json_encode($produtos) . "\n", FILE_APPEND);
    echo json_encode($produtos);
} catch (Exception $e) {
    file_put_contents($logPath, date('Y-m-d H:i:s') . " | erro: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['error' => $e->getMessage(), 'debug' => $q]);
}
