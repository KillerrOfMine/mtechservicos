<?php
require_once 'db_connect_horarios.php';

$senha = '@Mar1401a';
$login = 'junilson.augusto';

// Buscar professor
$stmt = $conn->prepare("SELECT id, nome, senha FROM professores WHERE login = ?");
$stmt->execute([$login]);
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Testando senha para: " . $login . "\n\n";
echo "Hash no banco:\n" . $prof['senha'] . "\n\n";

// Testar senha atual
if (password_verify($senha, $prof['senha'])) {
    echo "✓ Senha '@Mar1401a' está CORRETA!\n";
} else {
    echo "✗ Senha '@Mar1401a' está INCORRETA!\n";
    echo "\nGerando novo hash para '@Mar1401a'...\n";
    $novo_hash = password_hash($senha, PASSWORD_DEFAULT);
    echo "Novo hash: " . $novo_hash . "\n\n";
    
    // Atualizar no banco
    $stmt = $conn->prepare("UPDATE professores SET senha = ? WHERE login = ?");
    $stmt->execute([$novo_hash, $login]);
    echo "✓ Senha atualizada no banco!\n";
}
?>
