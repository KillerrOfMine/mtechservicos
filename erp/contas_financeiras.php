<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
  header('Location: /erp/login.php');
  exit;
}
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/header.php';
// Buscar contas financeiras
$stmt = $pdo->query('SELECT id, nome, numero_conta, ativo, tipo_conta, banco, agencia, saldo_inicial FROM contas_financeiras ORDER BY nome ASC');
$contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Contas financeiras</title>
  <link rel="stylesheet" href="/erp/assets/theme.css">
  <style>
    body { background: #f6f8fa; font-family: 'Segoe UI', Arial, sans-serif; }
    .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #1857d820; padding: 32px; }
    h1 { color: #1857d8; margin-bottom: 8px; }
    .subtitle { color: #444; font-size: 1.08em; margin-bottom: 18px; }
    .search-bar { width: 100%; max-width: 420px; padding: 10px 14px; border-radius: 8px; border: 1px solid #e3eaf2; font-size: 1em; margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; background: #fff; font-size: 1rem; }
    th, td { padding: 12px 8px; border-bottom: 1px solid #f0f0f0; text-align: left; }
    th { color: #222; font-weight: 600; background: none; }
    tr:last-child td { border-bottom: none; }
    .status-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; background: #4afc8b; margin-left: 8px; }
    .actions-btn {
      background: #1857d8;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5em;
      cursor: pointer;
      margin: 0 0 0 8px;
      box-shadow: 0 2px 8px #1857d820;
      transition: background 0.2s;
    }
    .actions-btn:hover {
      background: #1565c0;
    }
    .actions-menu { position: absolute; background: #fff; box-shadow: 0 2px 12px #1857d820; border-radius: 10px; padding: 12px 0; min-width: 180px; z-index: 999; }
    .actions-menu-item { display: flex; align-items: center; gap: 8px; padding: 8px 18px; cursor: pointer; font-size: 1em; color: #222; transition: background 0.2s; }
    .actions-menu-item:hover { background: #f6f8fa; }
    .lock-icon { font-size: 1.1em; margin-right: 6px; }
  </style>
</head>
<body>
  <div class="container">
  <div style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <h1>Contas financeiras</h1>
      <div class="subtitle">As contas financeiras são utilizadas no caixa, contas a receber e a pagar para classificar as receitas e despesas</div>
    </div>
  <button class="btn-cadastrar" style="height:40px;padding:0 18px;font-size:1em;margin-left:16px;white-space:normal;line-height:1.1;text-align:center;word-break:break-word;" onclick="abrirCadastroConta()">Nova Conta</button>
  </div>
    <table>
      <thead>
        <tr>
          <th>Descrição da conta</th>
          <th>Banco</th>
          <th>Agência / Conta</th>
          <th>Saldo</th>
          <th style="width: 50px;">Ações</th>
        </tr>
      </thead>
      <tbody id="contasBody">
        <?php foreach ($contas as $c): ?>
        <tr>
          <td>
            <?php if ($c['nome'] === 'Caixa'): ?>
              <span class="lock-icon">&#128274;</span>
            <?php endif; ?>
            <?= htmlspecialchars($c['nome']) ?>
            <span style="display: block; font-size: 0.85em; color: #666;"><?= htmlspecialchars($c['tipo_conta'] ?? '') ?></span>
          </td>
          <td><?= htmlspecialchars($c['banco'] ?? '') ?></td>
          <td><?= htmlspecialchars($c['agencia'] ?? '') ?> / <?= htmlspecialchars($c['numero_conta'] ?? '') ?></td>
          <td>R$ <?= number_format($c['saldo_inicial'] ?? 0, 2, ',', '.') ?></td>
          <td>
            <div style="display: flex; align-items: center; justify-content: flex-end;">
              <span class="status-dot" style="background:<?= $c['ativo'] ? '#4afc8b' : '#ccc' ?>; margin-right: 10px;"></span>
              <button class="actions-btn" onclick="abrirMenu(this, <?= $c['id'] ?>)"><span style="font-size:1.6em;display:flex;align-items:center;justify-content:center;letter-spacing:2px;">...</span></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="actionsMenu" class="actions-menu" style="display:none;"></div>
  <!-- Modal de cadastro de conta -->
  <div id="modalCadastroConta" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(24,87,216,0.10);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 24px #1857d820;padding:32px;max-width:400px;margin:auto;">
      <h2 style="color:#1857d8;font-size:1.2em;margin-bottom:18px;text-align:center;">Nova Conta Financeira</h2>
      <form id="formCadastroConta" method="post" action="salvar_conta_financeira.php" style="display:flex;flex-direction:column;gap:12px;">
        <input type="hidden" name="id" id="contaId">
        <label for="nomeConta" style="font-weight:500;">Nome da conta</label>
        <input type="text" name="nome" id="nomeConta" required style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
        
        <label for="tipoConta" style="font-weight:500;">Tipo da conta</label>
        <select name="tipo_conta" id="tipoConta" style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;background-color:white;">
          <option value="Caixa">Caixa</option>
          <option value="Conta Corrente" selected>Conta Corrente</option>
          <option value="Conta Poupança">Conta Poupança</option>
          <option value="Cartão de Crédito">Cartão de Crédito</option>
          <option value="Investimento">Investimento</option>
        </select>

        <label for="banco" style="font-weight:500;">Banco</label>
        <input type="text" name="banco" id="banco" style="padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">

        <div style="display:flex; gap: 12px;">
            <div style="flex: 1;">
                <label for="agencia" style="font-weight:500;">Agência</label>
                <input type="text" name="agencia" id="agencia" style="width: 100%; padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
            <div style="flex: 2;">
                <label for="numeroConta" style="font-weight:500;">Número da conta</label>
                <input type="text" name="numero_conta" id="numeroConta" style="width: 100%; padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
        </div>

        <div style="display:flex; gap: 12px;">
            <div style="flex: 1;">
                <label for="saldoInicial" style="font-weight:500;">Saldo Inicial</label>
                <input type="number" step="0.01" name="saldo_inicial" id="saldoInicial" value="0.00" style="width: 100%; padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
            <div style="flex: 1;">
                <label for="dataSaldo" style="font-weight:500;">Data do Saldo</label>
                <input type="date" name="data_saldo" id="dataSaldo" value="<?= date('Y-m-d') ?>" style="width: 100%; padding:8px;border-radius:8px;border:1px solid #e3eaf2;font-size:1em;">
            </div>
        </div>

        <div style="display:flex;gap:16px;justify-content:center;margin-top:12px;">
          <button type="button" class="btn-cadastrar" onclick="salvarContaAjax()">Salvar</button>
          <button type="button" class="btn-cadastrar" style="background:#888;" onclick="fecharCadastroConta()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
  </div>
  <script>
    function abrirCadastroConta() {
      document.getElementById('modalCadastroConta').style.display = 'flex';
    }
    function fecharCadastroConta() {
      document.getElementById('modalCadastroConta').style.display = 'none';
    }
    function salvarContaAjax() {
      var form = document.getElementById('formCadastroConta');
      var formData = new FormData(form);
      fetch('salvar_conta_financeira.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.sucesso) {
          fecharCadastroConta();
          location.reload();
        } else {
          alert(data.mensagem || 'Erro ao salvar conta.');
        }
      })
      .catch(() => {
        alert('Erro ao salvar conta.');
      });
    }
    const contas = <?php echo json_encode($contas); ?>;
    function filtrarContas() {
      const busca = document.getElementById('searchInput').value.toLowerCase();
      const tbody = document.getElementById('contasBody');
      tbody.innerHTML = '';
      contas.filter(c => c.nome.toLowerCase().includes(busca)).forEach(c => {
        tbody.innerHTML += `<tr><td>${c.nome === 'Caixa' ? '<span class=\"lock-icon\">&#128274;</span>' : ''}${c.nome} <button class=\"actions-btn\" onclick=\"abrirMenu(this, ${c.id})\">&#8942;</button></td><td>${c.numero_conta ?? ''} <span class=\"status-dot\" style=\"background:${c.ativo ? '#4afc8b' : '#ccc'};\"></span></td></tr>`;
      });
    }
    function abrirMenu(btn, id) {
      const menu = document.getElementById('actionsMenu');
      menu.innerHTML = `
        <div class="actions-menu-item">&#11088; preferencial para movimentações</div>
        <div class="actions-menu-item">&#8618; informar número da conta</div>
        <div class="actions-menu-item">&#9940; inativar conta</div>
        <div class="actions-menu-item">&#128465; excluir conta</div>
      `;
      menu.style.display = 'block';
      const rect = btn.getBoundingClientRect();
      menu.style.top = (rect.bottom + window.scrollY + 4) + 'px';
      menu.style.left = (rect.left + window.scrollX - 40) + 'px';
      document.addEventListener('click', fecharMenu);
    }
    function fecharMenu(e) {
      const menu = document.getElementById('actionsMenu');
      if (!menu.contains(e.target)) {
        menu.style.display = 'none';
        document.removeEventListener('click', fecharMenu);
      }
    }
  </script>
</body>
</html>
