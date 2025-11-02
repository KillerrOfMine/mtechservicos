<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'superuser'])) {
    header('Location: /erp/login.php');
    exit;
}
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/header.php';

// Listar usuários
$stmt = $pdo->query('SELECT id, usuario, nome, email, role, ativo FROM usuarios ORDER BY usuario ASC');
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listar perfis disponíveis
$perfis = [];
$stmtp = $pdo->query('SELECT id, nome FROM perfis ORDER BY nome ASC');
while ($row = $stmtp->fetch(PDO::FETCH_ASSOC)) {
  $perfis[] = $row;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciamento de Usuários</title>
  <style>
    body { background: #f6f8fa; font-family: 'Segoe UI', Arial, sans-serif; }
    .container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 24px #1857d820;
      padding: 36px 36px 28px 36px;
    }
    h1 { color: #1857d8; margin-bottom: 24px; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 24px;
      background: #fff;
      font-size: 1rem;
      box-shadow: 0 2px 8px #1857d820;
      border-radius: 10px;
      overflow: hidden;
    }
    th, td {
      padding: 13px 10px;
      border-bottom: 1px solid #e3eaf2;
      text-align: left;
      font-weight: 400;
    }
    th {
      background: #1857d8;
      color: #fff;
      font-weight: 600;
      position: sticky;
      top: 0;
      z-index: 2;
      letter-spacing: 0.02em;
    }
    tr:nth-child(even) td {
      background: #f6f8fa;
    }
    tr:last-child td {
      border-bottom: none;
    }
    .btn {
  background: linear-gradient(90deg, #1857d8 80%, #1565c0 100%);
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 2px 10px;
  font-size: 0.85rem;
  cursor: pointer;
  margin-right: 4px;
  margin-bottom: 0;
  height: 26px;
  min-width: 50px;
  width: auto;
  line-height: 1.1;
  transition: background 0.2s, box-shadow 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 1px 4px #1857d820;
  font-weight: 500;
  letter-spacing: 0.01em;
    }
    .btn-danger {
  background: linear-gradient(90deg, #d81b1b 80%, #b71c1c 100%) !important;
  color: #fff !important;
  border: none;
  font-weight: 500;
  font-size: 0.85rem;
  padding: 2px 10px;
    }
      .btn-edit {
  background: #1565c0;
  color: #fff;
  border-radius: 4px;
  padding: 2px 8px;
  font-size: 0.80rem;
  font-weight: 500;
  border: none;
  margin-right: 4px;
  transition: background 0.2s;
      }
    .btn-edit { background: #f6b800; color: #222; }
    .btn-ativo { background: #22aaff; color: #fff; }
    .btn-inativo { background: #aaa; color: #fff; }
    .acoes-cell {
      display: flex;
      flex-direction: row;
      gap: 8px;
        </td>
        <td>
          <span class="status <?= $u['ativo'] ? 'ativo' : 'inativo' ?>" style="margin-right:10px;">Status:</span>
          <label class="switch" onclick="toggleAtivo(<?= $u['id'] ?>, !this.querySelector('input').checked)">
            <input type="checkbox" <?= $u['ativo'] ? 'checked' : '' ?> />
            <span class="slider"></span>
          </label>
        </td>
    .modal {
      display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh;
      background: rgba(24,87,216,0.10); align-items: center; justify-content: center;
    }
    .modal-content {
  background: #fff; border-radius: 10px; box-shadow: 0 1px 8px #1857d820; padding: 16px; min-width: 240px; max-width: 320px;
    }
  .modal-header { font-size: 1.05em; font-weight: 700; color: #1857d8; margin-bottom: 10px; }
    .modal-actions { margin-top: 18px; text-align: right; }
  .modal input, .modal select { width: 100%; padding: 5px; margin-bottom: 8px; border-radius: 6px; border: 1px solid #eee; font-size: 0.92em; }
  .status { font-size: 0.85em; font-weight: 600; padding: 2px 6px; border-radius: 6px; }
    .status.ativo { background: #22aaff; color: #fff; }
    .status.inativo { background: #aaa; color: #fff; }
  </style>
</head>
<body>
  <div class="container" style="max-width:900px;">
    <h1 style="font-size:1.15rem; margin-bottom:10px;">Gerenciamento de Usuários</h1>
    <button class="btn" style="font-size:0.85rem; padding:2px 10px;" onclick="openModal('criar')">Adicionar Usuário</button>
    <button class="btn" style="background:#1565c0;margin-left:4px;font-size:0.85rem; padding:2px 10px;" onclick="window.location.href='permissoes_usuario.php'">Permissões</button>
    <table style="font-size:0.90rem;">
      <tr>
        <th style="padding:6px 4px;">Usuário</th>
        <th style="padding:6px 4px;">Email</th>
        <th style="padding:6px 4px;">Nome</th>
        <th style="padding:6px 4px;">Perfis</th>
        <th style="padding:6px 4px;">Status</th>
        <th style="padding:6px 4px;">Ações</th>
      </tr>
      <?php foreach ($usuarios as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['usuario']) ?></td>
        <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
        <td><?= htmlspecialchars($u['nome']) ?></td>
        <td>
          <?php
            // Buscar perfis vinculados ao usuário
            $stmtup = $pdo->prepare('SELECT p.nome FROM usuario_perfil up JOIN perfis p ON up.perfil_id = p.id WHERE up.usuario_id = ?');
            $stmtup->execute([$u['id']]);
            $perfis_usuario = $stmtup->fetchAll(PDO::FETCH_COLUMN);
            echo htmlspecialchars(implode(', ', $perfis_usuario));
          ?>
        </td>
        <td><span class="status <?= $u['ativo'] ? 'ativo' : 'inativo' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
        <td class="acoes-cell">
          <button class="btn btn-edit" title="Editar" onclick="openModal('editar', <?= htmlspecialchars(json_encode($u)) ?>)">Editar</button>
          <button class="btn btn-danger" title="Excluir" onclick="if(confirm('Confirma excluir?')) location.href='excluir_usuario.php?id=<?= $u['id'] ?>'">Excluir</button>
          <button class="btn <?= $u['ativo'] ? 'btn-inativo' : 'btn-ativo' ?>" title="<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>" onclick="location.href='ativar_usuario.php?id=<?= $u['id'] ?>&acao=<?= $u['ativo'] ? 'desativar' : 'ativar' ?>'">
            <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- Modal de criação/edição -->
  <div class="modal" id="modalUsuario" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(24,87,216,0.10);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;">
  <div class="modal-content" style="margin:auto;max-width:600px;width:96vw;max-height:90vh;overflow-y:auto;background:#f8fbff;border-radius:18px;box-shadow:0 4px 24px #1857d820;padding:40px 36px;">
      <div class="modal-header" id="modalTitulo" style="font-size:1.25em;font-weight:700;color:#1857d8;margin-bottom:18px;text-align:center;">Novo Usuário</div>
      <form id="formUsuario" method="post" action="salvar_usuario.php" style="display:flex;flex-direction:column;gap:12px;">
        <input type="hidden" name="id" id="usuarioId">
        <label for="nomeInput" style="font-weight:500;margin-bottom:2px;">Nome</label>
        <input type="text" name="nome" id="nomeInput" required style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
        <label for="usuarioInput" style="font-weight:500;margin-bottom:2px;">Usuário</label>
        <input type="text" name="usuario" id="usuarioInput" required style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
        <label for="emailInput" style="font-weight:500;margin-bottom:2px;">Email</label>
        <input type="email" name="email" id="emailInput" required style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
        <div id="senhaFields"></div>
        <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
          <input type="checkbox" id="redefinirSenhaInput" name="redefinir_senha" value="1" style="width:18px;height:18px;">
          <label for="redefinirSenhaInput" style="font-weight:500;cursor:pointer;">Solicitar redefinição de senha no próximo login</label>
        </div>
        <div style="font-weight:500;margin-bottom:2px;margin-top:8px;">Perfis</div>
        <div style="position:relative;">
          <div id="checkboxPerfis" style="max-height:140px;overflow-y:auto;border:1px solid #e3eaf2;padding:8px;border-radius:8px;background:#fff;">
            <div style="display:grid;grid-template-columns:32px 1fr;align-items:center;gap:0 0;">
              <?php foreach ($perfis as $p): ?>
                <div style="display:flex;align-items:center;justify-content:center;height:32px;">
                  <input type="checkbox" name="perfis[]" value="<?= $p['id'] ?>" class="perfil-checkbox" style="margin:0;vertical-align:middle;">
                </div>
                <div style="display:flex;align-items:center;height:32px;">
                  <span style="vertical-align:middle;"><?= htmlspecialchars($p['nome']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="modal-actions" style="display:flex;gap:16px;justify-content:center;margin-top:18px;">
          <button type="button" class="btn" style="min-width:110px;font-weight:700;" onclick="closeModal()">Cancelar</button>
          <button type="button" class="btn btn-edit" style="min-width:110px;font-weight:700;" onclick="salvarUsuarioAjax()">Salvar</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    function salvarUsuarioAjax() {
      var form = document.getElementById('formUsuario');
      var perfisMarcados = Array.from(document.querySelectorAll('.perfil-checkbox')).filter(cb => cb.checked);
      if (!form.usuarioInput.value.trim() || !form.nomeInput.value.trim() || perfisMarcados.length === 0) {
        alert('Preencha todos os campos.');
        return;
      }
      var formData = new FormData(form);
      fetch('salvar_usuario.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.sucesso) {
          closeModal();
          location.reload();
        } else {
          alert(data.mensagem || 'Erro ao salvar usuário.');
        }
      })
      .catch(() => {
        alert('Erro ao salvar usuário.');
      });
    }

    function toggleAtivo(id, ativo) {
      fetch('ativar_usuario.php?id=' + id + '&acao=' + (ativo ? 'ativar' : 'desativar'))
        .then(() => location.reload());
    }

    function openModal(tipo, usuario = null) {
      document.getElementById('modalUsuario').style.display = 'flex';
      var senhaFields = document.getElementById('senhaFields');
      if (tipo === 'criar') {
        document.getElementById('modalTitulo').innerText = 'Novo Usuário';
        document.getElementById('formUsuario').reset();
        document.getElementById('usuarioId').value = '';
        document.getElementById('emailInput').value = '';
        document.querySelectorAll('.perfil-checkbox').forEach(cb => cb.checked = false);
        senhaFields.innerHTML = `
          <div style="display:flex;gap:16px;align-items:flex-end;">
            <div style="flex:1;display:flex;flex-direction:column;">
              <label for="senhaInput" style="font-weight:500;margin-bottom:2px;">Senha</label>
              <input type="password" name="senha" id="senhaInput" required style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
            <div style="flex:1;display:flex;flex-direction:column;">
              <label for="confirmaSenhaInput" style="font-weight:500;margin-bottom:2px;">Confirme a Senha</label>
              <input type="password" name="confirma_senha" id="confirmaSenhaInput" required style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
          </div>
        `;
      } else if (tipo === 'editar' && usuario) {
        senhaFields.innerHTML = `
          <div style="display:flex;gap:16px;align-items:flex-end;">
            <div style="flex:1;display:flex;flex-direction:column;">
              <label for="novaSenhaInput" style="font-weight:500;margin-bottom:2px;">Alterar Senha</label>
              <input type="password" name="nova_senha" id="novaSenhaInput" style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
            <div style="flex:1;display:flex;flex-direction:column;">
              <label for="confirmaNovaSenhaInput" style="font-weight:500;margin-bottom:2px;">Confirme a Nova Senha</label>
              <input type="password" name="confirma_nova_senha" id="confirmaNovaSenhaInput" style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
          </div>
        `;
        document.getElementById('modalTitulo').innerText = 'Editar Usuário';
        document.getElementById('usuarioId').value = usuario.id;
  document.getElementById('usuarioInput').value = usuario.usuario;
  document.getElementById('emailInput').value = usuario.email || '';
  document.getElementById('nomeInput').value = usuario.nome;
        // Buscar perfis do usuário para marcar no select
        fetch('listar_perfis_usuario.php?id=' + usuario.id)
          .then(response => response.json())
          .then(data => {
            document.querySelectorAll('.perfil-checkbox').forEach(cb => {
              cb.checked = data.includes(cb.value);
            });
          });
      }
      document.getElementById('buscaPerfil').value = '';
      filtrarPerfis();
    }

    // Função de marcar/desmarcar todos removida

    // Função de filtro de perfis removida
    function closeModal() {
      document.getElementById('modalUsuario').style.display = 'none';
    }
    // Removido: não fecha modal ao clicar fora
  </script>
</body>
</html>
