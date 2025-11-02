<?php
require_once 'config.php';
require_once 'classes/GoogleAuth.php';

$auth = new GoogleAuth();

// Verifica se há código de retorno
if (!isset($_GET['code'])) {
    redirect('login.php?error=no_code');
}

$code = $_GET['code'];

// Troca código por token
$tokenData = $auth->getAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    redirect('login.php?error=token_error');
}

// Obtém informações do usuário
$userInfo = $auth->getUserInfo($tokenData['access_token']);

if (!$userInfo) {
    redirect('login.php?error=user_info_error');
}

// Salva ou atualiza usuário
$userId = $auth->saveUser($userInfo);

// Faz login
$auth->login($userId);

// Redireciona para dashboard
redirect('dashboard.php');
