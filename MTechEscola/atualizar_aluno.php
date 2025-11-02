<?php
require_once 'db_connect_horarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $data_nascimento = $_POST['data_nascimento'];
    $numero_chamada = $_POST['numero_chamada'] ?? null;
    $ativo = ($_POST['ativo'] === 'true');

    $sql = "UPDATE alunos SET nome = ?, cpf = ?, telefone = ?, email = ?, data_nascimento = ?, numero_chamada = ?, ativo = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $cpf, $telefone, $email, $data_nascimento, $numero_chamada, $ativo, $id]);

    echo '<script>alert("Aluno atualizado com sucesso!"); window.location.href = "alunos.php";</script>';
    exit;
}
?>