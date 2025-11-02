<?php
require_once 'config.php';

/**
 * Classe para gerenciar autenticação Google OAuth 2.0
 */
class GoogleAuth {
    
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    
    public function __construct() {
        $this->clientId = GOOGLE_CLIENT_ID;
        $this->clientSecret = GOOGLE_CLIENT_SECRET;
        $this->redirectUri = GOOGLE_REDIRECT_URI;
    }
    
    /**
     * Gera URL de autenticação do Google
     */
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Troca o código por tokens de acesso
     */
    public function getAccessToken($code) {
        $url = 'https://oauth2.googleapis.com/token';
        
        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Erro ao obter token do Google: " . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Obtém informações do usuário do Google
     */
    public function getUserInfo($accessToken) {
        $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Erro ao obter informações do usuário: " . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Salva ou atualiza usuário no banco de dados
     */
    public function saveUser($userInfo) {
        $db = getDB();
        
        // Verifica se usuário já existe
        $stmt = $db->prepare("SELECT id FROM usuarios_loja WHERE google_id = ?");
        $stmt->execute([$userInfo['id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Atualiza último acesso
            $stmt = $db->prepare("
                UPDATE usuarios_loja 
                SET ultimo_acesso = NOW(), 
                    nome = ?, 
                    foto_perfil = ?
                WHERE google_id = ?
            ");
            $stmt->execute([
                $userInfo['name'],
                $userInfo['picture'] ?? null,
                $userInfo['id']
            ]);
            
            return $user['id'];
        } else {
            // Cria novo usuário
            $stmt = $db->prepare("
                INSERT INTO usuarios_loja (google_id, email, nome, foto_perfil) 
                VALUES (?, ?, ?, ?)
                RETURNING id
            ");
            $stmt->execute([
                $userInfo['id'],
                $userInfo['email'],
                $userInfo['name'],
                $userInfo['picture'] ?? null
            ]);
            
            $result = $stmt->fetch();
            $userId = $result['id'];
            
            // Cria configuração de tema padrão
            $stmt = $db->prepare("
                INSERT INTO tema_config (usuario_id) 
                VALUES (?)
            ");
            $stmt->execute([$userId]);
            
            return $userId;
        }
    }
    
    /**
     * Inicia sessão do usuário
     */
    public function login($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();
        
        // Atualiza último acesso
        $db = getDB();
        $stmt = $db->prepare("UPDATE usuarios_loja SET ultimo_acesso = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    /**
     * Desloga usuário
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Obtém dados do usuário logado
     */
    public function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM usuarios_loja WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
}
