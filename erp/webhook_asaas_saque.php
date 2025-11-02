<?php
// Webhook de validação de saque Asaas
// Documentação: https://docs.asaas.com/docs/webhooks-de-aprovacao

// Configurações
$filaFile = __DIR__ . '/autorizacao_saque_fila.jsonl';
$logFile = __DIR__ . '/webhook_asaas_log.jsonl';
$configFile = __DIR__ . '/webhook_config.json';

// LOG COMPLETO PARA DEBUG
$debugLog = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'raw_input' => file_get_contents('php://input'),
    'server' => [
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]
];
file_put_contents(__DIR__ . '/webhook_debug.jsonl', json_encode($debugLog) . "\n", FILE_APPEND);

// PÁGINA DE CONFIGURAÇÃO (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Carrega ou cria configurações
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
    } else {
        $config = [
            'auto_approve' => true, // Aprovar automaticamente
            'webhook_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    // Salva alterações (se enviado via POST na URL)
    if (isset($_GET['action']) && $_GET['action'] === 'toggle_auto') {
        $config['auto_approve'] = !($config['auto_approve'] ?? false);
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Configuração Webhook Asaas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4><i class="bi bi-webhook"></i> Configuração Webhook Asaas</h4>
                </div>
                <div class="card-body">
                    <h5>URL do Webhook</h5>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($config['webhook_url']); ?>" readonly>
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('<?php echo $config['webhook_url']; ?>')">
                            <i class="bi bi-clipboard"></i> Copiar
                        </button>
                    </div>

                    <div class="alert alert-info">
                        <strong><i class="bi bi-info-circle"></i> Como configurar no Asaas:</strong>
                        <ol>
                            <li>Acesse o painel Asaas → Integrações → Webhooks</li>
                            <li>Crie um webhook para <strong>Aprovação de Transferências</strong></li>
                            <li>Cole a URL acima no campo "URL do webhook"</li>
                            <li>Não é necessário token de autenticação (o Asaas valida por IP)</li>
                        </ol>
                    </div>

                    <h5 class="mt-4">Modo de Aprovação</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoApprove"
                            <?php echo $config['auto_approve'] ? 'checked' : ''; ?>
                            onchange="window.location.href='?action=toggle_auto'">
                        <label class="form-check-label" for="autoApprove">
                            <strong>Aprovação Automática</strong>
                            <?php if ($config['auto_approve']): ?>
                                <span class="badge bg-success">ATIVADO</span>
                                <p class="text-muted small mb-0">Todas as transferências serão aprovadas automaticamente</p>
                            <?php else: ?>
                                <span class="badge bg-warning">DESATIVADO</span>
                                <p class="text-muted small mb-0">Transferências precisarão de aprovação manual no painel</p>
                            <?php endif; ?>
                        </label>
                    </div>

                    <div class="mt-4">
                        <a href="painel_autorizacao_saque.php" class="btn btn-primary">
                            <i class="bi bi-shield-check"></i> Ir para Painel de Autorização
                        </a>
                        <a href="testar_envio_pix.php" class="btn btn-outline-secondary">
                            <i class="bi bi-send"></i> Testar Envio PIX
                        </a>
                    </div>
                </div>
            </div>

            <!-- Logs Recentes -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h5><i class="bi bi-journal-text"></i> Últimas Requisições (Debug)</h5>
                </div>
                <div class="card-body">
                    <?php
                    $debugLogFile = __DIR__ . '/webhook_debug.jsonl';
                    if (file_exists($debugLogFile)) {
                        $logs = file($debugLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        $logs = array_slice(array_reverse($logs), 0, 10); // Últimas 10
                        foreach ($logs as $log) {
                            $entry = json_decode($log, true);
                            echo '<div class="border-bottom pb-2 mb-2">';
                            echo '<strong>' . $entry['timestamp'] . '</strong> - ';
                            echo '<code>' . $entry['method'] . '</code> de <code>' . $entry['server']['REMOTE_ADDR'] . '</code><br>';
                            if (!empty($entry['raw_input'])) {
                                echo '<small>Payload: <code>' . htmlspecialchars(substr($entry['raw_input'], 0, 100)) . '...</code></small>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted">Nenhuma requisição recebida ainda.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// VALIDAÇÃO BÁSICA (webhook POST do Asaas)
// O Asaas valida webhooks por IP, não por token
// IPs permitidos: veja https://docs.asaas.com/docs/ips-dos-servidores

// Recebe dados do POST
$data = json_decode(file_get_contents('php://input'), true);

// Carrega configurações
$config = file_exists($configFile)
    ? json_decode(file_get_contents($configFile), true)
    : ['auto_approve' => true];

// Extrai informações da transferência
$id = $data['id'] ?? null;
$valor = $data['value'] ?? 0;
$descricao = $data['description'] ?? '';
$solicitante = $data['requester'] ?? '';

// VALIDAÇÃO: transferência deve ter ID válido
if (empty($id)) {
    http_response_code(400);
    $response = [
        'approved' => false,
        'reason' => 'ID da transferência não fornecido'
    ];

    // Log da resposta
    file_put_contents($logFile, json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data,
        'response' => $response
    ]) . "\n", FILE_APPEND);

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Verifica se já existe decisão manual para esta transferência
$aprovacaoManual = null;
if (file_exists($filaFile)) {
    $lines = file($filaFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $item = json_decode($line, true);
        if ($item['id'] === $id && isset($item['aprovacao']) && $item['aprovacao'] !== null) {
            $aprovacaoManual = $item['aprovacao'];
            break;
        }
    }
}

// DECISÃO DE APROVAÇÃO
$aprovado = false;
$motivo = '';

if ($aprovacaoManual !== null) {
    // Prioridade 1: Decisão manual já existe
    $aprovado = $aprovacaoManual === true;
    $motivo = $aprovado
        ? 'Aprovado manualmente pelo ERP'
        : 'Negado manualmente pelo ERP';

} elseif ($config['auto_approve'] ?? false) {
    // Prioridade 2: Aprovação automática está ativada
    $aprovado = true;
    $motivo = 'Aprovado automaticamente pelo ERP';

    // Registra na fila como aprovado automaticamente
    $fila = [
        'id' => $id,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data,
        'aprovacao' => true,
        'tipo' => 'automatica'
    ];
    file_put_contents($filaFile, json_encode($fila) . "\n", FILE_APPEND);

} else {
    // Prioridade 3: Aguarda aprovação manual
    $aprovado = false;
    $motivo = 'Aguardando aprovação manual no painel';

    // Registra como pendente na fila
    $fila = [
        'id' => $id,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data,
        'aprovacao' => null,
        'tipo' => 'manual_pendente'
    ];
    file_put_contents($filaFile, json_encode($fila) . "\n", FILE_APPEND);

    // Retorna HTTP 202 (Accepted) para indicar que está pendente
    http_response_code(202);
}

// Monta resposta
$response = [
    'approved' => $aprovado,
    'reason' => $motivo
];

// Log completo da decisão
$logEntry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'transfer_id' => $id,
    'value' => $valor,
    'description' => $descricao,
    'requester' => $solicitante,
    'data' => $data,
    'response' => $response,
    'config_mode' => $config['auto_approve'] ? 'automatico' : 'manual'
];
file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);

// Responde ao Asaas
header('Content-Type: application/json');
echo json_encode($response);
