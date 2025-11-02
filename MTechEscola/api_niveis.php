<?php
// API para buscar níveis por escola
include_once 'db_connect_horarios.php';
header('Content-Type: application/json');
$escola_id = isset($_GET['escola_id']) ? intval($_GET['escola_id']) : 0;
$result = [];
if ($escola_id > 0) {
    $res = pg_query($conn, "SELECT id, nome FROM niveis WHERE escola_id = $escola_id ORDER BY nome;");
    while ($row = pg_fetch_assoc($res)) {
        $result[] = [ 'id' => $row['id'], 'nome' => $row['nome'] ];
    }
}
echo json_encode($result);
