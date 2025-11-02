<?php
require_once 'db_connect_horarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $data_nascimento = $_POST['data_nascimento'];
    $turma_id = $_POST['turma_id'] ?? null;
    $numero_chamada = $_POST['numero_chamada'] ?? null;

    // Verifica se o aluno já existe
    $sql_check = "SELECT id FROM alunos WHERE cpf = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([$cpf]);
    if ($stmt_check->fetch()) {
        echo '<script>alert("Aluno já cadastrado!"); window.location.href = "cadastrar_aluno.php";</script>';
        exit;
    }

    // Insere o novo aluno
    $sql = "INSERT INTO alunos (nome, cpf, telefone, email, data_nascimento, turma_id, numero_chamada, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, true)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nome, $cpf, $telefone, $email, $data_nascimento, $turma_id, $numero_chamada]);

    echo '<script>alert("Aluno cadastrado com sucesso!"); window.location.href = "cadastrar_aluno.php";</script>';
    exit;
}
?>