<?php
require_once 'config.php';

/**
 * Classe para integração com API do Mercado Livre
 */
class MercadoLivreAPI {
    
    private $appId;
    private $clientSecret;
    private $redirectUri;
    private $apiUrl;
    private $userId;
    
    public function __construct($userId = null) {
        $this->appId = ML_APP_ID;
        $this->clientSecret = ML_CLIENT_SECRET;
        $this->redirectUri = ML_REDIRECT_URI;
        $this->apiUrl = ML_API_URL;
        $this->userId = $userId;
    }
    
    /**
     * Gera URL de autorização do Mercado Livre
     */
    public function getAuthUrl() {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'state' => bin2hex(random_bytes(16)) // Para segurança
        ];
        
        return 'https://auth.mercadolivre.com.br/authorization?' . http_build_query($params);
    }
    
    /**
     * Troca código por access token
     */
    public function getAccessToken($code) {
        $url = $this->apiUrl . '/oauth/token';
        
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->appId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ];
        
        return $this->makeRequest($url, 'POST', $data, [], false);
    }
    
    /**
     * Atualiza access token usando refresh token
     */
    public function refreshAccessToken($refreshToken) {
        $url = $this->apiUrl . '/oauth/token';
        
        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->appId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken
        ];
        
        return $this->makeRequest($url, 'POST', $data, [], false);
    }
    
    /**
     * Salva tokens no banco de dados
     */
    public function saveTokens($tokenData) {
        if (!$this->userId) {
            throw new Exception("User ID não definido");
        }
        
        $db = getDB();
        
        // Desativa tokens antigos
        $stmt = $db->prepare("UPDATE ml_tokens SET ativo = FALSE WHERE usuario_id = ?");
        $stmt->execute([$this->userId]);
        
        // Salva novo token
        $expiresAt = date('Y-m-d H:i:s', time() + $tokenData['expires_in']);
        
        $stmt = $db->prepare("
            INSERT INTO ml_tokens 
            (usuario_id, access_token, refresh_token, token_type, expires_in, user_id_ml, data_expiracao, ativo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)
            RETURNING id
        ");
        
        $stmt->execute([
            $this->userId,
            $tokenData['access_token'],
            $tokenData['refresh_token'],
            $tokenData['token_type'] ?? 'Bearer',
            $tokenData['expires_in'],
            $tokenData['user_id'] ?? null,
            $expiresAt
        ]);
        
        $result = $stmt->fetch();
        return $result['id'];
    }
    
    /**
     * Obtém token válido do banco de dados
     */
    public function getValidToken() {
        if (!$this->userId) {
            return null;
        }
        
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM ml_tokens 
            WHERE usuario_id = ? AND ativo = TRUE 
            ORDER BY data_criacao DESC 
            LIMIT 1
        ");
        $stmt->execute([$this->userId]);
        $token = $stmt->fetch();
        
        if (!$token) {
            return null;
        }
        
        // Verifica se token expirou
        if (strtotime($token['data_expiracao']) <= time()) {
            // Tenta renovar
            $newToken = $this->refreshAccessToken($token['refresh_token']);
            if ($newToken) {
                $this->saveTokens($newToken);
                return $newToken['access_token'];
            }
            return null;
        }
        
        return $token['access_token'];
    }
    
    /**
     * Busca produtos/anúncios do usuário
     */
    public function getMyItems($filters = []) {
        $accessToken = $this->getValidToken();
        if (!$accessToken) {
            throw new Exception("Token de acesso inválido ou expirado");
        }
        
        $userIdML = $this->getMercadoLivreUserId($accessToken);
        
        // Usa API pública de busca (não requer autenticação especial)
        $url = $this->apiUrl . '/sites/MLB/search?seller_id=' . $userIdML . '&limit=50';
        
        return $this->makeRequest($url, 'GET', null, [
            'Authorization: Bearer ' . $accessToken
        ]);
    }
    
    /**
     * Busca detalhes de um item específico
     */
    public function getItem($itemId) {
        $accessToken = $this->getValidToken();
        
        if (!$accessToken) {
            throw new Exception("Token de acesso necessário para buscar produtos");
        }
        
        $url = $this->apiUrl . '/items/' . $itemId;
        
        return $this->makeRequest($url, 'GET', null, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
    }
    
    /**
     * Sincroniza produtos do ML com banco de dados local
     */
    public function syncProducts() {
        $items = $this->getMyItems();
        
        if (!$items) {
            $this->logSync('produtos', 'erro', 'Erro ao buscar produtos da API');
            return ['success' => false, 'message' => 'Erro ao buscar produtos do Mercado Livre'];
        }
        
        // Verifica se há produtos
        if (!isset($items['results']) || empty($items['results'])) {
            $this->logSync('produtos', 'sucesso', 'Nenhum produto encontrado no ML');
            return ['success' => true, 'message' => 'Você ainda não tem produtos anunciados no Mercado Livre', 'synced' => 0];
        }
        
        $db = getDB();
        $synced = 0;
        
        // A API pública retorna os itens completos em results
        foreach ($items['results'] as $item) {
            // Pega mais detalhes do item
            $itemDetails = $this->getItem($item['id']);
            
            if (!$itemDetails) {
                continue;
            }
            
            // Busca descrição
            $descricao = '';
            try {
                $accessToken = $this->getValidToken();
                $descUrl = $this->apiUrl . '/items/' . $item['id'] . '/description';
                $descData = $this->makeRequest($descUrl, 'GET', null, [
                    'Authorization: Bearer ' . $accessToken
                ]);
                $descricao = $descData['plain_text'] ?? '';
            } catch (Exception $e) {
                // Ignora erro de descrição
            }
            
            // Verifica se produto já existe
            $stmt = $db->prepare("SELECT id FROM produtos_ml WHERE ml_id = ?");
            $stmt->execute([$itemDetails['id']]);
            $existingProduct = $stmt->fetch();
            
            if ($existingProduct) {
                // Atualiza
                $stmt = $db->prepare("
                    UPDATE produtos_ml SET
                        titulo = ?,
                        descricao = ?,
                        preco = ?,
                        moeda = ?,
                        quantidade_disponivel = ?,
                        quantidade_vendida = ?,
                        condicao = ?,
                        categoria_id = ?,
                        thumbnail = ?,
                        permalink = ?,
                        status = ?
                    WHERE ml_id = ?
                ");
                
                $stmt->execute([
                    $itemDetails['title'],
                    $descricao,
                    $itemDetails['price'],
                    $itemDetails['currency_id'],
                    $itemDetails['available_quantity'],
                    $itemDetails['sold_quantity'],
                    $itemDetails['condition'],
                    $itemDetails['category_id'],
                    $itemDetails['thumbnail'],
                    $itemDetails['permalink'],
                    $itemDetails['status'],
                    $itemDetails['id']
                ]);
                
                $productId = $existingProduct['id'];
            } else {
                // Insere
                $stmt = $db->prepare("
                    INSERT INTO produtos_ml 
                    (usuario_id, ml_id, titulo, descricao, preco, moeda, quantidade_disponivel, 
                     quantidade_vendida, condicao, categoria_id, thumbnail, permalink, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    RETURNING id
                ");
                
                $stmt->execute([
                    $this->userId,
                    $itemDetails['id'],
                    $itemDetails['title'],
                    $descricao,
                    $itemDetails['price'],
                    $itemDetails['currency_id'],
                    $itemDetails['available_quantity'],
                    $itemDetails['sold_quantity'],
                    $itemDetails['condition'],
                    $itemDetails['category_id'],
                    $itemDetails['thumbnail'],
                    $itemDetails['permalink'],
                    $itemDetails['status']
                ]);
                
                $result = $stmt->fetch();
                $productId = $result['id'];
            }
            
            // Sincroniza imagens
            if (isset($itemDetails['pictures'])) {
                // Remove imagens antigas
                $stmt = $db->prepare("DELETE FROM produto_imagens WHERE produto_id = ?");
                $stmt->execute([$productId]);
                
                // Insere novas imagens
                $stmt = $db->prepare("
                    INSERT INTO produto_imagens (produto_id, url, ordem) 
                    VALUES (?, ?, ?)
                ");
                
                foreach ($itemDetails['pictures'] as $index => $picture) {
                    $stmt->execute([
                        $productId,
                        $picture['secure_url'],
                        $index
                    ]);
                }
            }
            
            $synced++;
        }
        
        $this->logSync('produtos', 'sucesso', "Sincronizados {$synced} produtos");
        return ['success' => true, 'message' => "Sincronizados {$synced} produtos com sucesso!", 'synced' => $synced];
    }
    
    /**
     * Busca saldo da carteira do Mercado Livre
     */
    public function getBalance() {
        $accessToken = $this->getValidToken();
        if (!$accessToken) {
            throw new Exception("Token de acesso inválido ou expirado");
        }
        
        $userIdML = $this->getMercadoLivreUserId($accessToken);
        $url = $this->apiUrl . "/users/{$userIdML}/mercadopago_account/balance";
        
        return $this->makeRequest($url, 'GET', null, [
            'Authorization: Bearer ' . $accessToken
        ]);
    }
    
    /**
     * Busca transações da carteira
     */
    public function getTransactions($filters = []) {
        $accessToken = $this->getValidToken();
        if (!$accessToken) {
            throw new Exception("Token de acesso inválido ou expirado");
        }
        
        $params = array_merge([
            'limit' => 50,
            'offset' => 0
        ], $filters);
        
        $url = $this->apiUrl . '/money_requests/search?' . http_build_query($params);
        
        return $this->makeRequest($url, 'GET', null, [
            'Authorization: Bearer ' . $accessToken
        ]);
    }
    
    /**
     * Sincroniza transações com banco de dados
     */
    public function syncTransactions() {
        try {
            $transactions = $this->getTransactions();
            
            if (!$transactions || !isset($transactions['results'])) {
                $this->logSync('transacoes', 'erro', 'Nenhuma transação retornada');
                return false;
            }
            
            $db = getDB();
            $synced = 0;
            
            foreach ($transactions['results'] as $trans) {
                // Verifica se transação já existe
                $stmt = $db->prepare("SELECT id FROM ml_transacoes WHERE transacao_id = ?");
                $stmt->execute([$trans['id']]);
                
                if ($stmt->fetch()) {
                    continue; // Já existe
                }
                
                // Insere nova transação
                $stmt = $db->prepare("
                    INSERT INTO ml_transacoes 
                    (usuario_id, transacao_id, tipo, status, valor, moeda, descricao, 
                     data_criacao, data_aprovacao)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $this->userId,
                    $trans['id'],
                    $trans['type'] ?? 'unknown',
                    $trans['status'] ?? 'unknown',
                    $trans['amount'] ?? 0,
                    $trans['currency_id'] ?? 'BRL',
                    $trans['description'] ?? '',
                    $trans['date_created'] ?? date('Y-m-d H:i:s'),
                    $trans['date_approved'] ?? null
                ]);
                
                $synced++;
            }
            
            $this->logSync('transacoes', 'sucesso', "Sincronizadas {$synced} transações");
            return $synced;
        } catch (Exception $e) {
            $this->logSync('transacoes', 'erro', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém ID do usuário no Mercado Livre
     */
    private function getMercadoLivreUserId($accessToken) {
        $url = $this->apiUrl . '/users/me';
        
        $userData = $this->makeRequest($url, 'GET', null, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        return $userData['id'] ?? null;
    }
    
    /**
     * Faz requisição HTTP
     */
    private function makeRequest($url, $method = 'GET', $data = null, $headers = [], $requireAuth = true) {
        $ch = curl_init($url);
        
        $defaultHeaders = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, 
                    is_array($data) ? http_build_query($data) : $data
                );
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Erro cURL: " . $error);
            return false;
        }
        
        if ($httpCode >= 400) {
            error_log("Erro HTTP {$httpCode}: " . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Registra log de sincronização
     */
    private function logSync($tipo, $status, $mensagem) {
        if (!$this->userId) {
            return;
        }
        
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO sync_logs (usuario_id, tipo, status, mensagem) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$this->userId, $tipo, $status, $mensagem]);
    }
}
