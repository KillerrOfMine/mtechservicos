<?php
require_once 'db_connect_horarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $usuario = $_POST['usuario'];
    $telefone = $_POST['telefone'];
    $ativo = ($_POST['ativo'] === 'true');
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    $senha2 = isset($_POST['senha2']) ? $_POST['senha2'] : '';

    if (!empty($senha)) {
        if ($senha !== $senha2) {
            echo '<script>alert("As senhas não coincidem!"); window.location.href = "editar_usuario.php?id=' . $id . '";</script>';
            exit;
        }
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET nome = ?, usuario = ?, telefone = ?, ativo = ?, senha = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $usuario, $telefone, $ativo, $senha_hash, $id]);
    } else {
    $sql = "UPDATE usuarios SET nome = ?, usuario = ?, telefone = ?, ativo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $usuario, $telefone, $ativo, $id]);
    }

    echo '<script>alert("Usuário atualizado com sucesso!"); window.location.href = "usuarios.php";</script>';
    exit;
}
?>