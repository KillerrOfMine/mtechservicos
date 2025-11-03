<?php
session_start();
require_once 'db_connect_horarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    // Consulta o usuário na tabela usuarios
    $sql = "SELECT * FROM usuarios WHERE usuario = ? AND ativo = true LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$usuario]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_data && password_verify($senha, $usuario_data['senha'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $usuario_data['id'];
        $_SESSION['usuario_nome'] = $usuario_data['nome'];
        $_SESSION['usuario_perfil'] = $usuario_data['perfil'] ?? 'usuario';
        header('Location: home.php');
        exit;
    } else {
        echo '<script>alert("Usuário ou senha inválidos!"); window.location.href = "login.php";</script>';
        exit;
    }
}
?>