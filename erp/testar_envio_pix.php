<?php
session_start();

// Habilita exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect_pagamentos.php';
require_once 'classes/AsaasAPI.php';

// Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$erro = '';
$sucesso = '';
$aguardandoAutorizacao = false;
$transferenciaId = '';
$pixKey = '';
$valor = '';
$descricao = '';

// Busca configurações
$stmt = $pdo->query("SELECT chave, valor FROM config_pagamentos WHERE chave IN ('api_key', 'api_sandbox')");
$config = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $config[$row['chave']] = $row['valor'];
}

$apiKey = $config['api_key'] ?? '';
$isSandbox = ($config['api_sandbox'] ?? '0') === '1';

// Processa reenvio de token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reenviar_token'])) {
    $transferenciaId = trim($_POST['transferencia_id']);
    
    try {
        $asaas = new AsaasAPI($apiKey, $isSandbox);
        $resultado = $asaas->solicitarTokenAutorizacao($transferenciaId);
        
        if ($resultado['sucesso']) {
            $sucesso = "✅ Token reenviado com sucesso! Verifique seu celular.";
            $aguardandoAutorizacao = true;
        } else {
            $erro = "❌ " . $resultado['erro'];
            if (isset($resultado['detalhes'])) {
                $erro .= "<br><small>Detalhes: " . json_encode($resultado['detalhes']) . "</small>";
                $endpointDebug = ($isSandbox ? 'https://sandbox.asaas.com/api/v3' : 'https://api.asaas.com/v3') . "/transfers/" . $transferenciaId . "/requestAuthorizationToken";
                $erro .= "<br><strong>Endpoint chamado:</strong> $endpointDebug";
            }
        }
    } catch (Exception $e) {
        $erro = "❌ Erro: " . $e->getMessage();
    }
}

// Processa envio de PIX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_pix'])) {
    $pixKey = trim($_POST['pix_key']);
    $valor = floatval($_POST['valor']);
    $descricao = trim($_POST['descricao']);
    
    // Validações
    if (empty($pixKey)) {
        $erro = "Chave PIX é obrigatória";
    } elseif ($valor <= 0) {
        $erro = "Valor deve ser maior que zero";
    } else {
        try {
            $asaas = new AsaasAPI($apiKey, $isSandbox);
            
            // Detecta tipo da chave PIX
            $tipoChave = 'EVP'; // Padrão
            if (preg_match('/^\d{11}$/', preg_replace('/\D/', '', $pixKey))) {
                $tipoChave = 'CPF';
            } elseif (preg_match('/^\d{14}$/', preg_replace('/\D/', '', $pixKey))) {
                $tipoChave = 'CNPJ';
            } elseif (filter_var($pixKey, FILTER_VALIDATE_EMAIL)) {
                $tipoChave = 'EMAIL';
            } elseif (preg_match('/^\+?55\d{10,11}$/', preg_replace('/\D/', '', $pixKey))) {
                $tipoChave = 'PHONE';
            }
            
            // Envia PIX
            $resultado = $asaas->enviarPIX([
                'valor' => $valor,
                'chave_pix' => $pixKey,
                'tipo_chave' => $tipoChave,
                'descricao' => $descricao ?: 'Pagamento teste via ERP'
            ]);
            
            if ($resultado['sucesso']) {
                $transferenciaId = $resultado['dados']['id'] ?? '';
                $status = $resultado['dados']['status'] ?? '';
                
                // Verifica se precisa de autorização
                if ($status === 'PENDING' || $status === 'AWAITING_AUTHORIZATION') {
                    // Solicita envio do token automaticamente
                    $tokenResult = $asaas->solicitarTokenAutorizacao($transferenciaId);
                    
                    $aguardandoAutorizacao = true;
                    $sucesso = "⏳ Transferência criada! Aguardando autorização.<br>";
                    $sucesso .= "ID da transferência: <strong>" . $transferenciaId . "</strong><br>";
                    $sucesso .= "Status: " . $status . "<br><br>";
                    
                    if ($tokenResult['sucesso']) {
                        $sucesso .= "✅ <strong>Token enviado para seu celular!</strong><br>";
                        $sucesso .= "📱 Verifique seu <strong>SMS</strong> para obter o código de 6 dígitos.<br>";
                    } else {
                        $sucesso .= "⚠️ Não foi possível enviar o token automaticamente.<br>";
                        $sucesso .= "Erro: " . $tokenResult['erro'] . "<br>";
                    }
                    
                    $sucesso .= "<br>Digite o código no campo abaixo para confirmar o envio.";
                } else {
                    $sucesso = "✅ PIX enviado com sucesso!<br>";
                    $sucesso .= "ID da transferência: " . $transferenciaId . "<br>";
                    $sucesso .= "Status: " . $status . "<br>";
                    $sucesso .= "Valor: R$ " . number_format($valor, 2, ',', '.') . "<br>";
                    
                    if (isset($resultado['dados']['scheduleDate'])) {
                        $sucesso .= "Agendado para: " . $resultado['dados']['scheduleDate'];
                    }
                }
                
            } else {
                $erro = "❌ Erro ao enviar PIX: " . ($resultado['erro'] ?? 'Erro desconhecido');
            }
            
        } catch (Exception $e) {
            $erro = "❌ Erro: " . $e->getMessage();
        }
    }
}

// Busca saldo atual
$saldo = 0;
try {
    $asaas = new AsaasAPI($apiKey, $isSandbox);
    $resultadoSaldo = $asaas->consultarSaldo();
    if ($resultadoSaldo['sucesso']) {
        $saldo = $resultadoSaldo['dados']['balance'] ?? 0;
    }
} catch (Exception $e) {
    $saldo = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testar Envio PIX - ERP MTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-enviar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .saldo-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .saldo-valor {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .ambiente-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.8em;
            padding: 5px 10px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .pix-key-examples {
            font-size: 0.85em;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="card">
            <span class="badge <?php echo $isSandbox ? 'bg-warning' : 'bg-success'; ?> ambiente-badge">
                <?php echo $isSandbox ? '🧪 SANDBOX' : '🚀 PRODUÇÃO'; ?>
            </span>
            
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-send-fill"></i> Testar Envio PIX
                </h4>
            </div>
            
            <div class="card-body p-4">
                <!-- Saldo Disponível -->
                <div class="saldo-box">
                    <div class="text-muted mb-1">Saldo Disponível</div>
                    <div class="saldo-valor">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></div>
                    <?php if ($saldo <= 0): ?>
                        <small class="text-danger">⚠️ Saldo insuficiente para envio</small>
                    <?php endif; ?>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger">
                        <?php echo $erro; ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success">
                        <?php echo $sucesso; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário de Autorização (aparece quando necessário) -->
                <?php if ($aguardandoAutorizacao && $transferenciaId): ?>
                    <div class="card border-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title text-info">
                                <i class="bi bi-clock-history"></i> Aguardando Autorização
                            </h5>
                            <p class="mb-2">
                                <strong>ID da Transferência:</strong> <code><?php echo htmlspecialchars($transferenciaId); ?></code>
                            </p>
                            <div class="alert alert-warning mb-2">
                                <strong><i class="bi bi-exclamation-triangle"></i> Atenção:</strong><br>
                                O Asaas irá chamar seu webhook para autorização da transferência.<br>
                                Certifique-se de que:
                                <ul class="mb-0 mt-2">
                                    <li>O webhook está configurado corretamente no painel Asaas</li>
                                    <li>A URL do webhook está acessível publicamente</li>
                                    <li>A aprovação automática está ativada (ou aprove manualmente)</li>
                                </ul>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="webhook_asaas_saque.php" class="btn btn-primary" target="_blank">
                                    <i class="bi bi-gear"></i> Configurar Webhook
                                </a>
                                <a href="painel_autorizacao_saque.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-shield-check"></i> Ver Painel de Autorização
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulário de Envio (esconde quando aguardando autorização) -->
                <?php if (!$aguardandoAutorizacao): ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-key-fill"></i> Chave PIX de Destino
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="pix_key" 
                            value="<?php echo htmlspecialchars($pixKey); ?>"
                            placeholder="CPF, CNPJ, E-mail, Celular ou Chave Aleatória"
                            required
                        >
                        <div class="pix-key-examples">
                            Exemplos: 123.456.789-00 | email@example.com | +5511999999999 | chave-aleatoria-uuid
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-cash-coin"></i> Valor (R$)
                        </label>
                        <input 
                            type="number" 
                            class="form-control" 
                            name="valor" 
                            value="<?php echo $valor ?: '0.01'; ?>"
                            min="0.01" 
                            step="0.01"
                            required
                        >
                        <small class="text-muted">Qualquer valor acima de R$ 0,01</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-chat-left-text-fill"></i> Descrição (Opcional)
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="descricao" 
                            value="<?php echo htmlspecialchars($descricao); ?>"
                            placeholder="Ex: Pagamento fornecedor"
                            maxlength="100"
                        >
                    </div>

                    <div class="d-grid gap-2">
                        <button 
                            type="submit" 
                            name="enviar_pix" 
                            class="btn btn-enviar btn-lg"
                            <?php echo ($saldo <= 0) ? 'disabled' : ''; ?>
                        >
                            <i class="bi bi-send-check-fill"></i> Enviar PIX
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- Informações Importantes -->
                <div class="alert alert-info mt-4 mb-0">
                    <h6><i class="bi bi-info-circle-fill"></i> Informações Importantes:</h6>
                    <ul class="mb-0" style="font-size: 0.9em;">
                        <li><strong>Sandbox:</strong> Use chaves PIX de teste fornecidas pela Asaas</li>
                        <li><strong>Produção:</strong> PIX será enviado REALMENTE para a chave informada</li>
                        <li><strong>Saldo:</strong> Certifique-se de ter saldo suficiente na conta Asaas</li>
                        <li><strong>Taxas:</strong> PIX enviado é GRATUITO no Asaas (sem taxa)</li>
                    </ul>
                </div>

                <!-- Links Úteis -->
                <div class="mt-3 text-center">
                    <a href="conta_digital.php" class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-wallet2"></i> Ver Conta Digital
                    </a>
                    <a href="ambiente_asaas.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-gear-fill"></i> Configurar Ambiente
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
