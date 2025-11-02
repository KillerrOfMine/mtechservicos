<?php
/**
 * Exemplo de Configuração de Banco de Dados
 *
 * INSTRUÇÕES:
 * 1. Copie este arquivo para: db_connect.php
 * 2. Edite as credenciais abaixo
 * 3. NUNCA versione o arquivo db_connect.php (está no .gitignore)
 */

// Configurações do PostgreSQL
$db_host = 'localhost';
$db_port = '5432';
$db_name = 'erpdb';
$db_user = 'mtechuser';
$db_pass = '@Mar1401a';  // ⚠️ MUDE A SENHA EM PRODUÇÃO!

// String de conexão DSN
$dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";

try {
    // Cria conexão PDO
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Define charset UTF-8
    $pdo->exec("SET NAMES 'UTF8'");

} catch (PDOException $e) {
    // Em produção, NÃO mostre detalhes do erro
    if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
        // Desenvolvimento - mostra erro completo
        die('Erro de conexão: ' . $e->getMessage());
    } else {
        // Produção - mensagem genérica
        error_log('Erro de conexão ao banco: ' . $e->getMessage());
        die('Erro ao conectar ao banco de dados. Contate o administrador.');
    }
}
