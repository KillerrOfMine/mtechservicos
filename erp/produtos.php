<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/header.php';
require_once 'includes/db_connect.php';
?>
<div class="container" style="max-width:1100px;margin:80px auto 0 auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px #0001;padding:32px 24px;">
    <h2 style="font-size:2em;font-weight:700;color:#1857d8;margin-bottom:24px;">Produtos cadastrados</h2>
    <div style="margin-bottom:32px;">
        <a href="produtos_cadastro.php" class="pdv-btn" style="background:#1857d8;color:#fff;font-weight:700;border:none;border-radius:8px;padding:12px 32px;font-size:1em;text-decoration:none;">Novo Produto</a>
    </div>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f3f6fa;">
                <th style="padding:12px 8px;text-align:left;">ID</th>
                <th style="padding:12px 8px;text-align:left;">Nome</th>
                <th style="padding:12px 8px;text-align:left;">Tipo</th>
                <th style="padding:12px 8px;text-align:left;">NCM</th>
                <th style="padding:12px 8px;text-align:left;">Unidade</th>
                <th style="padding:12px 8px;text-align:left;">Peso Líquido</th>
                <th style="padding:12px 8px;text-align:left;">Peso Bruto</th>
                <th style="padding:12px 8px;text-align:left;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                $stmt = $pdo->query('SELECT * FROM produtos ORDER BY id DESC');
                foreach ($stmt as $p): ?>
                    <tr>
                        <td style="padding:10px 8px;"><?= $p['id'] ?></td>
                        <td style="padding:10px 8px;"><?= htmlspecialchars($p['nome']) ?></td>
                        <td style="padding:10px 8px;"><?= htmlspecialchars($p['tipo_produto']) ?></td>
                        <td style="padding:10px 8px;"><?= htmlspecialchars($p['ncm']) ?></td>
                        <td style="padding:10px 8px;"><?= htmlspecialchars($p['unidade_medida']) ?></td>
                        <td style="padding:10px 8px;"><?= number_format($p['peso_liquido'],2,',','.') ?></td>
                        <td style="padding:10px 8px;"><?= number_format($p['peso_bruto'],2,',','.') ?></td>
                        <td style="padding:10px 8px;">
                            <a href="produtos_editar.php?id=<?= $p['id'] ?>" style="color:#1857d8;font-weight:600;text-decoration:none;">Editar</a>
                            <!-- <a href="produtos_excluir.php?id=<?= $p['id'] ?>" style="color:#d81b60;font-weight:600;margin-left:12px;text-decoration:none;">Excluir</a> -->
                        </td>
                    </tr>
                <?php endforeach;
            } catch (PDOException $e) {
                echo '<tr><td colspan="8" style="color:red;font-weight:bold;padding:24px;">Erro ao consultar produtos: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
