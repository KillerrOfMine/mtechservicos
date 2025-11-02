<?php
// API para buscar turmas por nível
include_once 'db_connect_horarios.php';
header('Content-Type: application/json');
$nivel_id = isset($_GET['nivel_id']) ? intval($_GET['nivel_id']) : 0;
$result = [];
if ($nivel_id > 0) {
    $res = pg_query($conn, "SELECT id, nome FROM turmas WHERE nivel_id = $nivel_id ORDER BY nome;");
    while ($row = pg_fetch_assoc($res)) {
        $result[] = [ 'id' => $row['id'], 'nome' => $row['nome'] ];
    }
}
echo json_encode($result);
