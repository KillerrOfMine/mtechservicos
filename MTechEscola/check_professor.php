<?php
require_once 'db_connect_horarios.php';

$stmt = $conn->query("SELECT id, nome, login FROM professores WHERE id = 44");
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Professor encontrado:\n";
echo "ID: " . $prof['id'] . "\n";
echo "Nome: " . $prof['nome'] . "\n";
echo "Login: " . $prof['login'] . "\n";

// Tentar login
$login = 'junilson.augusto';
$stmt = $conn->prepare("SELECT id, nome, senha, login FROM professores WHERE login = ?");
$stmt->execute([$login]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nBusca por login 'junilson.augusto':\n";
if ($result) {
    echo "Encontrado: " . $result['nome'] . " (ID: " . $result['id'] . ")\n";
    echo "Hash da senha no banco: " . substr($result['senha'], 0, 30) . "...\n";
} else {
    echo "NÃO ENCONTRADO!\n";
}

// Listar todos os logins
echo "\nTodos os professores:\n";
$stmt = $conn->query("SELECT id, nome, login FROM professores ORDER BY id");
while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID " . $p['id'] . ": " . $p['login'] . " - " . $p['nome'] . "\n";
}
?>
