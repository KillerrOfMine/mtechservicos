<?php
require_once 'config.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Remove todos os tokens do ML do usuário
$db = getDB();
$stmt = $db->prepare("DELETE FROM ml_tokens WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);

redirect('dashboard.php?ml_disconnected=1');
