<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /erp/login.php');
    exit;
}

require_once __DIR__ . '/includes/db_connect_pagamentos.php';
require_once __DIR__ . '/classes/AsaasAPI.php';

// Busca configuração do Asaas
$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_key'");
$apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_sandbox'");
$sandboxRow = $stmt->fetch(PDO::FETCH_ASSOC);

$asaas = new AsaasAPI($apiKeyRow['valor'], ($sandboxRow['valor'] ?? '0') == '1');

$mensagem = '';
$cobranca = null;

// Criar cobrança PIX
if (isset($_POST['criar_cobranca'])) {
    $valor = floatval($_POST['valor']);
    $descricao = $_POST['descricao'] ?? 'Teste de recebimento PIX';
    
    $baseUrl = ($sandboxRow['valor'] == '1' ? 'https://sandbox.asaas.com/api/v3' : 'https://api.asaas.com/v3');
    $headers = [
        'access_token: ' . $apiKeyRow['valor'],
        'Content-Type: application/json',
        'User-Agent: ERP-MTech/1.0'
    ];
    
    // Primeiro, criar/buscar cliente
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/customers');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => 'Cliente Teste',
        'cpfCnpj' => '24971563792', // CPF fictício válido
        'email' => 'teste@mtechservicos.com'
    ]));
    
    $responseCliente = curl_exec($ch);
    $clienteData = json_decode($responseCliente, true);
    curl_close($ch);
    
    $customerId = $clienteData['id'] ?? null;
    
    if (!$customerId) {
        // Se não conseguiu criar, tenta buscar existente
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/customers?email=teste@mtechservicos.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $responseCliente = curl_exec($ch);
        $clienteData = json_decode($responseCliente, true);
        curl_close($ch);
        
        $customerId = $clienteData['data'][0]['id'] ?? null;
    }
    
    if (!$customerId) {
        $mensagem = "❌ Erro ao criar cliente. Resposta: " . json_encode($clienteData);
        $cobranca = ['error' => true];
    } else {
        // Agora criar cobrança com o cliente
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => $valor,
            'dueDate' => date('Y-m-d'),
            'description' => $descricao,
            'postalService' => false,           // Desabilita envio por correio
            'notificationDisabled' => true,     // Desabilita TODAS as notificações
            'callback' => [
                'autoRedirect' => false
            ]
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $cobranca = json_decode($response, true);
        
        // Buscar QR Code PIX separadamente
        if (isset($cobranca['id']) && ($httpCode == 200 || $httpCode == 201)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/payments/' . $cobranca['id'] . '/pixQrCode');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $responseQrCode = curl_exec($ch);
            curl_close($ch);
            
            $qrCodeData = json_decode($responseQrCode, true);
            
            if (isset($qrCodeData['encodedImage'])) {
                $cobranca['encodedImage'] = $qrCodeData['encodedImage'];
                $cobranca['payload'] = $qrCodeData['payload'];
            }
        }
    }
    
    if ($httpCode == 200 || $httpCode == 201) {
        $mensagem = "✅ Cobrança criada com sucesso! Cliente ID: " . $customerId;
    } else {
        $mensagem = "❌ Erro ao criar cobrança: " . ($cobranca['errors'][0]['description'] ?? 'Erro desconhecido');
    }
}

// Consultar cobrança
if (isset($_POST['consultar_cobranca'])) {
    $paymentId = $_POST['payment_id'];
    
    $baseUrl = ($sandboxRow['valor'] == '1' ? 'https://sandbox.asaas.com/api/v3' : 'https://api.asaas.com/v3');
    $headers = [
        'access_token: ' . $apiKeyRow['valor'],
        'Content-Type: application/json',
        'User-Agent: ERP-MTech/1.0'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/payments/' . $paymentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $cobranca = json_decode($response, true);
    
    // Buscar QR Code PIX
    if (isset($cobranca['id'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/payments/' . $paymentId . '/pixQrCode');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $responseQrCode = curl_exec($ch);
        curl_close($ch);
        
        $qrCodeData = json_decode($responseQrCode, true);
        
        if (isset($qrCodeData['encodedImage'])) {
            $cobranca['encodedImage'] = $qrCodeData['encodedImage'];
            $cobranca['payload'] = $qrCodeData['payload'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Recebimento PIX - Asaas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .mensagem.sucesso {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .mensagem.erro {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .qrcode-container {
            text-align: center;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .qrcode-container img {
            max-width: 300px;
            border: 3px solid #667eea;
            border-radius: 8px;
        }
        
        .pix-code {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            word-break: break-all;
            font-family: monospace;
            margin: 10px 0;
        }
        
        .btn-copiar {
            background: #10b981;
            margin-top: 10px;
        }
        
        .btn-copiar:hover {
            background: #059669;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin: 10px 0;
        }
        
        .status-badge.pendente {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.pago {
            background: #d1fae5;
            color: #065f46;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        
        .info-item {
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .info-item strong {
            display: block;
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        
        .info-item span {
            color: #333;
            font-size: 16px;
        }
        
        .btn-voltar {
            background: #6b7280;
            margin-right: 10px;
        }
        
        .btn-voltar:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔔 Teste de Recebimento PIX</h1>
            <p class="subtitle">Crie uma cobrança PIX para testar o recebimento via Asaas</p>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
                    <?= $mensagem ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$cobranca): ?>
            <form method="post">
                <div class="form-group">
                    <label>Valor (R$) - Mínimo: R$ 5,00</label>
                    <input type="number" name="valor" step="0.01" min="5.00" value="5.00" required>
                </div>
                
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" rows="3" placeholder="Teste de recebimento PIX">Teste de recebimento PIX</textarea>
                </div>
                
                <button type="submit" name="criar_cobranca">Criar Cobrança PIX</button>
                <a href="/erp/conta_digital.php" class="btn-voltar" style="display:inline-block; text-decoration:none; text-align:center;">Voltar</a>
            </form>
            <?php else: ?>
                <?php if (isset($cobranca['id'])): ?>
                <div class="status-badge <?= $cobranca['status'] == 'RECEIVED' ? 'pago' : 'pendente' ?>">
                    Status: <?= $cobranca['status'] == 'PENDING' ? 'Aguardando Pagamento' : ($cobranca['status'] == 'RECEIVED' ? 'PAGO' : $cobranca['status']) ?>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>ID da Cobrança</strong>
                        <span><?= $cobranca['id'] ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Valor</strong>
                        <span>R$ <?= number_format($cobranca['value'], 2, ',', '.') ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Vencimento</strong>
                        <span><?= date('d/m/Y', strtotime($cobranca['dueDate'])) ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Tipo</strong>
                        <span><?= $cobranca['billingType'] ?></span>
                    </div>
                </div>
                
                <?php if (isset($cobranca['encodedImage'])): ?>
                <div class="qrcode-container">
                    <h3>QR Code PIX</h3>
                    <img src="data:image/png;base64,<?= $cobranca['encodedImage'] ?>" alt="QR Code PIX">
                    
                    <h4 style="margin-top: 20px;">PIX Copia e Cola</h4>
                    <div class="pix-code"><?= $cobranca['payload'] ?></div>
                    <button class="btn-copiar" onclick="copiarPix()">📋 Copiar Código PIX</button>
                </div>
                
                <script>
                function copiarPix() {
                    const pixCode = "<?= $cobranca['payload'] ?>";
                    navigator.clipboard.writeText(pixCode).then(() => {
                        alert('✅ Código PIX copiado!');
                    });
                }
                </script>
                <?php else: ?>
                <div class="mensagem erro">
                    ⚠️ QR Code PIX não disponível. Isso pode acontecer se:
                    <ul style="margin: 10px 0 0 20px;">
                        <li>A conta Asaas ainda não foi totalmente ativada</li>
                        <li>O PIX está em processo de configuração</li>
                        <li>Aguarde alguns minutos e tente novamente</li>
                    </ul>
                </div>
                <details style="margin-top: 20px;">
                    <summary>Ver dados completos da cobrança (debug)</summary>
                    <pre style="background: #f3f4f6; padding: 15px; border-radius: 8px; overflow: auto; max-height: 300px;"><?= print_r($cobranca, true) ?></pre>
                </details>
                <?php endif; ?>
                
                <form method="post" id="formConsultar" style="margin-top: 20px;">
                    <input type="hidden" name="payment_id" value="<?= $cobranca['id'] ?>">
                    <button type="submit" name="consultar_cobranca">🔄 Atualizar Status</button>
                    <a href="/erp/testar_recebimento_pix.php" class="btn-voltar" style="display:inline-block; text-decoration:none; text-align:center;">Nova Cobrança</a>
                    <a href="/erp/conta_digital.php" class="btn-voltar" style="display:inline-block; text-decoration:none; text-align:center;">Ver Saldo</a>
                </form>
                
                <?php if ($cobranca['status'] == 'RECEIVED'): ?>
                    <div class="mensagem sucesso" style="margin-top: 20px;">
                        🎉 <strong>Pagamento confirmado!</strong> O valor já está disponível na sua conta Asaas.
                    </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="mensagem erro">
                    Erro ao processar cobrança. Verifique se você tem um cliente cadastrado no Asaas.
                </div>
                <pre><?= print_r($cobranca, true) ?></pre>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
