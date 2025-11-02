<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /erp/login.php');
    exit;
}

require_once __DIR__ . '/includes/db_connect_pagamentos.php';

$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_key'");
$apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_sandbox'");
$sandboxRow = $stmt->fetch(PDO::FETCH_ASSOC);

$apiKey = $apiKeyRow['valor'];
$baseUrl = ($sandboxRow['valor'] == '1' ? 'https://sandbox.asaas.com/api/v3' : 'https://api.asaas.com/v3');
$headers = [
    'access_token: ' . $apiKey,
    'Content-Type: application/json',
    'User-Agent: ERP-MTech/1.0'
];

$mensagem = '';
$chaves = [];

// Criar chave PIX aleatória (EVP)
if (isset($_POST['criar_chave'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/pix/addressKeys');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'type' => 'EVP'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode == 200 || $httpCode == 201) {
        $mensagem = "✅ Chave PIX criada com sucesso!";
    } else {
        $mensagem = "❌ Erro ao criar chave PIX: " . ($result['errors'][0]['description'] ?? 'Erro desconhecido');
    }
}

// Listar chaves PIX
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/pix/addressKeys');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$chaves = $result['data'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chaves PIX - Asaas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
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
        .chave-item {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }
        .chave-item strong {
            display: block;
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .chave-item .chave-valor {
            font-family: monospace;
            font-size: 16px;
            color: #333;
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            word-break: break-all;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #10b981;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .alert {
            padding: 20px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            border-radius: 8px;
            margin: 20px 0;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }
        .badge.ativa {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔑 Chaves PIX para Recebimento</h1>
            <p class="subtitle">Gerencie suas chaves PIX no Asaas</p>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?= strpos($mensagem, '✅') !== false ? 'sucesso' : 'erro' ?>">
                    <?= $mensagem ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($chaves)): ?>
                <div class="alert">
                    <strong>⚠️ Nenhuma chave PIX cadastrada</strong>
                    <p style="margin-top: 10px;">Você precisa criar uma chave PIX para poder receber pagamentos via PIX no Asaas.</p>
                </div>
                
                <div class="empty-state">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" style="margin-bottom: 20px;">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <path d="M2 10h20"/>
                    </svg>
                    <h3 style="color: #333; margin-bottom: 10px;">Criar Chave PIX Aleatória</h3>
                    <p style="margin-bottom: 20px;">Uma chave aleatória (EVP) será gerada automaticamente pelo Asaas.</p>
                    
                    <form method="post">
                        <button type="submit" name="criar_chave" class="btn btn-success">✨ Criar Chave PIX</button>
                        <a href="/erp/conta_digital.php" class="btn btn-secondary">Voltar</a>
                    </form>
                </div>
            <?php else: ?>
                <h3 style="margin-bottom: 15px;">Suas Chaves PIX:</h3>
                
                <?php foreach ($chaves as $chave): ?>
                <div class="chave-item">
                    <strong>Tipo: <?= $chave['addressKeyType'] ?></strong>
                    <span class="badge ativa">Ativa</span>
                    
                    <div class="chave-valor">
                        <?= htmlspecialchars($chave['addressKey']) ?>
                    </div>
                    
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">
                        Criada em: <?= date('d/m/Y H:i', strtotime($chave['dateCreated'])) ?>
                    </p>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 30px;">
                    <form method="post" style="display: inline;">
                        <button type="submit" name="criar_chave" class="btn">➕ Criar Nova Chave</button>
                    </form>
                    <a href="/erp/testar_recebimento_pix.php" class="btn btn-success">Testar Recebimento</a>
                    <a href="/erp/conta_digital.php" class="btn btn-secondary">Voltar</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
