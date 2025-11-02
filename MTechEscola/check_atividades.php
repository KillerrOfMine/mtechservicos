<?php
require_once 'db_connect_horarios.php';

echo "Verificando estrutura da tabela atividades:\n\n";

try {
    $stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'atividades' ORDER BY ordinal_position");
    
    echo "Colunas da tabela 'atividades':\n";
    while ($col = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
    }
    
    echo "\n\nPrimeiro registro:\n";
    $stmt = $conn->query("SELECT * FROM atividades LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        print_r($row);
    } else {
        echo "Nenhum registro encontrado.\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
