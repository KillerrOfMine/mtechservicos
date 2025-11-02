<?php
require_once 'db_connect_horarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    // Campo perfil removido, cadastro apenas de administradores
    $senha = $_POST['senha'];
    $senha2 = $_POST['senha2'];

    if ($senha !== $senha2) {
        echo '<script>alert("As senhas não coincidem!"); window.location.href = "cadastrar_usuario.php";</script>';
        exit;
    }
    if (strlen($senha) < 6) {
        echo '<script>alert("A senha deve ter pelo menos 6 caracteres."); window.location.href = "cadastrar_usuario.php";</script>';
        exit;
    }
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o usuário já existe
    $sql_check = "SELECT id FROM usuarios WHERE usuario = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$cpf]);
    if ($stmt_check->fetch()) {
        echo '<script>alert("Usuário já cadastrado!"); window.location.href = "cadastrar_usuario.php";</script>';
        exit;
    }

    // Insere o novo usuário
    try {
        $sql = "INSERT INTO usuarios (nome, usuario, telefone, senha) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $cpf, $telefone, $senha_hash]);
        echo '<script>alert("Usuário cadastrado com sucesso!"); window.location.href = "usuarios.php";</script>';
        exit;
    } catch (PDOException $e) {
        echo '<div style="background:#ff3c3c;color:#fff;padding:16px;border-radius:8px;margin:24px 0;font-weight:700;text-align:center;">';
        echo 'Erro ao salvar usuário: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        exit;
    }
}
?>