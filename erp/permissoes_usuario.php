<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'superuser'])) {
    header('Location: /erp/login.php');
    exit;
}
require_once __DIR__ . '/includes/db_connect.php';
$perfil_id = $_GET['perfil_id'] ?? null;
// Salvar permissões do perfil (antes de qualquer saída)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $perfil_id) {
    $novas_permissoes = $_POST['permissoes'] ?? [];
    $stmt = $pdo->prepare('UPDATE perfis SET permissoes = :permissoes WHERE id = :id');
    $stmt->execute([':permissoes' => json_encode($novas_permissoes), ':id' => $perfil_id]);
    header('Location: permissoes_usuario.php?perfil_id=' . $perfil_id . '&ok=1');
    exit;
}
require_once __DIR__ . '/includes/header.php';
// Buscar perfis
$stmtp = $pdo->query('SELECT id, nome, permissoes FROM perfis ORDER BY nome ASC');
$perfis = $stmtp->fetchAll(PDO::FETCH_ASSOC);
// Buscar permissões disponíveis do banco, agrupadas por módulo
$stmt = $pdo->query('SELECT modulo, nome, chave FROM permissoes_disponiveis ORDER BY modulo, nome');
$permissoes_disponiveis = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $permissoes_disponiveis[$row['modulo']][] = $row;
}
// Permissões do perfil selecionado
$permissoes_perfil = [];
if ($perfil_id) {
    foreach ($perfis as $p) {
        if ($p['id'] == $perfil_id) {
            $permissoes_perfil = json_decode($p['permissoes'], true) ?? [];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Permissionamento por Perfil</title>
    <link rel="stylesheet" href="/erp/assets/theme.css">
    <style>
        body { background: #f6f8fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .container { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #1857d820; padding: 32px; }
        h1 { color: #1857d8; margin-bottom: 24px; }
        label { display: block; margin: 12px 0 6px; font-weight: 500; }
        .perms { margin-bottom: 24px; }
        .btn { background: #1857d8; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 1rem; cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h1>Permissionamento por Perfil</h1>
    <button class="btn" style="margin-bottom:18px;" onclick="document.getElementById('modalCriarPerfil').style.display='block'">Criar Perfil</button>
    <form method="get" style="margin-bottom:24px;">
        <label for="perfil_id">Selecione o perfil:</label>
        <select id="perfil_id" name="perfil_id" onchange="this.form.submit()">
            <option value="">Selecione um perfil</option>
            <?php foreach ($perfis as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($perfil_id == $p['id'] ? 'selected' : '') ?>><?= htmlspecialchars($p['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($perfil_id): ?>
        <button type="button" class="btn" style="margin-left:12px;" onclick="document.getElementById('modalEditarPerfil').style.display='block'">Editar Nome do Perfil</button>
        <?php endif; ?>
    </form>
    <!-- Modal de criação de perfil -->
    <div id="modalCriarPerfil" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;">
        <div style="background:#fff;max-width:400px;margin:60px auto;padding:32px;border-radius:16px;box-shadow:0 2px 12px #1857d820;">
            <h2>Criar Novo Perfil</h2>
            <form id="formNovoPerfil" onsubmit="criarPerfil(event)">
                <input type="text" id="novoPerfilNome" name="novoPerfilNome" placeholder="Nome do perfil" style="width:100%;padding:8px;margin-bottom:18px;">
                <button type="submit" class="btn">Criar</button>
                <button type="button" class="btn" style="background:#888;margin-left:8px;" onclick="document.getElementById('modalCriarPerfil').style.display='none'">Cancelar</button>
            </form>
        </div>
    </div>
    <!-- Modal de edição de nome do perfil -->
    <div id="modalEditarPerfil" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);z-index:9999;">
        <div style="background:#fff;max-width:400px;margin:60px auto;padding:32px;border-radius:16px;box-shadow:0 2px 12px #1857d820;">
            <h2>Editar Nome do Perfil</h2>
            <form id="formEditarPerfil" onsubmit="editarPerfil(event)">
                <input type="text" id="editarPerfilNome" name="editarPerfilNome" value="<?= htmlspecialchars($perfis[array_search($perfil_id, array_column($perfis, 'id'))]['nome'] ?? '') ?>" style="width:100%;padding:8px;margin-bottom:18px;">
                <input type="hidden" id="editarPerfilId" name="editarPerfilId" value="<?= $perfil_id ?>">
                <button type="submit" class="btn">Salvar</button>
                <button type="button" class="btn" style="background:#888;margin-left:8px;" onclick="document.getElementById('modalEditarPerfil').style.display='none'">Cancelar</button>
            </form>
        </div>
    </div>
    <script>
    function criarPerfil(e) {
        e.preventDefault();
        var nome = document.getElementById('novoPerfilNome').value.trim();
        if (!nome) return alert('Informe o nome do perfil!');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'criar_perfil.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Perfil criado com sucesso!');
                location.reload();
            } else {
                alert('Erro ao criar perfil: ' + xhr.responseText);
            }
        };
        xhr.send('nome=' + encodeURIComponent(nome));
    }

    function editarPerfil(e) {
        e.preventDefault();
        var nome = document.getElementById('editarPerfilNome').value.trim();
        var id = document.getElementById('editarPerfilId').value;
        if (!nome) return alert('Informe o novo nome do perfil!');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'editar_perfil.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                alert('Nome do perfil atualizado!');
                location.reload();
            } else {
                alert('Erro ao editar perfil: ' + xhr.responseText);
            }
        };
        xhr.send('id=' + encodeURIComponent(id) + '&nome=' + encodeURIComponent(nome));
    }
    </script>
    <?php if ($perfil_id): ?>
    <form method="post">
        <div class="perms" id="perms-list">
            <?php foreach ($permissoes_disponiveis as $modulo => $lista): ?>
                <fieldset style="margin-bottom:18px;" class="modulo-fieldset" data-modulo="<?= htmlspecialchars($modulo) ?>">
                    <legend style="font-weight:bold;color:#1857d8;">
                        <?= htmlspecialchars($modulo) ?>
                        <button type="button" class="btn btn-mini" style="margin-left:12px;padding:2px 10px;font-size:0.9em;" onclick="toggleModuloPerms('<?= htmlspecialchars($modulo) ?>', true)">Marcar todos</button>
                        <button type="button" class="btn btn-mini" style="margin-left:4px;padding:2px 10px;font-size:0.9em;background:#888;" onclick="toggleModuloPerms('<?= htmlspecialchars($modulo) ?>', false)">Desmarcar todos</button>
                    </legend>
                    <?php foreach ($lista as $perm): ?>
                        <?php if (is_array($perm) && isset($perm['chave']) && isset($perm['nome'])): ?>
                            <label><input type="checkbox" name="permissoes[]" value="<?= htmlspecialchars($perm['chave']) ?>" <?= in_array($perm['chave'], $permissoes_perfil) ? 'checked' : '' ?>> <?= htmlspecialchars($perm['nome']) ?></label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn">Salvar Permissões</button>
    </form>
    <?php endif; ?>
    <a href="usuarios.php" class="btn" style="background:#888;margin-top:24px;">Voltar</a>
</div>
<script>
function toggleModuloPerms(modulo, marcar) {
    document.querySelectorAll('.modulo-fieldset[data-modulo="'+modulo+'"] input[type=checkbox]').forEach(cb => {
        cb.checked = marcar;
    });
}
</script>
</body>
</html>
