<?php
/**
 * Gerenciador de Pagamentos com Agrupamento
 * Suporta: Asaas, Banco Inter
 * 
 * Funcionalidades:
 * - Agrupamento automático de contas por fornecedor
 * - Pagamento via PIX/TED
 * - Controle de limites (30 PIX gratuitos do Asaas)
 * - Rastreabilidade completa
 */

class GerenciadorPagamentos {
    private $db;
    private $config = [];
    private $apiProvider;
    
    public function __construct($db) {
        $this->db = $db;
        $this->carregarConfig();
        $this->inicializarAPI();
    }
    
    /**
     * Carrega configurações do banco
     */
    private function carregarConfig() {
        $sql = "SELECT chave, valor FROM config_pagamentos";
        $result = $this->db->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $this->config[$row['chave']] = $row['valor'];
        }
    }
    
    /**
     * Inicializa a API de pagamentos (Asaas ou Inter)
     */
    private function inicializarAPI() {
        $provider = $this->config['api_provider'] ?? 'asaas';
        
        switch ($provider) {
            case 'asaas':
                require_once __DIR__ . '/AsaasAPI.php';
                $this->apiProvider = new AsaasAPI(
                    $this->config['api_key'],
                    $this->config['api_sandbox'] == '1'
                );
                break;
            
            case 'banco_inter':
                require_once __DIR__ . '/BancoInterAPI.php';
                $this->apiProvider = new BancoInterAPI(
                    $this->config['api_key']
                );
                break;
            
            default:
                throw new Exception("Provedor de API não suportado: {$provider}");
        }
    }
    
    /**
     * Agrupa contas pendentes por fornecedor
     * 
     * @param string $dataProgramada Data para pagamento (Y-m-d)
     * @return array Resultado do agrupamento
     */
    public function agruparPagamentos($dataProgramada = null) {
        if (!$dataProgramada) {
            $dataProgramada = date('Y-m-d');
        }
        
        // Verifica se agrupamento está ativo
        if ($this->config['agrupamento_ativo'] != '1') {
            return ['sucesso' => false, 'mensagem' => 'Agrupamento desativado'];
        }
        
        // Chama a stored procedure
        $stmt = $this->db->prepare("CALL sp_agrupar_pagamentos(?)");
        $stmt->bind_param('s', $dataProgramada);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados = $result->fetch_assoc();
        $stmt->close();
        
        // Limpa resultados pendentes
        while ($this->db->next_result()) {
            $this->db->store_result();
        }
        
        return [
            'sucesso' => true,
            'lotes_criados' => $dados['lotes_criados'] ?? 0,
            'data_programada' => $dataProgramada
        ];
    }
    
    /**
     * Lista lotes aguardando pagamento
     * 
     * @return array Lista de lotes
     */
    public function listarLotesAguardando() {
        $sql = "
            SELECT 
                lp.*,
                f.nome as fornecedor_nome,
                f.pix_chave,
                f.pix_tipo,
                (SELECT GROUP_CONCAT(numero_documento SEPARATOR ', ')
                 FROM contas_pagar 
                 WHERE lote_pagamento_id = lp.id) as documentos
            FROM lotes_pagamento lp
            INNER JOIN fornecedores f ON lp.fornecedor_id = f.id
            WHERE lp.status = 'AGUARDANDO'
            ORDER BY lp.data_pagamento_programada, f.nome
        ";
        
        $result = $this->db->query($sql);
        $lotes = [];
        
        while ($row = $result->fetch_assoc()) {
            $lotes[] = $row;
        }
        
        return $lotes;
    }
    
    /**
     * Processa um lote de pagamento (envia PIX)
     * 
     * @param int $loteId ID do lote
     * @return array Resultado do pagamento
     */
    public function processarLote($loteId) {
        // Busca dados do lote
        $stmt = $this->db->prepare("
            SELECT 
                lp.*,
                f.nome as fornecedor_nome,
                f.cpf_cnpj as fornecedor_documento,
                f.pix_chave,
                f.pix_tipo
            FROM lotes_pagamento lp
            INNER JOIN fornecedores f ON lp.fornecedor_id = f.id
            WHERE lp.id = ? AND lp.status = 'AGUARDANDO'
        ");
        $stmt->bind_param('i', $loteId);
        $stmt->execute();
        $lote = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$lote) {
            return ['sucesso' => false, 'mensagem' => 'Lote não encontrado ou já processado'];
        }
        
        // Atualiza status para PROCESSANDO
        $this->atualizarStatusLote($loteId, 'PROCESSANDO');
        
        try {
            // Envia pagamento via API
            $resultado = $this->apiProvider->enviarPIX([
                'valor' => $lote['valor_total'],
                'chave_pix' => $lote['pix_chave'],
                'tipo_chave' => $lote['pix_tipo'],
                'descricao' => "Pagamento agrupado - Lote #{$loteId} - {$lote['quantidade_contas']} contas"
            ]);
            
            if ($resultado['sucesso']) {
                // Atualiza lote como PAGO
                $this->atualizarLotePago(
                    $loteId,
                    $resultado['transacao_id'],
                    json_encode($resultado['dados'])
                );
                
                // Atualiza contas como PAGAS
                $this->atualizarContasPagas($loteId);
                
                // Registra histórico
                $this->registrarTransacao($loteId, 'CONFIRMADO', 'Pagamento realizado com sucesso', $resultado);
                
                return [
                    'sucesso' => true,
                    'mensagem' => 'Pagamento realizado com sucesso',
                    'transacao_id' => $resultado['transacao_id'],
                    'valor' => $lote['valor_total'],
                    'fornecedor' => $lote['fornecedor_nome']
                ];
                
            } else {
                // Erro no pagamento
                $this->atualizarStatusLote($loteId, 'ERRO', $resultado['erro']);
                $this->registrarTransacao($loteId, 'ERRO', $resultado['erro'], $resultado);
                
                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao processar pagamento: ' . $resultado['erro']
                ];
            }
            
        } catch (Exception $e) {
            // Erro excepcional
            $this->atualizarStatusLote($loteId, 'ERRO', $e->getMessage());
            $this->registrarTransacao($loteId, 'ERRO', $e->getMessage(), ['exception' => $e->getTrace()]);
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processa todos os lotes aguardando
     * 
     * @return array Resultado do processamento em lote
     */
    public function processarTodosLotes() {
        $lotes = $this->listarLotesAguardando();
        $resultados = [
            'total' => count($lotes),
            'sucesso' => 0,
            'erro' => 0,
            'detalhes' => []
        ];
        
        foreach ($lotes as $lote) {
            $resultado = $this->processarLote($lote['id']);
            
            if ($resultado['sucesso']) {
                $resultados['sucesso']++;
            } else {
                $resultados['erro']++;
            }
            
            $resultados['detalhes'][] = [
                'lote_id' => $lote['id'],
                'fornecedor' => $lote['fornecedor_nome'],
                'valor' => $lote['valor_total'],
                'resultado' => $resultado
            ];
        }
        
        return $resultados;
    }
    
    /**
     * Atualiza status do lote
     */
    private function atualizarStatusLote($loteId, $status, $erro = null) {
        $stmt = $this->db->prepare("
            UPDATE lotes_pagamento 
            SET status = ?, transacao_erro = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $status, $erro, $loteId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Atualiza lote após pagamento confirmado
     */
    private function atualizarLotePago($loteId, $transacaoId, $comprovante) {
        $stmt = $this->db->prepare("
            UPDATE lotes_pagamento 
            SET 
                status = 'PAGO',
                data_pagamento_realizada = NOW(),
                transacao_id = ?,
                transacao_comprovante = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $transacaoId, $comprovante, $loteId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Atualiza contas do lote como pagas
     */
    private function atualizarContasPagas($loteId) {
        $stmt = $this->db->prepare("
            UPDATE contas_pagar 
            SET 
                status = 'PAGA',
                data_pagamento = CURDATE()
            WHERE lote_pagamento_id = ?
        ");
        $stmt->bind_param('i', $loteId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Registra transação no histórico
     */
    private function registrarTransacao($loteId, $tipo, $descricao, $dados) {
        $dadosJson = json_encode($dados);
        
        $stmt = $this->db->prepare("
            INSERT INTO transacoes_pagamento 
            (lote_pagamento_id, tipo_evento, descricao, dados_json)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('isss', $loteId, $tipo, $descricao, $dadosJson);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Obtém estatísticas de uso de PIX (para controle dos 30 gratuitos)
     */
    public function getEstatisticasPIX() {
        $mesAtual = date('Y-m');
        $limite = (int) $this->config['limite_pix_gratuitos'];
        
        $sql = "
            SELECT COUNT(*) as total_pix_mes
            FROM lotes_pagamento
            WHERE status = 'PAGO'
            AND metodo_pagamento = 'PIX'
            AND DATE_FORMAT(data_pagamento_realizada, '%Y-%m') = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $mesAtual);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $totalUsado = $result['total_pix_mes'] ?? 0;
        $restantes = max(0, $limite - $totalUsado);
        
        return [
            'limite_mensal' => $limite,
            'utilizados' => $totalUsado,
            'restantes' => $restantes,
            'percentual_uso' => $limite > 0 ? round(($totalUsado / $limite) * 100, 2) : 0,
            'alerta' => $restantes <= 5 ? 'Atenção: Poucos PIX gratuitos restantes!' : null
        ];
    }
    
    /**
     * Obtém dashboard de pagamentos
     */
    public function getDashboard() {
        $sql = "SELECT * FROM view_dashboard_pagamentos";
        $result = $this->db->query($sql);
        $dashboard = $result->fetch_assoc();
        
        // Adiciona estatísticas de PIX
        $dashboard['estatisticas_pix'] = $this->getEstatisticasPIX();
        
        return $dashboard;
    }
}
