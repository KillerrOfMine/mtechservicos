<?php
session_start();
// Remove todas as variáveis de sessão
$_SESSION = array();
// Destroi a sessão
session_destroy();
// Redireciona para a página de login
header('Location: /erp/login.php');
exit;
?>
