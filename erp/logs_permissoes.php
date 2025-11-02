<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /erp/login.php');
    exit;
}
require_once __DIR__ . '/includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Logs de Permissionamento</title>
    <link rel="stylesheet" href="/erp/assets/theme.css">
</head>
<body style="font-family: 'Inter', sans-serif; margin:0;">
<?php include 'includes/header.php'; ?>
<div class="container" style="padding:32px 24px;max-width:1100px;margin:32px auto;">
    <h2 style="margin-bottom:24px;font-size:1.6em;font-weight:700;">Logs de Permissionamento</h2>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f6f8fa;">
                <th style="padding:10px 6px;">Data/Hora</th>
                <th style="padding:10px 6px;">Usuário que alterou</th>
                <th style="padding:10px 6px;">Usuário afetado</th>
                <th style="padding:10px 6px;">Ação</th>
                <th style="padding:10px 6px;">Permissões</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query('SELECT l.*, u.nome AS usuario_alterador, ua.nome AS usuario_afetado FROM logs_permissoes l LEFT JOIN usuarios u ON l.usuario_alterador_id = u.id LEFT JOIN usuarios ua ON l.usuario_afetado_id = ua.id ORDER BY l.datahora DESC LIMIT 100');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td style='padding:8px 6px;'>" . htmlspecialchars($row['datahora']) . "</td>";
                echo "<td style='padding:8px 6px;'>" . htmlspecialchars($row['usuario_alterador']) . "</td>";
                echo "<td style='padding:8px 6px;'>" . htmlspecialchars($row['usuario_afetado']) . "</td>";
                echo "<td style='padding:8px 6px;'>" . htmlspecialchars($row['acao']) . "</td>";
                echo "<td style='padding:8px 6px;'>" . htmlspecialchars($row['permissoes']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</body>
</html>
