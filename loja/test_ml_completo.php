<?php
require_once 'config.php';

echo "<h1>🔍 Diagnóstico Completo Mercado Livre</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .erro{color:red;} .aviso{color:orange;} pre{background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;}</style>";

// 1. Verifica Token (busca qualquer usuário com token)
echo "<h2>1. Token de Acesso</h2>";

try {
    $db = getDB();
    echo "<p class='ok'>✅ Conexão com banco OK</p>";
} catch (Exception $e) {
    echo "<p class='erro'>❌ Erro ao conectar no banco: " . $e->getMessage() . "</p>";
    exit;
}

try {
    $stmt = $db->query("SELECT usuario_id, access_token, refresh_token, expires_in, data_criacao, data_expiracao FROM ml_tokens ORDER BY data_criacao DESC LIMIT 1");
    $tokenData = $stmt->fetch();
} catch (Exception $e) {
    echo "<p class='erro'>❌ Erro ao buscar token: " . $e->getMessage() . "</p>";
    exit;
}

if (!$tokenData) {
    echo "<p class='erro'>❌ Token não encontrado. <a href='conectar_ml.php'>Conectar Mercado Livre</a></p>";
    exit;
}

$token = $tokenData['access_token'];
$dataExpiracao = $tokenData['data_expiracao'];
$dataCriacao = $tokenData['data_criacao'];
$isExpired = strtotime($dataExpiracao) < time();

echo "<p class='ok'>✅ Token encontrado</p>";
echo "<p>Criado em: " . date('d/m/Y H:i:s', strtotime($dataCriacao)) . "</p>";
echo "<p>Expira em: " . date('d/m/Y H:i:s', strtotime($dataExpiracao)) . " " . ($isExpired ? "<span class='erro'>(EXPIRADO!)</span>" : "<span class='ok'>(Válido)</span>") . "</p>";

// 2. Testa API /users/me
echo "<h2>2. Informações do Usuário ML</h2>";
$ch = curl_init(ML_API_URL . '/users/me');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: <strong>$httpCode</strong></p>";

if ($httpCode == 200) {
    $userData = json_decode($response, true);
    echo "<p class='ok'>✅ User ID: " . $userData['id'] . "</p>";
    echo "<p class='ok'>✅ Nickname: " . $userData['nickname'] . "</p>";
    echo "<p class='ok'>✅ Seller Experience: " . $userData['seller_experience'] . "</p>";
    $userId = $userData['id'];
} else {
    echo "<p class='erro'>❌ Erro ao buscar dados do usuário</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    exit;
}

// 3. Testa diferentes endpoints de produtos
echo "<h2>3. Testando Endpoints de Produtos</h2>";

// Teste 1: API pública SEM autenticação
echo "<h3>Teste A: API Pública (sem token)</h3>";
$url1 = ML_API_URL . '/sites/MLB/search?seller_id=' . $userId;
echo "<p>URL: <code>$url1</code></p>";

$ch = curl_init($url1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: <strong>$httpCode1</strong></p>";
if ($httpCode1 == 200) {
    $data1 = json_decode($response1, true);
    $total1 = isset($data1['paging']['total']) ? $data1['paging']['total'] : 0;
    echo "<p class='ok'>✅ Total de anúncios encontrados: <strong>$total1</strong></p>";
    
    if ($total1 > 0 && isset($data1['results'])) {
        echo "<h4>Primeiros 3 anúncios:</h4>";
        foreach (array_slice($data1['results'], 0, 3) as $item) {
            echo "<p>• <strong>" . $item['title'] . "</strong> - " . $item['currency_id'] . " " . $item['price'] . " (Status: " . $item['status'] . ")</p>";
        }
    }
} else {
    echo "<p class='erro'>❌ Erro $httpCode1</p>";
    echo "<pre>" . htmlspecialchars(substr($response1, 0, 500)) . "</pre>";
}

// Teste 2: /users/{id}/items/search COM autenticação
echo "<h3>Teste B: API Privada (com token)</h3>";
$url2 = ML_API_URL . '/users/' . $userId . '/items/search';
echo "<p>URL: <code>$url2</code></p>";

$ch = curl_init($url2);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: <strong>$httpCode2</strong></p>";
if ($httpCode2 == 200) {
    $data2 = json_decode($response2, true);
    echo "<p class='ok'>✅ Resposta recebida</p>";
    echo "<pre>" . htmlspecialchars(json_encode($data2, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p class='erro'>❌ Erro $httpCode2</p>";
    echo "<pre>" . htmlspecialchars($response2) . "</pre>";
}

// Teste 3: Com filtro status=active
echo "<h3>Teste C: API com filtro status=active</h3>";
$url3 = ML_API_URL . '/users/' . $userId . '/items/search?status=active';
echo "<p>URL: <code>$url3</code></p>";

$ch = curl_init($url3);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: <strong>$httpCode3</strong></p>";
if ($httpCode3 == 200) {
    $data3 = json_decode($response3, true);
    echo "<p class='ok'>✅ Resposta recebida</p>";
    echo "<pre>" . htmlspecialchars(json_encode($data3, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p class='erro'>❌ Erro $httpCode3</p>";
    echo "<pre>" . htmlspecialchars($response3) . "</pre>";
}

// 4. Resumo e Recomendação
echo "<h2>4. 📋 Resumo e Recomendação</h2>";

if ($httpCode1 == 200 && $total1 > 0) {
    echo "<p class='ok'>✅ <strong>SOLUÇÃO ENCONTRADA!</strong></p>";
    echo "<p>A API pública funciona e encontrou <strong>$total1 anúncios</strong>.</p>";
    echo "<p>🔧 <strong>Ação:</strong> O sistema já está configurado para usar a API pública.</p>";
    echo "<p><a href='sincronizar.php' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>🔄 Sincronizar Produtos Agora</a></p>";
} elseif ($httpCode1 == 403 || $httpCode2 == 403 || $httpCode3 == 403) {
    echo "<p class='erro'>❌ <strong>BLOQUEIO DE API</strong></p>";
    echo "<p>Suas APIs estão bloqueadas com erro 403. Possíveis causas:</p>";
    echo "<ul>";
    echo "<li>🔸 Conta nova sem histórico de vendas</li>";
    echo "<li>🔸 IP do servidor bloqueado temporariamente</li>";
    echo "<li>🔸 Anúncios estão pausados ou inativos</li>";
    echo "<li>🔸 Aplicação ML precisa de aprovação especial</li>";
    echo "</ul>";
    echo "<p><strong>Solução:</strong></p>";
    echo "<ol>";
    echo "<li>Verifique se seus anúncios estão <strong>ativos</strong>: <a href='https://www.mercadolivre.com.br/vendas/listagem' target='_blank'>Ver anúncios</a></li>";
    echo "<li>Aguarde 24-48h (bloqueios temporários)</li>";
    echo "<li>Entre em contato com suporte ML se persistir</li>";
    echo "</ol>";
} else {
    echo "<p class='aviso'>⚠️ Resultados inconclusivos. Verifique os detalhes acima.</p>";
}

echo "<hr><p><a href='dashboard.php'>← Voltar ao Dashboard</a></p>";
