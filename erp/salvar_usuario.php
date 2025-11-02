<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db_connect.php';

$id = $_POST['id'] ?? '';

$usuario = trim($_POST['usuario'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$perfis = $_POST['perfis'] ?? [];

if (!$usuario || !$nome || !$email || empty($perfis)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos.']);
    exit;
}

if (!$id) {
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma_senha'] ?? '';
    if (!$senha || !$confirma || $senha !== $confirma) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Senha e confirmação obrigatórias e devem ser iguais.']);
        exit;
    }
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
}

try {
    if ($id) {
        // Atualizar usuário existente
        $stmt = $pdo->prepare('UPDATE usuarios SET usuario = :usuario, nome = :nome, email = :email WHERE id = :id');
        $stmt->execute([
            ':usuario' => $usuario,
            ':nome' => $nome,
            ':email' => $email,
            ':id' => $id
        ]);
        // Alterar senha se informada
        if (!empty($_POST['nova_senha'])) {
            $nova = $_POST['nova_senha'];
            $confirma = $_POST['confirma_nova_senha'] ?? '';
            if ($nova && $nova === $confirma) {
                $nova_hash = password_hash($nova, PASSWORD_DEFAULT);
                $stmtSenha = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha WHERE id = :id');
                $stmtSenha->execute([':senha' => $nova_hash, ':id' => $id]);
            }
        }
        $usuario_id = $id;
    } else {
        // Criar novo usuário
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, nome, email, senha_hash, ativo) VALUES (:usuario, :nome, :email, :senha, TRUE)');
        $stmt->execute([
            ':usuario' => $usuario,
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senha_hash
        ]);
        $usuario_id = $pdo->lastInsertId();
    }

    // Atualizar perfis vinculados
    // Remove todos os vínculos antigos
    $stmtDel = $pdo->prepare('DELETE FROM usuario_perfil WHERE usuario_id = ?');
    $stmtDel->execute([$usuario_id]);
    // Insere os novos vínculos
    $stmtAdd = $pdo->prepare('INSERT INTO usuario_perfil (usuario_id, perfil_id) VALUES (?, ?)');
    foreach ($perfis as $perfil_id) {
        $stmtAdd->execute([$usuario_id, $perfil_id]);
    }

    echo json_encode(['sucesso' => true]);
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar usuário: ' . $e->getMessage()]);
}
