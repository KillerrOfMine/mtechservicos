<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/includes/db_connect.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
  header('Location: /erp/login.php');
  exit;
}

// Inclui header após validação
require_once __DIR__ . '/includes/header.php';

/**
 * Função para renderizar os campos do lançamento no formulário/modal
 */
function renderCamposLancamento(
  $tipo = '',
  $idData = 'dataLancamento',
  $idValor = 'valor',
  $idInputComprovante = 'inputComprovante',
  $idNomeComprovante = 'nomeComprovante'
) {
?>
  <div class="erp-modal-group" style="margin-bottom:14px;">
    <div style="display:flex; gap:12px; margin-bottom:2px;">
      <label style="flex:1;">Categoria</label>
      <label style="flex:1;">Tipo</label>
      <label style="flex:1;">Conta</label>
    </div>
    <div style="display:flex; gap:12px;">
      <select name="categoria" style="flex:1;">
        <option value="">Selecione</option>
        <option>Copiadora</option>
        <option>Despesas Bancárias</option>
        <option>Despesas Mensais</option>
        <option>Empréstimo</option>
        <option>PagBank</option>
      </select>
      <select name="tipo" style="flex:1;">
        <option>Entrada</option>
        <option>Saída</option>
      </select>
      <select name="conta" style="flex:1;">
        <option value="">Selecione</option>
        <option>Caixa</option>
        <option>Banco do Brasil</option>
        <option>PagBank</option>
        <option>Conta Corrente</option>
        <option>Conta Poupança</option>
      </select>
    </div>
  </div>

  <div class="erp-modal-group" style="margin-bottom:14px;">
    <div style="display:flex; gap:12px; margin-bottom:2px;">
      <label style="flex:1;">Data</label>
      <label style="flex:1;">Valor</label>
    </div>
    <div style="display:flex; gap:12px;">
      <input type="date" name="data" id="<?php echo $idData; ?>" value="<?php echo date('Y-m-d'); ?>" style="flex:1;">
      <div class="campo-valor-container" style="flex:1; display:flex; align-items:center;">
        <span class="campo-valor-prefixo">R$</span>
        <input type="text" name="valor" id="<?php echo $idValor; ?>" class="campo-valor-input" placeholder="0,00">
      </div>
    </div>
  </div>

  <div class="erp-modal-group" style="margin-bottom:14px;">
    <label>Histórico</label>
    <textarea name="historico" placeholder="Descreva o lançamento"></textarea>
  </div>

  <div class="erp-modal-group" style="margin-bottom:14px;">
    <label>Cliente / Fornecedor</label>
    <input type="text" name="cliente_fornecedor" placeholder="Digite o nome, CPF ou CNPJ">
  </div>

  <div class="erp-modal-group" style="margin-bottom:14px;">
    <label>Comprovante</label>
    <input
      type="file"
      name="comprovante"
      id="<?php echo $idInputComprovante; ?>"
      accept="image/*,application/pdf"
      onchange="document.getElementById('<?php echo $idNomeComprovante; ?>').innerText = this.files[0]?.name || '';"
    >
    <span id="<?php echo $idNomeComprovante; ?>" class="nome-comprovante"></span>
  </div>
<?php
}
?>

<main class="erp-caixa-bancos">
  <div style="padding:20px; text-align:center;">
    <h1 style="margin-bottom:20px;">Controle Financeiro</h1>
    <button id="abrirModal" class="botao-principal">➕ Novo Lançamento</button>
  </div>
</main>

<!-- MODAL DE LANÇAMENTO -->
<div id="modalLancamento" class="modal-overlay" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h2>Novo Lançamento</h2>
      <button class="fechar-modal" id="fecharModal">✖</button>
    </div>

    <form id="formLancamento" method="post" enctype="multipart/form-data" action="/erp/salvar_lancamento.php">
      <?php renderCamposLancamento(); ?>
      <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:16px;">
        <button type="button" class="botao-cancelar" id="cancelarModal">Cancelar</button>
        <button type="submit" class="botao-salvar">💾 Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- CSS GLOBAL -->
<style>
  body, .erp-caixa-bancos {
    font-family: 'Inter', Arial, sans-serif;
    background: #181a1b;
    color: #fff;
    min-height: 100vh;
  }

  h1, h2, label {
    color: #fff;
  }

  input, select, textarea {
    width: 100%;
    height: 44px;
    border-radius: 8px;
    border: 1px solid #333;
    padding: 10px 12px;
    background: #111;
    color: #fff;
    font-size: 1rem;
  }

  textarea {
    min-height: 60px;
  }

  ::placeholder {
    color: #888;
  }

  .botao-principal {
    background: #1857d8;
    color: #fff;
    padding: 12px 24px;
    border-radius: 24px;
    border: none;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s ease;
    box-shadow: 0 2px 8px #0004;
  }

  .botao-principal:hover {
    filter: brightness(1.2);
  }

  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
  }

  .modal {
    background: #222;
    border-radius: 12px;
    padding: 24px;
    width: 600px;
    max-width: 90%;
    box-shadow: 0 0 20px #0007;
    animation: fadeIn 0.25s ease;
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
  }

  .fechar-modal {
    background: none;
    border: none;
    color: #aaa;
    font-size: 1.4rem;
    cursor: pointer;
  }

  .fechar-modal:hover {
    color: #fff;
  }

  .botao-cancelar {
    background: #444;
    border: none;
    color: #fff;
    border-radius: 24px;
    padding: 10px 20px;
    cursor: pointer;
  }

  .botao-salvar {
    background: #007bff;
    border: none;
    color: #fff;
    border-radius: 24px;
    padding: 10px 22px;
    font-weight: bold;
    cursor: pointer;
  }

  .nome-comprovante {
    display: block;
    font-size: 0.9rem;
    color: #aaa;
    margin-top: 6px;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
  }
</style>

<!-- JS: Modal e máscara de valores -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('modalLancamento');
  const abrir = document.getElementById('abrirModal');
  const fechar = document.getElementById('fecharModal');
  const cancelar = document.getElementById('cancelarModal');

  const toggleModal = (show) => {
    modal.style.display = show ? 'flex' : 'none';
  };

  abrir.addEventListener('click', () => toggleModal(true));
  fechar.addEventListener('click', () => toggleModal(false));
  cancelar.addEventListener('click', () => toggleModal(false));

  // Máscara de valor (moeda brasileira)
  document.querySelectorAll('.campo-valor-input').forEach(input => {
    input.addEventListener('input', e => {
      let v = e.target.value.replace(/\D/g, '');
      v = (v / 100).toFixed(2) + '';
      v = v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
      e.target.value = v;
    });
  });
});
</script>

<footer style="margin-top:60px; text-align:center; padding:24px; color:#666; font-size:0.9rem;">
  © <?php echo date('Y'); ?> - Sistema ERP Personalizado
</footer>
