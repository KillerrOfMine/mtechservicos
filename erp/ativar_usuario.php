<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db_connect.php';

$id = $_GET['id'] ?? '';
$acao = $_GET['acao'] ?? '';

if (!$id || !in_array($acao, ['ativar', 'desativar'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

try {
    $ativo = $acao === 'ativar' ? true : false;
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = :ativo WHERE id = :id');
    $stmt->execute([':ativo' => $ativo, ':id' => $id]);
    echo json_encode(['sucesso' => true]);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar status: ' . $e->getMessage()]);
}
