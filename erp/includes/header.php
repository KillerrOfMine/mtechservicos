<?php
session_start();
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';
// Carregar última configuração
try {
    $stmt = $pdo->query('SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1');
    $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $config = [];
}
$header_logo = $config['header_logo'] ?? '';
$user_name = 'Usuário';
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['nome'])) {
        $user_name = htmlspecialchars($row['nome']);
    }
}
$tema = $config['tema'] ?? 'claro';
$logo_url = '';
if ($tema === 'Escuro' && !empty($config['logo_clara'])) {
    $logo_url = $config['logo_clara'];
} elseif (!empty($config['logo_escura'])) {
    $logo_url = $config['logo_escura'];
} else {
    $logo_url = $config['header_logo'] ?? '';
}
$favicon_url = $config['favicon'] ?? '';
?>

<!-- O PHP está fechado antes do CSS -->
<link rel="stylesheet" href="/erp/assets/header.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="/erp/assets/theme.css?v=<?php echo time(); ?>">
<script src="/erp/assets/header.js?v=<?php echo time(); ?>"></script>
