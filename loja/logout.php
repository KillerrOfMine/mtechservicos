<?php
require_once 'config.php';
require_once 'classes/GoogleAuth.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$auth = new GoogleAuth();
$auth->logout();

redirect('login.php');
