<?php
session_start();
// Painel de autorizações de saque/estorno Asaas
require_once 'includes/db_connect_pagamentos.php';
require_once 'classes/AsaasAPI.php';

// Carrega API Key e ambiente
$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_key'");
$apiKeyRow = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT valor FROM config_pagamentos WHERE chave = 'api_sandbox'");
$sandboxRow = $stmt->fetch(PDO::FETCH_ASSOC);
$apiKey = $apiKeyRow['valor'] ?? '';
$isSandbox = ($sandboxRow['valor'] ?? '0') === '1';

// Busca transferências pendentes via API Asaas
$asaas = new AsaasAPI($apiKey, $isSandbox);
$pendentes = [];
$result = $asaas->listarTransferencias(['status' => 'PENDING']);
if ($result['sucesso']) {
    $pendentes = $result['transferencias'];
}
$result2 = $asaas->listarTransferencias(['status' => 'AWAITING_AUTHORIZATION']);
if ($result2['sucesso']) {
    $pendentes = array_merge($pendentes, $result2['transferencias']);
}

// Carrega logs das tentativas de validação
$logFile = __DIR__ . '/webhook_asaas_log.jsonl';
$logs = [];
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $logs[] = json_decode($line, true);
    }
}

// Fila de autorizações pendentes
$filaFile = __DIR__ . '/autorizacao_saque_fila.jsonl';
$fila = [];
if (file_exists($filaFile)) {
    $lines = file($filaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $item = json_decode($line, true);
        if (isset($item['aprovacao']) && $item['aprovacao'] === null) {
            $fila[] = $item;
        }
    }
}

// Aprovação manual via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && isset($_POST['id'])) {
    $id = $_POST['id'];
    $acao = $_POST['acao'] === 'aprovar';
    // Atualiza a fila
    $newLines = [];
    foreach ($lines as $line) {
        $item = json_decode($line, true);
        if ($item['id'] === $id && $item['aprovacao'] === null) {
            $item['aprovacao'] = $acao;
        }
        $newLines[] = json_encode($item);
    }
    file_put_contents($filaFile, implode("\n", $newLines) . "\n");
    header('Location: painel_autorizacao_saque.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Autorizações de Saque - ERP MTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; }
        .container { max-width: 1100px; margin-top: 40px; }
        .log-table { font-size: 0.95em; }
        .status-aprovado { color: #198754; font-weight: bold; }
        .status-negado { color: #dc3545; font-weight: bold; }
        .status-pendente { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4"><i class="bi bi-shield-check"></i> Painel de Autorizações de Saque/Estorno</h2>
    <h4 class="mt-4 mb-2"><i class="bi bi-hourglass-split"></i> Autorizações Pendentes (Manual)</h4>
    <table class="table table-bordered log-table">
        <thead class="table-warning">
            <tr>
                <th>ID</th>
                <th>Valor</th>
                <th>Solicitante</th>
                <th>Descrição</th>
                <th>Data</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($fila as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['id'] ?? ''); ?></td>
                <td>R$ <?php echo number_format($p['data']['value'] ?? 0, 2, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($p['data']['requester'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['data']['description'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['timestamp'] ?? ''); ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($p['id']); ?>">
                        <button type="submit" name="acao" value="aprovar" class="btn btn-success btn-sm">Aprovar</button>
                        <button type="submit" name="acao" value="negar" class="btn btn-danger btn-sm">Negar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($fila)): ?>
        <div class="alert alert-info">Nenhuma autorização pendente.</div>
    <?php endif; ?>

    <h4 class="mt-4 mb-2"><i class="bi bi-hourglass-split"></i> Transferências Pendentes (API Asaas)</h4>
    <table class="table table-bordered log-table">
        <thead class="table-warning">
            <tr>
                <th>ID</th>
                <th>Valor</th>
                <th>Solicitante</th>
                <th>Status</th>
                <th>Descrição</th>
                <th>Data</th>
                <th>Destinatário</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pendentes as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['id'] ?? ''); ?></td>
                <td>R$ <?php echo number_format($p['value'] ?? 0, 2, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($p['requester'] ?? ''); ?></td>
                <td class="status-pendente"><?php echo htmlspecialchars($p['status'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['description'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['dateCreated'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($p['bankAccount']['ownerName'] ?? ($p['pixAddressKey'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($pendentes)): ?>
        <div class="alert alert-info">Nenhuma transferência pendente encontrada.</div>
    <?php endif; ?>

    <h4 class="mt-4 mb-2"><i class="bi bi-journal-text"></i> Logs das Autorizações Recebidas (Webhook)</h4>
    <table class="table table-bordered log-table">
        <thead class="table-light">
            <tr>
                <th>Data/Hora</th>
                <th>Transferência</th>
                <th>Valor</th>
                <th>Solicitante</th>
                <th>Status</th>
                <th>Motivo</th>
                <th>Dados</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (array_reverse($logs) as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['timestamp'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($log['data']['id'] ?? ''); ?></td>
                <td>R$ <?php echo number_format($log['data']['value'] ?? 0, 2, ',', '.'); ?></td>
                <td><?php echo htmlspecialchars($log['data']['requester'] ?? ''); ?></td>
                <td class="<?php echo ($log['response']['approved'] ?? false) ? 'status-aprovado' : 'status-negado'; ?>">
                    <?php echo ($log['response']['approved'] ?? false) ? 'Aprovado' : 'Negado'; ?>
                </td>
                <td><?php echo htmlspecialchars($log['response']['reason'] ?? ''); ?></td>
                <td><button class="btn btn-sm btn-outline-secondary" onclick="alert(JSON.stringify(<?php echo json_encode($log['data']); ?>, null, 2));">Ver</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (empty($logs)): ?>
        <div class="alert alert-info">Nenhuma tentativa registrada ainda.</div>
    <?php endif; ?>
</div>
</body>
</html>
