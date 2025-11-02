<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Verifica se há código de retorno
if (!isset($_GET['code'])) {
    redirect('dashboard.php?error=ml_no_code');
}

$code = $_GET['code'];
$ml = new MercadoLivreAPI($_SESSION['user_id']);

// Troca código por tokens
$tokenData = $ml->getAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    redirect('dashboard.php?error=ml_token_error');
}

// Salva tokens
$ml->saveTokens($tokenData);

// Não sincroniza automaticamente - usuário pode fazer manualmente
// (evita erro se não houver produtos)

// Redireciona para dashboard
redirect('dashboard.php?ml_connected=1');


