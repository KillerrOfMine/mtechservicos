<?php
/**
 * Classe para integração com Mercado Pago
 * Usa as credenciais de produção do Checkout Transparente
 */
class MercadoPago {
    private $publicKey;
    private $accessToken;
    private $apiUrl = 'https://api.mercadopago.com';
    
    public function __construct() {
        $this->publicKey = MP_PUBLIC_KEY;
        $this->accessToken = MP_ACCESS_TOKEN;
    }
    
    /**
     * Busca saldo disponível na conta
     */
    public function getBalance() {
        $url = $this->apiUrl . '/v1/users/me';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $userData = json_decode($response, true);
        
        // Busca saldo da conta
        $balanceUrl = $this->apiUrl . '/v1/users/' . $userData['id'] . '/mercadopago_account/balance';
        
        $ch = curl_init($balanceUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Busca transações recentes
     */
    public function getTransactions($limit = 10) {
        $url = $this->apiUrl . '/v1/payments/search?limit=' . $limit . '&sort=date_created&criteria=desc';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Busca informações do usuário
     */
    public function getUserInfo() {
        $url = $this->apiUrl . '/v1/users/me';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Cria uma preferência de pagamento (para Checkout Pro)
     */
    public function createPreference($items, $payer = null) {
        $url = $this->apiUrl . '/checkout/preferences';
        
        $data = [
            'items' => $items,
            'back_urls' => [
                'success' => APP_URL . '/pagamento_sucesso.php',
                'failure' => APP_URL . '/pagamento_erro.php',
                'pending' => APP_URL . '/pagamento_pendente.php'
            ],
            'auto_return' => 'approved'
        ];
        
        if ($payer) {
            $data['payer'] = $payer;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Busca detalhes de um pagamento
     */
    public function getPayment($paymentId) {
        $url = $this->apiUrl . '/v1/payments/' . $paymentId;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Retorna a Public Key para uso no frontend
     */
    public function getPublicKey() {
        return $this->publicKey;
    }
}
