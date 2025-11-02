<?php
/**
 * Classe de Integração com Asaas API
 * Documentação: https://docs.asaas.com/
 * 
 * Funcionalidades:
 * - Transferências PIX
 * - Transferências TED
 * - Consulta de saldo
 * - Histórico de transações
 */

class AsaasAPI {
    private $apiKey;
    private $sandbox;
    private $baseUrl;
    
    /**
     * Construtor
     * 
     * @param string $apiKey Token de API do Asaas
     * @param bool $sandbox Usar ambiente de testes
     */
    public function __construct($apiKey, $sandbox = false) {
        $this->apiKey = $apiKey;
        $this->sandbox = $sandbox;
        $this->baseUrl = $sandbox 
            ? 'https://sandbox.asaas.com/api/v3'
            : 'https://api.asaas.com/v3';
    }
    
    /**
     * Envia transferência PIX
     * 
     * @param array $dados Dados da transferência
     * @return array Resultado da operação
     */
    public function enviarPIX($dados) {
        $payload = [
            'value' => (float) $dados['valor'],
            'pixAddressKey' => $dados['chave_pix'],
            'pixAddressKeyType' => $this->converterTipoChave($dados['tipo_chave']),
            'description' => $dados['descricao'] ?? 'Pagamento via PIX'
        ];
        
        // Data programada (opcional)
        if (isset($dados['data_agendamento'])) {
            $payload['scheduleDate'] = $dados['data_agendamento'];
        }
        
        $response = $this->request('POST', '/transfers', $payload);
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'transacao_id' => $response['data']['id'],
                'status' => $response['data']['status'],
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro desconhecido',
                'detalhes' => $response
            ];
        }
    }
    
    /**
     * Envia transferência TED
     * 
     * @param array $dados Dados da transferência
     * @return array Resultado da operação
     */
    public function enviarTED($dados) {
        $payload = [
            'value' => (float) $dados['valor'],
            'operationType' => 'TED',
            'bankAccount' => [
                'bank' => [
                    'code' => $dados['banco_codigo']
                ],
                'ownerName' => $dados['beneficiario_nome'],
                'cpfCnpj' => $dados['beneficiario_documento'],
                'agency' => $dados['agencia'],
                'account' => $dados['conta'],
                'accountDigit' => $dados['conta_digito'],
                'bankAccountType' => $dados['tipo_conta'] === 'POUPANCA' ? 'CONTA_POUPANCA' : 'CONTA_CORRENTE'
            ],
            'description' => $dados['descricao'] ?? 'Pagamento via TED'
        ];
        
        // Data programada (opcional)
        if (isset($dados['data_agendamento'])) {
            $payload['scheduleDate'] = $dados['data_agendamento'];
        }
        
        $response = $this->request('POST', '/transfers', $payload);
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'transacao_id' => $response['data']['id'],
                'status' => $response['data']['status'],
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro desconhecido',
                'detalhes' => $response
            ];
        }
    }
    
    /**
     * Consulta saldo da conta
     * 
     * @return array Saldo disponível
     */
    public function consultarSaldo() {
        $response = $this->request('GET', '/finance/balance');
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'saldo_disponivel' => $response['data']['balance'] ?? 0,
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao consultar saldo'
            ];
        }
    }
    
    /**
     * Lista transferências
     * 
     * @param array $filtros Filtros opcionais
     * @return array Lista de transferências
     */
    public function listarTransferencias($filtros = []) {
        $queryParams = [];
        
        if (isset($filtros['data_inicio'])) {
            $queryParams['startDate'] = $filtros['data_inicio'];
        }
        
        if (isset($filtros['data_fim'])) {
            $queryParams['endDate'] = $filtros['data_fim'];
        }
        
        if (isset($filtros['status'])) {
            $queryParams['status'] = $filtros['status'];
        }
        
        $query = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
        $response = $this->request('GET', '/transfers' . $query);
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'transferencias' => $response['data']['data'] ?? [],
                'total' => $response['data']['totalCount'] ?? 0
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao listar transferências'
            ];
        }
    }
    
    /**
     * Consulta detalhes de uma transferência
     * 
     * @param string $transferenciaId ID da transferência
     * @return array Detalhes da transferência
     */
    public function consultarTransferencia($transferenciaId) {
        $response = $this->request('GET', "/transfers/{$transferenciaId}");
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao consultar transferência'
            ];
        }
    }
    
    /**
     * Cancela uma transferência agendada
     * 
     * @param string $transferenciaId ID da transferência
     * @return array Resultado da operação
     */
    public function cancelarTransferencia($transferenciaId) {
        $response = $this->request('DELETE', "/transfers/{$transferenciaId}");
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'mensagem' => 'Transferência cancelada com sucesso'
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao cancelar transferência'
            ];
        }
    }
    
    /**
     * Solicita envio do token de autorização por SMS
     * 
     * @param string $transferenciaId ID da transferência
     * @return array Resultado da operação
     */
    public function solicitarTokenAutorizacao($transferenciaId) {
        $response = $this->request('POST', "/transfers/{$transferenciaId}/requestAuthorizationToken");
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'mensagem' => 'Token enviado com sucesso',
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao solicitar token',
                'detalhes' => $response
            ];
        }
    }
    
    /**
     * Autoriza uma transferência com código de 6 dígitos
     * 
     * @param string $transferenciaId ID da transferência
     * @param string $codigo Código de autorização (6 dígitos)
     * @return array Resultado da operação
     */
    public function autorizarTransferencia($transferenciaId, $codigo) {
        $payload = [
            'authorizationCode' => $codigo
        ];
        
        $response = $this->request('POST', "/transfers/{$transferenciaId}/authorize", $payload);
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'mensagem' => 'Transferência autorizada com sucesso',
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Código de autorização inválido',
                'detalhes' => $response
            ];
        }
    }
    
    /**
     * Cria chave PIX (tipo EVP - aleatória)
     * 
     * @return array Resultado com a chave criada
     */
    public function criarChavePix() {
        $payload = [
            'type' => 'EVP'
        ];
        
        $response = $this->request('POST', '/pix/addressKeys', $payload);
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'chave' => $response['data']['key'],
                'dados' => $response['data']
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao criar chave PIX'
            ];
        }
    }
    
    /**
     * Lista chaves PIX da conta
     * 
     * @return array Lista de chaves
     */
    public function listarChavesPix() {
        $response = $this->request('GET', '/pix/addressKeys');
        
        if ($response['success']) {
            return [
                'sucesso' => true,
                'chaves' => $response['data']['data'] ?? []
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => $response['error'] ?? 'Erro ao listar chaves PIX'
            ];
        }
    }
    
    /**
     * Valida chave PIX antes de enviar
     * 
     * @param string $chave Chave PIX
     * @param string $tipo Tipo da chave (CPF, CNPJ, EMAIL, PHONE, EVP)
     * @return array Resultado da validação
     */
    public function validarChavePix($chave, $tipo) {
        // Validações básicas
        $tipo = $this->converterTipoChave($tipo);
        
        switch ($tipo) {
            case 'CPF':
                if (!$this->validarCPF($chave)) {
                    return ['sucesso' => false, 'erro' => 'CPF inválido'];
                }
                break;
            
            case 'CNPJ':
                if (!$this->validarCNPJ($chave)) {
                    return ['sucesso' => false, 'erro' => 'CNPJ inválido'];
                }
                break;
            
            case 'EMAIL':
                if (!filter_var($chave, FILTER_VALIDATE_EMAIL)) {
                    return ['sucesso' => false, 'erro' => 'E-mail inválido'];
                }
                break;
            
            case 'PHONE':
                if (!preg_match('/^\d{11}$/', preg_replace('/\D/', '', $chave))) {
                    return ['sucesso' => false, 'erro' => 'Telefone inválido (deve ter 11 dígitos)'];
                }
                break;
        }
        
        return ['sucesso' => true];
    }
    
    /**
     * Converte tipo de chave PIX para formato Asaas
     */
    private function converterTipoChave($tipo) {
        $mapa = [
            'CPF' => 'CPF',
            'CNPJ' => 'CNPJ',
            'EMAIL' => 'EMAIL',
            'TELEFONE' => 'PHONE',
            'PHONE' => 'PHONE',
            'EVP' => 'EVP',
            'ALEATORIA' => 'EVP'
        ];
        
        return $mapa[strtoupper($tipo)] ?? 'CPF';
    }
    
    /**
     * Executa requisição HTTP para a API
     */
    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'access_token: ' . $this->apiKey,
            'User-Agent: ERP-MTech/1.0'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $error
            ];
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $decoded
            ];
        } else {
            return [
                'success' => false,
                'error' => $decoded['errors'][0]['description'] ?? 'Erro na requisição',
                'http_code' => $httpCode,
                'response' => $decoded
            ];
        }
    }
    
    /**
     * Valida CPF
     */
    private function validarCPF($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica sequências inválidas
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        
        // Valida dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida CNPJ
     */
    private function validarCNPJ($cnpj) {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        // Verifica sequências inválidas
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        
        // Valida dígitos verificadores
        $tamanho = strlen($cnpj) - 2;
        $numeros = substr($cnpj, 0, $tamanho);
        $digitos = substr($cnpj, $tamanho);
        $soma = 0;
        $pos = $tamanho - 7;
        
        for ($i = $tamanho; $i >= 1; $i--) {
            $soma += $numeros[$tamanho - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        
        $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
        if ($resultado != $digitos[0]) {
            return false;
        }
        
        $tamanho = $tamanho + 1;
        $numeros = substr($cnpj, 0, $tamanho);
        $soma = 0;
        $pos = $tamanho - 7;
        
        for ($i = $tamanho; $i >= 1; $i--) {
            $soma += $numeros[$tamanho - $i] * $pos--;
            if ($pos < 2) {
                $pos = 9;
            }
        }
        
        $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
        if ($resultado != $digitos[1]) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Testa conexão com a API
     * 
     * @return array Resultado do teste
     */
    public function testarConexao() {
        $response = $this->request('GET', '/finance/balance');
        
        return [
            'sucesso' => $response['success'],
            'mensagem' => $response['success'] 
                ? 'Conexão com Asaas estabelecida com sucesso!' 
                : 'Erro ao conectar com Asaas: ' . ($response['error'] ?? 'Desconhecido'),
            'ambiente' => $this->sandbox ? 'Sandbox (Testes)' : 'Produção'
        ];
    }
}
