<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_host = 'localhost';
$db_port = '5432';
$db_name = 'erpdb';
$db_user = 'mtechuser';
$db_pass = '@Mar1401a';

try {
    $pdo = new PDO("pgsql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // echo 'Conexão bem-sucedida!'; // Removido para produção
} catch (PDOException $e) {
    die('Erro ao conectar: ' . $e->getMessage());
}
?>
