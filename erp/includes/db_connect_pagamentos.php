<?php
/**
 * Conexão PostgreSQL para Sistema de Pagamentos
 * Database: erpdb
 */

$host = 'localhost';
$dbname = 'erpdb';
$user = 'mtechuser';
$password = '@Mar1401a';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
