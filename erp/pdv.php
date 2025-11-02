<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/db_connect.php';
require_once 'includes/header.php';
?>
<div class="pdv-modern-container pdv-layout-upgrade">
    <!-- Campos duplicados removidos -->
        <div class="pdv-layout-header-grid" style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:24px;margin:32px 0 0 0;align-items:end;">
            <div>
                <label for="vendedor" style="font-size:14px;color:#1766c2;font-weight:600;">Vendedor</label><br>
                <input id="vendedor" type="text" value="<?= htmlspecialchars($_SESSION['nome_usuario'] ?? 'Sem vendedor') ?>" readonly style="height:38px;font-size:15px;padding:6px 12px;border:2px solid #e3eaf5;border-radius:8px;background:#fff;box-shadow:0 1px 4px #0001;width:100%;">
            </div>
            <div style="position:relative;">
                <label for="clienteBusca" style="font-size:14px;color:#1766c2;font-weight:600;">Cliente</label><br>
                <input id="clienteBusca" type="text" value="" placeholder="Consumidor Final" style="height:38px;font-size:15px;padding:6px 12px;border:2px solid #e3eaf5;border-radius:8px;background:#fff;box-shadow:0 1px 4px #0001;width:100%;">
            </div>
            <div style="display:flex;gap:12px;align-items:end;">
                <div style="flex:1;">
                    <label for="produtoBusca" style="font-size:14px;color:#1766c2;font-weight:600;">Produto</label><br>
                    <input id="produtoBusca" type="text" placeholder="Descrição ou código" style="height:38px;font-size:15px;padding:6px 12px;border:2px solid #e3eaf5;border-radius:8px;background:#fff;box-shadow:0 1px 4px #0001;width:100%;">
                </div>
                <div>
                    <label style="font-size:14px;color:#1766c2;font-weight:600;">Qtd</label><br>
                    <input class="pdv-qty-input" type="text" value="1,00" style="height:38px;font-size:15px;padding:6px 12px;border:2px solid #e3eaf5;border-radius:8px;background:#fff;box-shadow:0 1px 4px #0001;width:70px;">
                </div>
                <div id="autocomplete" class="autocomplete-list"></div>
                <div class="pdv-loader" id="pdvLoader"></div>
            </div>
        </div>
    </div>
    <div class="pdv-layout-actions">
        <div class="pdv-layout-actions-row">
        </div>
        <div class="pdv-layout-actions-row">
        </div>
    </div>
    <div class="pdv-layout-list">
        <table class="pdv-layout-table">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Quant.</th>
                    <th>Preço un</th>
                    <th>Preço un final</th>
                    <th>Preço total</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="pdv-layout-empty">Nenhum item adicionado</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pdv-layout-summary">
        <span class="pdv-layout-summary-item">itens <b>0</b></span>
        <span class="pdv-layout-summary-item">quant. <b>0</b></span>
        <span class="pdv-layout-summary-total">total da venda R$ 0,00</span>
    </div>
    <div class="pdv-layout-actions-bottom">
        <button class="pdv-btn pdv-btn-primary pdv-layout-action-btn"><span class="pdv-btn-icon">✔️</span> continuar <span class="pdv-layout-action-shortcut">CTRL+ENTER</span></button>
        <button class="pdv-btn pdv-btn-light pdv-layout-action-btn"><span class="pdv-btn-icon">💾</span> salvar para depois <span class="pdv-layout-action-shortcut">F10</span></button>
    </div>
</div>
<script>
// ...todo o JS do PDV aqui...
var dd = document.getElementById('pdvDropdown');
if (dd) {
  dd.classList.toggle('show');
  document.addEventListener('click', function handler(ev) {
    if (!dd.contains(ev.target)) {
      dd.classList.remove('show');
      document.removeEventListener('click', handler);
    }
  });
}
// Função de busca de produtos direto no PDV
function buscaProdutosPDV(q) {
    q = q.trim();
    if (!q) return [];
    let resultados = [];
    <?php
    $todos = [];
    $stmt = $pdo->query("SELECT id, nome, valor, unidade_medida FROM produtos ORDER BY nome LIMIT 100");
    foreach ($stmt as $p) {
        $todos[] = [
            'id' => $p['id'],
            'nome' => $p['nome'],
            'valor' => $p['valor'],
            'unidade_medida' => $p['unidade_medida']
        ];
    }
    ?>
    const produtos = <?php echo json_encode($todos); ?>;
    q = q.toLowerCase();
    for (let p of produtos) {
        if (p.nome.toLowerCase().includes(q)) resultados.push(p);
        else if ((p.unidade_medida || '').toLowerCase().includes(q)) resultados.push(p);
    }
    return resultados;
}
let timer;
const buscaInput = document.getElementById('produtoBusca');
const autocomplete = document.getElementById('autocomplete');
buscaInput.addEventListener('input', function() {
    clearTimeout(timer);
    const val = this.value.trim();
    if (val.length < 1) {
        autocomplete.style.display = 'none';
        autocomplete.innerHTML = '';
        return;
    }
    timer = setTimeout(() => {
        const data = buscaProdutosPDV(val);
        if (data.length === 0) {
            autocomplete.innerHTML = '<div style="padding:12px;color:#888;">Nenhum produto encontrado</div>';
            autocomplete.style.display = 'block';
            return;
        }
        autocomplete.innerHTML = data.map(p => `<div style='padding:12px;cursor:pointer;' onclick='selecionaProduto(${JSON.stringify(p)})'>${p.nome}</div>`).join('');
        autocomplete.style.display = 'block';
    }, 100);
});
function atualizaTabelaPDV() {
    const tbody = document.querySelector('.pdv-modern-table tbody');
    if (itensPDV.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#bbb;">Nenhum item adicionado</td></tr>';
    } else {
        tbody.innerHTML = itensPDV.map(item => `<tr>
            <td>${item.descricao}</td>
            <td>${item.quantidade.toFixed(2)}</td>
            <td>${formatarMoeda(item.preco_un)}</td>
            <td>${formatarMoeda(item.preco_final)}</td>
            <td>${formatarMoeda(item.preco_total)}</td>
        </tr>`).join('');
    }
    // Atualiza resumo
    const totalItens = itensPDV.length;
    const totalQtd = itensPDV.reduce((acc, item) => acc + item.quantidade, 0);
    const totalVenda = itensPDV.reduce((acc, item) => acc + item.preco_total, 0);
    document.querySelector('.pdv-modern-summary-item b').textContent = totalItens;
    document.querySelectorAll('.pdv-modern-summary-item b')[1].textContent = totalQtd;
    document.querySelector('.pdv-modern-summary-total').textContent = 'total da venda ' + formatarMoeda(totalVenda);
}
// Busca de cliente: apenas AJAX
const clienteInput = document.getElementById('clienteBusca');
let clienteAutocomplete = document.querySelector('.autocomplete-list-cliente');
if (!clienteAutocomplete) {
    clienteAutocomplete = document.createElement('div');
    clienteAutocomplete.className = 'autocomplete-list-cliente';
    clienteInput.parentNode.insertAdjacentElement('afterend', clienteAutocomplete);
}
let clienteTimer;
clienteInput.addEventListener('input', function() {
    clearTimeout(clienteTimer);
    const val = this.value.trim();
    if (val.length < 1) {
        clienteAutocomplete.style.display = 'none';
        clienteAutocomplete.innerHTML = '';
        return;
    }
    clienteTimer = setTimeout(() => {
        fetch('buscar_contatos.php?q=' + encodeURIComponent(val))
            .then(r => {
                if (!r.ok) throw new Error('Erro na requisição: ' + r.status);
                return r.json();
            })
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    clienteAutocomplete.innerHTML = '<div style="padding:12px;color:#888;">Nenhum contato encontrado</div>';
                    clienteAutocomplete.style.display = 'block';
                    return;
                }
                clienteAutocomplete.innerHTML = data.map(c => `<div style='padding:12px;cursor:pointer;' onclick='selecionaCliente(${JSON.stringify(c)})'>${c.nome}</div>`).join('');
                clienteAutocomplete.style.display = 'block';
            })
            .catch(err => {
                clienteAutocomplete.innerHTML = `<div style='padding:12px;color:red;'>Erro ao buscar contatos: ${err.message}</div>`;
                clienteAutocomplete.style.display = 'block';
            });
    }, 200);
});
function selecionaCliente(cliente) {
    clienteInput.value = cliente.nome;
    clienteAutocomplete.style.display = 'none';
    // Aqui você pode adicionar lógica para preencher outros campos ou salvar o contato selecionado
}
</script>
<style>
.autocomplete-list-cliente {
  position: absolute;
  left: 0; right: 0;
  z-index: 10;
  background: #fff;
  border: 1px solid #e3eaf5;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 2px 8px #0001;
}
</style>
