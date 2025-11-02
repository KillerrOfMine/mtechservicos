<?php
require_once __DIR__ . '/includes/db_connect.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query('SELECT id, nome, tipo_contato, cpf_cnpj, contato FROM contatos ORDER BY id DESC LIMIT 100');
    $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'contatos' => $contatos]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao listar contatos: ' . $e->getMessage()]);
}
