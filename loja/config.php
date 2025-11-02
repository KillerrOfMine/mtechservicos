<?php
/**
 * Configurações do Marketplace
 * IMPORTANTE: Configure as credenciais antes de usar
 */

// Configurações do Banco de Dados PostgreSQL
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_USER', 'loja_user');
define('DB_PASS', '@Mar1401a');
define('DB_NAME', 'loja_mtechservicos');

// Configurações do Google OAuth 2.0
// Obtenha em: https://console.cloud.google.com/
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', 'https://mtechservicos.com/loja/callback_google.php');

// Configurações do Mercado Livre API
// Obtenha em: https://developers.mercadolivre.com.br/
define('ML_APP_ID', '8985811807975232');
define('ML_CLIENT_SECRET', 'QGjLSP4WE7tgo5QHxQljepn8YHWevO8D');
define('ML_REDIRECT_URI', 'https://mtechservicos.com/loja/callback_ml.php');
define('ML_API_URL', 'https://api.mercadolibre.com');

// Configurações do Mercado Pago
// Use as credenciais de PRODUÇÃO da sua aplicação Checkout Transparente
// Public Key e Access Token encontrados em: https://www.mercadopago.com.br/developers/panel/app/144450205/credentials
define('MP_PUBLIC_KEY', 'APP_USR-ac32e66f-74c9-4be0-b949-92c5b19bf579');
define('MP_ACCESS_TOKEN', 'APP_USR-8985811807975232-020718-36f5686578e785578491c0590875720-162691921');

// País do Mercado Livre (BR = Brasil, AR = Argentina, MX = México, etc)
define('ML_SITE_ID', 'MLB'); // MLB = Mercado Livre Brasil

// Configurações da Aplicação
define('APP_NAME', 'MTech Marketplace');
define('APP_URL', 'http://localhost/mtechservicos/loja');
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em produção com HTTPS
session_start();

// Timezone
date_default_timezone_set(TIMEZONE);

// Autoloader simples (caso precise)
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Função para obter conexão com banco de dados
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            die("Erro ao conectar ao banco de dados. Verifique as configurações.");
        }
    }
    
    return $pdo;
}

/**
 * Função para verificar se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Função para redirecionar
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Função para sanitizar output
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
