<?php
require_once __DIR__ . '/includes/db_connect.php';
header('Content-Type: application/json');
$stmt = $pdo->query('SELECT id, nome FROM perfis ORDER BY nome ASC');
$perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'perfis' => $perfis]);
