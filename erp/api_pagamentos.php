<?php
/**
 * API de Pagamentos - Backend para o sistema de pagamentos agrupados
 * Recebe requisições do frontend e processa via GerenciadorPagamentos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/includes/db_connect_pagamentos.php';
require_once __DIR__ . '/classes/GerenciadorPagamentos.php';

// Inicia sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica autenticação
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Não autenticado', 'redirect' => '/erp/login.php']);
    http_response_code(401);
    exit;
}

try {
    $gerenciador = new GerenciadorPagamentos($pdo);
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            $dashboard = $gerenciador->getDashboard();
            echo json_encode($dashboard);
            break;
        
        case 'listar_lotes':
            $lotes = $gerenciador->listarLotesAguardando();
            echo json_encode($lotes);
            break;
        
        case 'agrupar':
            $dataProgramada = $_POST['data_programada'] ?? date('Y-m-d');
            $resultado = $gerenciador->agruparPagamentos($dataProgramada);
            echo json_encode($resultado);
            break;
        
        case 'processar_lote':
            $loteId = (int) ($_GET['id'] ?? 0);
            if ($loteId) {
                $resultado = $gerenciador->processarLote($loteId);
                echo json_encode($resultado);
            } else {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID do lote não informado']);
            }
            break;
        
        case 'processar_todos':
            $resultado = $gerenciador->processarTodosLotes();
            echo json_encode($resultado);
            break;
        
        case 'detalhes_lote':
            $loteId = (int) ($_GET['id'] ?? 0);
            if ($loteId) {
                $stmt = $conn->prepare("
                    SELECT 
                        lp.*,
                        f.nome as fornecedor_nome,
                        f.pix_chave,
                        STRING_AGG(cp.numero_documento, ', ') as documentos
                    FROM lotes_pagamento lp
                    INNER JOIN fornecedores f ON lp.fornecedor_id = f.id
                    LEFT JOIN contas_pagar cp ON cp.lote_pagamento_id = lp.id
                    WHERE lp.id = :lote_id
                    GROUP BY lp.id, lp.fornecedor_id, lp.valor_total, lp.status, lp.data_programada, 
                             lp.transacao_id, lp.criado_em, f.razao_social, f.cpf_cnpj, f.pix_tipo, f.pix_chave
                ");
                $stmt->execute([':lote_id' => $loteId]);
                $detalhes = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode($detalhes ?: ['erro' => 'Lote não encontrado']);
            } else {
                echo json_encode(['erro' => 'ID não informado']);
            }
            break;
        
        case 'estatisticas_pix':
            $stats = $gerenciador->getEstatisticasPIX();
            echo json_encode($stats);
            break;
        
        default:
            echo json_encode(['erro' => 'Ação não especificada']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro no servidor',
        'mensagem' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
