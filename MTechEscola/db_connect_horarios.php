<?php
$host = 'localhost';
$db   = 'horarios_escolares';
$user = 'horarios_user';
$pass = '@Mar1401a';
$charset = 'utf8';

$dsn = "pgsql:host=$host;dbname=$db;options='--client_encoding=$charset'";
$options = [
	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
	$conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
	echo '<div style="background:#ff3c3c;color:#fff;padding:16px;border-radius:8px;margin:24px 0;font-weight:700;text-align:center;">';
	echo 'Erro ao conectar ao banco de horários: ' . htmlspecialchars($e->getMessage());
	echo '</div>';
	exit;
}