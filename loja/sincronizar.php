<?php
require_once 'config.php';
require_once 'classes/MercadoLivreAPI.php';

// Verifica se está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

// Sincroniza produtos e transações
$ml = new MercadoLivreAPI($_SESSION['user_id']);

try {
    $produtosSynced = $ml->syncProducts();
    $transacoesSynced = $ml->syncTransactions();
    
    redirect('dashboard.php?sync_success=1&produtos=' . $produtosSynced);
} catch (Exception $e) {
    error_log("Erro na sincronização: " . $e->getMessage());
    redirect('dashboard.php?sync_error=1');
}
