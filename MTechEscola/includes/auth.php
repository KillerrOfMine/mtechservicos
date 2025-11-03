<?php
// Verificar autenticação (admin ou professor)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin = isset($_SESSION['usuario_id']);
$is_professor = isset($_SESSION['professor_id']);

if (!$is_admin && !$is_professor) {
    header('Location: login.php');
    exit;
}

// ID do usuário para logs/registros
$usuario_id = $is_admin ? $_SESSION['usuario_id'] : $_SESSION['professor_id'];
$usuario_nome = $is_admin ? ($_SESSION['usuario_nome'] ?? 'Admin') : ($_SESSION['professor_nome'] ?? 'Professor');
