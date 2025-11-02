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

// Buscar informações da conta
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/myAccount');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

$conta = json_decode($response, true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Asaas - ERP MTech</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .info-item {
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        .info-item strong {
            display: block;
            color: #666;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .info-item span {
            color: #333;
            font-size: 18px;
            font-weight: 500;
        }
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid;
        }
        .alert.warning {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        .alert.info {
            background: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }
        .alert.success {
            background: #d1fae5;
            border-color: #10b981;
            color: #065f46;
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
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        ul {
            margin: 15px 0 15px 25px;
        }
        ul li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>⚙️ Configurações da Conta Asaas</h1>
            <p class="subtitle">Informações e configurações de taxas</p>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>Tipo de Conta</strong>
                    <span><?= $conta['companyType'] ?? 'N/A' ?></span>
                </div>
                <div class="info-item">
                    <strong>Status</strong>
                    <span><?= $conta['status'] == 'APPROVED' ? '✅ Aprovada' : $conta['status'] ?></span>
                </div>
                <div class="info-item">
                    <strong>CNPJ</strong>
                    <span><?= $conta['cpfCnpj'] ?? 'N/A' ?></span>
                </div>
                <div class="info-item">
                    <strong>Email</strong>
                    <span><?= $conta['email'] ?? 'N/A' ?></span>
                </div>
            </div>
            
            <div class="alert warning">
                <h3 style="margin-bottom: 10px;">⚠️ Taxas Detectadas no Extrato</h3>
                <ul>
                    <li><strong>Taxa do PIX:</strong> R$ 0,99 por recebimento (promoção até 02/02/2026)</li>
                    <li><strong>Taxa de mensageria:</strong> R$ 0,99 por notificação</li>
                </ul>
            </div>
            
            <div class="alert info">
                <h3 style="margin-bottom: 10px;">💡 Como Evitar Taxas</h3>
                <p><strong>Conta <?= $conta['companyType'] ?>:</strong></p>
                <ul>
                    <?php if ($conta['companyType'] == 'MEI'): ?>
                    <li>✅ <strong>PIX Gratuito Ilimitado</strong> para MEI (segundo tabela Asaas)</li>
                    <li>⚠️ Mas você está sendo cobrado R$ 0,99 por recebimento</li>
                    <li>📞 <strong>Entre em contato com o suporte Asaas</strong> para ativar o benefício MEI</li>
                    <?php else: ?>
                    <li>✅ <strong>30 PIX gratuitos por mês</strong> para PJ</li>
                    <li>📧 Solicite ao suporte Asaas para ativar os 30 PIX gratuitos</li>
                    <?php endif; ?>
                    <li>❌ Desabilite notificações automáticas (email, SMS, WhatsApp)</li>
                    <li>✅ Use <code>notificationDisabled: true</code> nas cobranças</li>
                </ul>
            </div>
            
            <div class="alert success">
                <h3 style="margin-bottom: 10px;">✅ Configurações Aplicadas no Sistema</h3>
                <ul>
                    <li><code>notificationDisabled: true</code> - Todas notificações desabilitadas</li>
                    <li><code>postalService: false</code> - Sem envio por correio</li>
                    <li>Economia: R$ 0,99 (email) + R$ 0,55 (WhatsApp) = R$ 1,54 por cobrança</li>
                </ul>
                <p style="margin-top: 15px;"><strong>Mas a taxa de recebimento PIX ainda aparece porque precisa ser configurada no painel Asaas.</strong></p>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>📞 Próximos Passos:</h3>
                <ol style="margin: 15px 0 20px 25px;">
                    <li>Acesse o <a href="https://www.asaas.com" target="_blank" style="color: #667eea;">painel Asaas</a></li>
                    <li>Vá em <strong>Configurações → Notificações</strong></li>
                    <li>Desabilite todas as notificações automáticas</li>
                    <li>Entre em <strong>Suporte → Chat</strong></li>
                    <li>Solicite: <em>"Ativar 30 PIX gratuitos mensais para recebimento (ou ilimitado para MEI)"</em></li>
                    <li>Aguarde confirmação (geralmente em minutos)</li>
                </ol>
                
                <a href="/erp/testar_recebimento_pix.php" class="btn">Testar Novamente</a>
                <a href="/erp/conta_digital.php" class="btn btn-secondary">Ver Saldo</a>
            </div>
        </div>
    </div>
</body>
</html>
