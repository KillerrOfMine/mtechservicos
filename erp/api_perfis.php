<?php
// API de perfis: CRUD via AJAX
session_start();
require_once __DIR__ . '/includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'superuser'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Listar perfis
if ($method === 'GET') {
    $stmt = $pdo->query('SELECT id, nome, permissoes FROM perfis ORDER BY nome');
    $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($perfis);
    exit;
}

// Criar perfil
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nome = trim($data['nome'] ?? '');
    $perms = $data['permissoes'] ?? [];
    if (!$nome || !is_array($perms)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    $perms_str = json_encode($perms);
    $stmt = $pdo->prepare('INSERT INTO perfis (nome, permissoes) VALUES (:nome, :perms)');
    try {
        $stmt->execute([':nome' => $nome, ':perms' => $perms_str]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(409);
        echo json_encode(['error' => 'Perfil já existe']);
    }
    exit;
}

// Editar perfil
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $nome = trim($data['nome'] ?? '');
    $perms = $data['permissoes'] ?? [];
    if (!$id || !$nome || !is_array($perms)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    $perms_str = json_encode($perms);
    $stmt = $pdo->prepare('UPDATE perfis SET nome = :nome, permissoes = :perms WHERE id = :id');
    $stmt->execute([':nome' => $nome, ':perms' => $perms_str, ':id' => $id]);
    echo json_encode(['ok' => true]);
    exit;
}

// Excluir perfil
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM perfis WHERE id = :id');
    $stmt->execute([':id' => $id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não suportado']);
