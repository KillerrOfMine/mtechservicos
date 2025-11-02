<?php
require_once 'db_connect_horarios.php';

if (!isset($_GET['id'])) {
    echo '<script>alert("Usuário não encontrado!"); window.location.href = "usuarios.php";</script>';
    exit;
}

$id = $_GET['id'];
$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);

echo '<script>alert("Usuário excluído com sucesso!"); window.location.href = "usuarios.php";</script>';
exit;
?>