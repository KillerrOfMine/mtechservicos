<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/db_connect.php';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$contatos = [];
if ($q !== '') {
    $sql = "SELECT id, nome, fantasia, codigo FROM contatos WHERE nome ILIKE ? OR fantasia ILIKE ? OR codigo ILIKE ? ORDER BY nome LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $like = "%$q%";
    $stmt->execute([$like, $like, $like]);
    foreach ($stmt as $c) {
        $contatos[] = [
            'id' => $c['id'],
            'nome' => $c['nome'],
            'fantasia' => $c['fantasia'],
            'codigo' => $c['codigo']
        ];
    }
}
echo json_encode($contatos);
