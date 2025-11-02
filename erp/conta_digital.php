<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /erp/login.php');
    exit;
}

require_once __DIR__ . '/includes/db_connect_pagamentos.php';
require_once __DIR__ . '/classes/AsaasAPI.php';

// Busca configuração do Asaas
$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave IN ('api_key', 'api_sandbox', 'api_provider')");
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$config = [];
foreach ($configs as $item) {
    $config[$item['chave']] = $item['valor'];
}

// Busca API key e sandbox específicos
$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_key'");
$apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_sandbox'");
$sandboxRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apiKeyRow || empty($apiKeyRow['valor'])) {
    die('Asaas não configurado. Configure em /erp/configurar_asaas.php');
}

// Inicializa API Asaas
$asaas = new AsaasAPI($apiKeyRow['valor'], ($sandboxRow['valor'] ?? '0') == '1');

// Busca saldo e informações da conta
$saldo = $asaas->consultarSaldo();
$transferencias = $asaas->listarTransferencias([
    'dateCreated[ge]' => date('Y-m-01'), // Primeiro dia do mês
    'limit' => 10
]);

// Estatísticas do mês
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'PAGO' THEN 1 END) as total_pagos,
        COALESCE(SUM(CASE WHEN status = 'PAGO' THEN valor_total ELSE 0 END), 0) as valor_pago,
        COUNT(CASE WHEN status = 'AGUARDANDO' THEN 1 END) as total_pendentes,
        COALESCE(SUM(CASE WHEN status = 'AGUARDANDO' THEN valor_total ELSE 0 END), 0) as valor_pendente
    FROM lotes_pagamento
    WHERE EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE)
      AND EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM CURRENT_DATE)
");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta Digital Asaas - ERP MTech</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-voltar {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-voltar:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .saldo-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }
        
        .saldo-label {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .saldo-valor {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .saldo-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .saldo-info-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .saldo-info-item label {
            font-size: 12px;
            opacity: 0.9;
            display: block;
            margin-bottom: 5px;
        }
        
        .saldo-info-item strong {
            font-size: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 500;
        }
        
        .stat-card .valor {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-card .descricao {
            color: #999;
            font-size: 13px;
        }
        
        .stat-card.verde .valor { color: #10b981; }
        .stat-card.amarelo .valor { color: #f59e0b; }
        .stat-card.azul .valor { color: #3b82f6; }
        .stat-card.roxo .valor { color: #8b5cf6; }
        
        .transferencias-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .transferencias-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .transferencia-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .transferencia-item:last-child {
            border-bottom: none;
        }
        
        .transferencia-info {
            flex: 1;
        }
        
        .transferencia-descricao {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }
        
        .transferencia-data {
            font-size: 13px;
            color: #999;
        }
        
        .transferencia-valor {
            font-size: 20px;
            font-weight: bold;
        }
        
        .transferencia-valor.saida {
            color: #ef4444;
        }
        
        .transferencia-valor.entrada {
            color: #10b981;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .refresh-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M7 15h0M2 9.5h20"/>
                </svg>
                Conta Digital Asaas
            </h1>
            <a href="/erp/pagamentos_agrupados.php" class="btn-voltar">← Voltar para Pagamentos</a>
        </div>
        
        <?php if ($saldo['sucesso']): ?>
        <div class="saldo-card">
            <div class="saldo-label">Saldo Disponível</div>
            <div class="saldo-valor">R$ <?= number_format($saldo['saldo'], 2, ',', '.') ?></div>
            
            <div class="saldo-info">
                <div class="saldo-info-item">
                    <label>Saldo Bloqueado</label>
                    <strong>R$ <?= number_format($saldo['saldo_bloqueado'] ?? 0, 2, ',', '.') ?></strong>
                </div>
                <div class="saldo-info-item">
                    <label>Total na Conta</label>
                    <strong>R$ <?= number_format($saldo['saldo'] + ($saldo['saldo_bloqueado'] ?? 0), 2, ',', '.') ?></strong>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="saldo-card">
            <div class="saldo-label">⚠️ Erro ao consultar saldo</div>
            <div style="font-size: 16px; margin-top: 10px;">
                <?= htmlspecialchars($saldo['erro'] ?? 'Erro desconhecido') ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card verde">
                <h3>Pagos este mês</h3>
                <div class="valor"><?= $stats['total_pagos'] ?></div>
                <div class="descricao">R$ <?= number_format($stats['valor_pago'], 2, ',', '.') ?></div>
            </div>
            
            <div class="stat-card amarelo">
                <h3>Pendentes</h3>
                <div class="valor"><?= $stats['total_pendentes'] ?></div>
                <div class="descricao">R$ <?= number_format($stats['valor_pendente'], 2, ',', '.') ?></div>
            </div>
            
            <div class="stat-card azul">
                <h3>Total Movimentado</h3>
                <div class="valor">R$ <?= number_format($stats['valor_pago'], 2, ',', '.') ?></div>
                <div class="descricao">Últimos 30 dias</div>
            </div>
            
            <div class="stat-card roxo">
                <h3>Média por Pagamento</h3>
                <div class="valor">
                    R$ <?= $stats['total_pagos'] > 0 ? number_format($stats['valor_pago'] / $stats['total_pagos'], 2, ',', '.') : '0,00' ?>
                </div>
                <div class="descricao">Valor médio</div>
            </div>
        </div>
        
        <div class="transferencias-section">
            <h2>Últimas Transferências</h2>
            <button class="refresh-btn" onclick="location.reload()">
                🔄 Atualizar
            </button>
            
            <?php if ($transferencias['sucesso'] && !empty($transferencias['transferencias'])): ?>
                <?php foreach ($transferencias['transferencias'] as $trans): ?>
                <div class="transferencia-item">
                    <div class="transferencia-info">
                        <div class="transferencia-descricao">
                            <?= htmlspecialchars($trans['description'] ?? 'Transferência PIX') ?>
                            <span class="badge <?= $trans['status'] === 'DONE' ? 'success' : ($trans['status'] === 'PENDING' ? 'pending' : 'error') ?>">
                                <?= $trans['status'] === 'DONE' ? 'Concluído' : ($trans['status'] === 'PENDING' ? 'Pendente' : $trans['status']) ?>
                            </span>
                        </div>
                        <div class="transferencia-data">
                            <?= date('d/m/Y H:i', strtotime($trans['dateCreated'])) ?> 
                            • <?= $trans['operationType'] === 'PIX' ? 'PIX' : 'TED' ?>
                        </div>
                    </div>
                    <div class="transferencia-valor saida">
                        - R$ <?= number_format($trans['value'], 2, ',', '.') ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php elseif (!$transferencias['sucesso']): ?>
                <div class="empty-state">
                    <p style="color: #ef4444;">⚠️ <?= htmlspecialchars($transferencias['erro']) ?></p>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p>Nenhuma transferência encontrada neste mês</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
