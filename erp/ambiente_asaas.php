<?php
require_once __DIR__ . '/includes/db_connect_pagamentos.php';

echo "<h2>Configuração Sandbox Asaas</h2>";

// Verificar estado atual
$stmt = $pdo->query("SELECT chave, valor FROM config_pagamentos WHERE chave IN ('api_sandbox', 'api_key')");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Estado Atual:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Chave</th><th>Valor</th></tr>";
foreach ($configs as $c) {
    echo "<tr><td>{$c['chave']}</td><td>" . ($c['chave'] == 'api_key' ? substr($c['valor'], 0, 30) . '...' : $c['valor']) . "</td></tr>";
}
echo "</table>";

// Atualizar para sandbox
if (isset($_POST['ativar_sandbox'])) {
    $pdo->exec("UPDATE config_pagamentos SET valor = '1' WHERE chave = 'api_sandbox'");
    echo "<p style='color: green;'><strong>✅ Sandbox ATIVADO</strong></p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

// Atualizar para produção
if (isset($_POST['ativar_producao'])) {
    $pdo->exec("UPDATE config_pagamentos SET valor = '0' WHERE chave = 'api_sandbox'");
    echo "<p style='color: green;'><strong>✅ Produção ATIVADA</strong></p>";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

$sandboxAtivo = false;
foreach ($configs as $c) {
    if ($c['chave'] == 'api_sandbox' && $c['valor'] == '1') {
        $sandboxAtivo = true;
    }
}

echo "<hr>";
echo "<h3>Ambiente:</h3>";
echo "<p><strong>Status:</strong> " . ($sandboxAtivo ? "🧪 SANDBOX" : "🚀 PRODUÇÃO") . "</p>";
echo "<p><strong>URL Base:</strong> " . ($sandboxAtivo ? "https://sandbox.asaas.com/api/v3" : "https://api.asaas.com/v3") . "</p>";

if ($sandboxAtivo) {
    echo "<p style='color: orange;'><strong>⚠️ Você está no ambiente de TESTES (Sandbox)</strong></p>";
    echo "<p>Chave PIX Sandbox: <code>b2e3b4e8-a061-4e1b-93b9-a8394923ff70</code></p>";
} else {
    echo "<p style='color: green;'><strong>✅ Você está no ambiente de PRODUÇÃO</strong></p>";
    echo "<p>Transações reais serão processadas!</p>";
}

echo "<hr>";
echo "<form method='post'>";
if ($sandboxAtivo) {
    echo "<button type='submit' name='ativar_producao' style='padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer;'>🚀 Ativar Produção</button>";
} else {
    echo "<button type='submit' name='ativar_sandbox' style='padding: 10px 20px; background: #f59e0b; color: white; border: none; border-radius: 5px; cursor: pointer;'>🧪 Ativar Sandbox</button>";
}
echo "</form>";

echo "<hr>";
echo "<p><a href='/erp/chaves_pix.php'>🔑 Gerenciar Chaves PIX</a> | ";
echo "<a href='/erp/testar_recebimento_pix.php'>💰 Testar Recebimento</a> | ";
echo "<a href='/erp/conta_digital.php'>📊 Ver Saldo</a></p>";
?>
