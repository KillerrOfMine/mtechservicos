<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'superuser'])) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}
require_once __DIR__ . '/includes/db_connect.php';
$nome = trim($_POST['nome'] ?? '');
if (!$nome) {
    http_response_code(400);
    echo 'Nome do perfil obrigatório.';
    exit;
}
// Verifica se já existe
$stmt = $pdo->prepare('SELECT COUNT(*) FROM perfis WHERE nome = :nome');
$stmt->execute([':nome' => $nome]);
if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo 'Perfil já existe.';
    exit;
}
// Cria perfil vazio
$stmt = $pdo->prepare('INSERT INTO perfis (nome, permissoes) VALUES (:nome, :permissoes)');
$stmt->execute([':nome' => $nome, ':permissoes' => json_encode([])]);
echo 'Perfil criado com sucesso!';
