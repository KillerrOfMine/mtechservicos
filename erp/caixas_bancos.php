<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /erp/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" style="font-family: 'Inter', sans-serif;">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Caixa e Bancos</title>
<link rel="stylesheet" href="/erp/assets/theme.css">
<style>
/* Modal side menu esquerdo */
.erp-modal-side {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 420px;
    max-width: 98vw;
    height: 100vh;
    background: #fff;
    box-shadow: 4px 0 32px rgba(24,87,216,0.10);
    z-index: 9999;
    border-radius: 0 24px 24px 0;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    overflow-y: auto;
}
.erp-modal-side.show {
    display: block;
    transform: translateX(0);
}
.erp-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.45);
    z-index: 9998;
}
.erp-modal-overlay.show {
    display: block;
}
/* Modais */
.erp-modal {
    display: none;
    position: fixed;
    top:0; left:0;
    width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(4px);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    transition: opacity 0.2s ease;
    opacity: 0;
}
.erp-modal.show { display:flex; opacity:1; }
.erp-modal-content {
    background: var(--bg-card, #fff);
    border-radius: 16px;
    padding: 28px;
    width: 640px;
    max-width: 95vw;
    box-shadow: 0 0 18px #0005;
    position: relative;
    color: var(--text-body, #222);
}
.campo-valor-container {
    display:flex;
    align-items:center;
    border:1px solid #dbeafe;
    border-radius:8px;
    background:#f3f6fa;
    padding:0 8px;
}
body.dark-theme .campo-valor-container {
    border:1px solid #444;
    background:#22242a;
}
.campo-valor-prefixo { margin-right:4px; color:#1857d8; }
body.dark-theme .campo-valor-prefixo { color:#22aaff; }
.campo-valor-input {
    width:100%; background:none; border:none; color:inherit; padding:10px 4px;
    font-size:1em;
}
.search-input {
    padding:8px 12px;
    border-radius:6px;
    border:1px solid #dbeafe;
    background:#fff;
    color:#222;
    outline:none;
    font-size:1em;
    transition:background 0.2s, color 0.2s, border 0.2s;
}
body.dark-theme .search-input {
    background:#22242a;
    color:#fff;
    border:1px solid #444;
}
.container {
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-radius: 16px;
    background: var(--bg-card, #fff);
    margin-bottom: 24px;
}
.card {
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-radius: 16px;
    background: var(--bg-card, #fff);
}
body.dark-theme .container, body.dark-theme .card {
    background: #22242a;
    color: #e3e6ee;
}
</style>
</head>
<body style="font-family: 'Inter', sans-serif; margin:0;">

<?php include 'includes/header.php'; ?>
<?php include 'includes/modal_pesquisar_cadastro.php'; ?>


<div id="caixa-bancos-header" class="container" style="padding:24px 16px 16px 16px; position:relative;">
    <div class="header-flex" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <h2 style="margin:0 0 12px 0;font-size:1.4em;font-weight:700;">Caixa e Bancos</h2>
            <div class="header-actions" style="display:flex;align-items:center;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
                <input type="text" placeholder="Pesquise por cliente" class="search-input" style="width:220px;" />
                <button class="btn-filtro" aria-label="Buscar">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="vertical-align:middle;">
                        <circle cx="9" cy="9" r="7" stroke="#1565c0" stroke-width="2" fill="none"/>
                        <line x1="15" y1="15" x2="19" y2="19" stroke="#1565c0" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
                <button class="btn-filtro" aria-label="Configurações">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" style="vertical-align:middle;">
                        <circle cx="10" cy="10" r="3" stroke="#1565c0" stroke-width="2" fill="none"/>
                        <path d="M2 10a8 8 0 0 1 1.6-4.8l1.4 1.4a6 6 0 0 0 0 6.8l-1.4 1.4A8 8 0 0 1 2 10zm16 0a8 8 0 0 1-1.6 4.8l-1.4-1.4a6 6 0 0 0 0-6.8l1.4-1.4A8 8 0 0 1 18 10z" stroke="#1565c0" stroke-width="1.5" fill="none"/>
                    </svg>
                </button>
                <button class="btn-chip" id="btnFiltroTempo" onclick="abrirModalFiltroTempo()" style="font-weight:600;font-size:1.08em;">
                    <span id="labelFiltroTempo">Últimos 30 dias</span>
                </button>
                <button class="btn-filtro" id="btnLimparTodosFiltros" onclick="limparTodosFiltros()" style="font-weight:600;font-size:1.08em;">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" style="vertical-align:middle;">
                        <circle cx="10" cy="10" r="8" stroke="#1565c0" stroke-width="2"/>
                        <line x1="6" y1="10" x2="14" y2="10" stroke="#1565c0" stroke-width="2"/>
                    </svg> limpar filtros
                </button>
            </div>
        </div>
    <div class="header-buttons" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button class="btn" onclick="abrirModalTransferencia()">Transferir</button>
            <button class="btn" onclick="abrirModalLancamento()">Novo Lançamento</button>
        </div>
    </div>
</div>

<!-- Modal Transferência -->
<div id="modalTransferencia" class="erp-modal">
    <div class="erp-modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:1.35em;font-weight:700;">Transferência entre Contas</h2>
            <button type="button" class="btn" onclick="fecharModalTransferencia()" style="width:44px;height:44px;border-radius:12px;font-size:1.3em;display:flex;align-items:center;justify-content:center;">✖</button>
        </div>
        <form id="formTransferencia" onsubmit="salvarTransferencia(event)">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div>
                    <label style="color:#1565c0;font-weight:600;">Conta de Origem</label>
                    <select name="conta_origem" required class="input">
                        <option value="">Selecione</option>
                        <option>Caixa</option>
                        <option>Banco do Brasil</option>
                        <option>PagBank</option>
                        <option>Conta Corrente</option>
                        <option>Conta Poupança</option>
                    </select>
                </div>
                <div>
                    <label style="color:#1565c0;font-weight:600;">Conta de Destino</label>
                    <select name="conta_destino" required class="input">
                        <option value="">Selecione</option>
                        <option>Caixa</option>
                        <option>Banco do Brasil</option>
                        <option>PagBank</option>
                        <option>Conta Corrente</option>
                        <option>Conta Poupança</option>
                    </select>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div>
                    <label style="color:#1565c0;font-weight:600;">Valor</label>
                    <div class="campo-valor-container">
                        <span class="campo-valor-prefixo">R$</span>
                        <input type="text" class="campo-valor-input input" name="valor_transferencia" required placeholder="0,00" />
                    </div>
                </div>
                <div>
                    <label style="color:#1565c0;font-weight:600;">Data</label>
                    <input type="date" name="data_transferencia" value="<?php echo date('Y-m-d'); ?>" required class="input" style="max-width:140px;">
                </div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="color:#1565c0;font-weight:600;">Observação</label>
                <textarea name="observacao" placeholder="Ex: transferência entre contas" class="input" style="width:100%;min-height:40px;max-height:60px;box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:16px;margin-top:18px;">
                <button type="button" class="btn" onclick="fecharModalTransferencia()">Cancelar</button>
                <button type="submit" class="btn">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Lançamento -->
<div id="modalLancamento" class="erp-modal">
    <div class="erp-modal-content" style="width:100%;max-width:600px;box-sizing:border-box;padding:18px 24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <h2 style="margin:0;font-size:1.18em;font-weight:700;">Incluir Lançamento</h2>
            <button type="button" class="btn" onclick="fecharModalLancamento()" style="width:38px;height:38px;border-radius:10px;font-size:1.1em;display:flex;align-items:center;justify-content:center;">✖</button>
        </div>
        <form id="formLancamento" enctype="multipart/form-data" onsubmit="salvarLancamento(event)">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:8px;">
                <div>
                    <label for="categoria" style="color:#1565c0;font-weight:600;font-size:0.98em;">Categoria</label>
                    <select id="categoria" name="categoria" class="input" required style="height:36px;font-size:0.98em;">
                        <option value="">Selecione</option>
                        <option value="1">Categoria 1</option>
                        <option value="2">Categoria 2</option>
                    </select>
                </div>
                <div>
                    <label for="tipo" style="color:#1565c0;font-weight:600;font-size:0.98em;">Tipo</label>
                    <select id="tipo" name="tipo" class="input" required style="height:36px;font-size:0.98em;">
                        <option value="">Selecione</option>
                        <option value="entrada">Entrada</option>
                        <option value="saida">Saída</option>
                    </select>
                </div>
                <div>
                    <label for="conta" style="color:#1565c0;font-weight:600;font-size:0.98em;">Conta</label>
                    <select id="conta" name="conta" class="input" required style="height:36px;font-size:0.98em;">
                        <option value="">Selecione</option>
                        <option value="caixa">Caixa</option>
                        <option value="banco">Banco</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:8px;">
                <label for="cliente" style="color:#1565c0;font-weight:600;font-size:0.98em;">Cliente/Fornecedor</label>
                <div style="display:flex;align-items:center;gap:6px;">
                    <input type="text" id="cliente" name="cliente" class="input" placeholder="Digite o nome ou razão social" autocomplete="off" style="flex:1;height:30px;font-size:0.98em;">
                    <button type="button" class="btn" onclick="abrirModalPesquisarCadastro()" style="padding:0 8px;display:flex;align-items:center;justify-content:center;height:30px;width:30px;min-width:30px;border-radius:8px;">
                        <span class="material-icons" style="font-size:1.1em;">search</span>
                    </button>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;">
                <div>
                    <label for="valor" style="color:#1565c0;font-weight:600;font-size:0.98em;">Valor</label>
                    <div class="campo-valor-container" style="height:36px;">
                        <span class="campo-valor-prefixo">R$</span>
                        <input type="text" id="valor" name="valor" class="campo-valor-input input" required placeholder="0,00" style="height:36px;font-size:0.98em;" />
                    </div>
                </div>
                <div>
                    <label for="data" style="color:#1565c0;font-weight:600;font-size:0.98em;">Data</label>
                    <input type="date" id="data" name="data" class="input" required value="<?php echo date('Y-m-d'); ?>" style="max-width:120px;height:36px;font-size:0.98em;">
                </div>
            </div>
            <div style="margin-bottom:8px;">
                <label for="observacao" style="color:#1565c0;font-weight:600;font-size:0.98em;">Observação</label>
                <textarea id="observacao" name="observacao" class="input" placeholder="Ex: transferência entre contas" style="width:100%;min-height:32px;max-height:40px;box-sizing:border-box;font-size:0.98em;"></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px;">
                <button type="button" class="btn" onclick="fecharModalLancamento()" style="height:36px;font-size:0.98em;">Cancelar</button>
                <button type="submit" class="btn" style="height:36px;font-size:0.98em;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Filtro de Tempo -->
<div id="modalFiltroTempo" class="erp-modal-content" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:999;max-width:340px;margin:auto;">
    <div class="erp-modal-title" style="margin-bottom:18px;">Filtros de Tempo</div>
    <div style="font-weight:500;margin-bottom:10px;">Período</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">
        <button class="btn-chip chip-ativo" onclick="selecionarFiltroTempo('ultimos30')">Últimos 30 dias</button>
        <button class="btn-chip" onclick="selecionarFiltroTempo('semfiltro')">Sem filtro</button>
        <button class="btn-chip" onclick="selecionarFiltroTempo('dodia')">Do dia</button>
        <button class="btn-chip" onclick="selecionarFiltroTempo('dasemana')">Da semana</button>
        <button class="btn-chip" onclick="selecionarFiltroTempo('domes')">Do mês</button>
        <button class="btn-chip" onclick="selecionarFiltroTempo('intervalo')">Intervalo</button>
        <button class="btn-chip" onclick="selecionarFiltroTempo('semcompetencia')">Sem competência</button>
    </div>
    <div class="erp-modal-actions">
        <button class="btn" onclick="aplicarFiltroTempo()">aplicar</button>
        <button class="btn" onclick="fecharModalFiltroTempo()">cancelar</button>
    </div>
</div>

<!-- Modal Filtro de Categoria -->
<div id="modalFiltroCategoria" class="erp-modal-content" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:999;max-width:340px;margin:auto;">
    <div class="erp-modal-title" style="margin-bottom:18px;">Filtro de Categoria</div>
    <div style="margin-bottom:18px;">
        <label for="selectCategoria">Categoria</label>
        <select id="selectCategoria" class="input">
            <option value="Todas">Todas</option>
            <option value="Receitas">Receitas</option>
            <option value="Despesas">Despesas</option>
            <option value="Transferências">Transferências</option>
        </select>
    </div>
    <div class="erp-modal-actions">
    <button class="btn" onclick="aplicarFiltroCategoria()">aplicar</button>
    <button class="btn" onclick="fecharModalCategoria()">cancelar</button>
    </div>
</div>

<!-- Modal Filtro de Conta -->
<div id="modalFiltroConta" class="erp-modal-content" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:999;max-width:340px;margin:auto;">
    <div class="erp-modal-title" style="margin-bottom:18px;">Filtro de Conta</div>
    <div style="margin-bottom:18px;">
        <label for="selectConta">Conta</label>
        <select id="selectConta" class="input">
            <option value="Todas">Todas</option>
            <option value="Caixa">Caixa</option>
            <option value="Banco">Banco</option>
        </select>
    </div>
    <div class="erp-modal-actions">
    <button class="btn" onclick="aplicarFiltroConta()">aplicar</button>
    <button class="btn" onclick="fecharModalConta()">cancelar</button>
    </div>
</div>

<!-- Tabela -->
<div id="caixa-bancos-table" class="card" style="margin:24px;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;font-size:1em;">
        <thead>
            <tr>
                <th style="padding:10px;">Data</th>
                <th style="padding:10px;">Histórico</th>
                <th style="padding:10px;">Cliente</th>
                <th style="padding:10px;">Categoria</th>
                <th style="padding:10px;">Entradas</th>
                <th style="padding:10px;">Saídas</th>
                <th style="padding:10px;">Origem</th>
            </tr>
        </thead>
        <tbody id="tabelaMovimentacoes">
            <!-- Linhas geradas via PHP ou JS -->
        </tbody>
    </table>
</div>

<script>
// Filtro de período
let filtroTempoSelecionado = localStorage.getItem('filtroTempoSelecionado') || 'ultimos30';
const opcoesFiltro = {
    'ultimos30': 'Últimos 30 dias',
    'semfiltro': 'Sem filtro',
    'dodia': 'Do dia',
    'dasemana': 'Da semana',
    'domes': 'Do mês',
    'intervalo': 'Intervalo',
    'semcompetencia': 'Sem competência'
};
function restaurarFiltroTempo() {
    document.getElementById('labelFiltroTempo').textContent = opcoesFiltro[filtroTempoSelecionado];
}
function abrirModalFiltroTempo() {
    document.getElementById('modalFiltroTempo').style.display = 'block';
}
function fecharModalFiltroTempo() {
    document.getElementById('modalFiltroTempo').style.display = 'none';
}
function selecionarFiltroTempo(tipo) {
    filtroTempoSelecionado = tipo;
    document.querySelectorAll('.btn-chip').forEach(btn => {
        btn.classList.remove('chip-ativo');
        if (btn.textContent.trim().toLowerCase() === opcoesFiltro[tipo].toLowerCase()) {
            btn.classList.add('chip-ativo');
        }
    });
}
function aplicarFiltroTempo() {
    document.getElementById('labelFiltroTempo').textContent = opcoesFiltro[filtroTempoSelecionado];
    localStorage.setItem('filtroTempoSelecionado', filtroTempoSelecionado);
    fecharModalFiltroTempo();
    window.location.search = '?filtro_tempo=' + filtroTempoSelecionado;
}
function limparFiltrosTempo() {
    filtroTempoSelecionado = 'ultimos30';
    document.getElementById('labelFiltroTempo').textContent = opcoesFiltro['ultimos30'];
    localStorage.removeItem('filtroTempoSelecionado');
    fecharModalFiltroTempo();
    window.location.search = '';
}
function limparTodosFiltros() {
    localStorage.removeItem('filtroTempoSelecionado');
    localStorage.removeItem('filtroCategoria');
    localStorage.removeItem('filtroConta');
    document.getElementById('labelFiltroTempo').textContent = 'Últimos 30 dias';
    document.getElementById('btnFiltroCategoriaLabel').textContent = 'filtros';
    document.getElementById('btnFiltroContaLabel').textContent = 'conta';
    window.location.search = '';
}
window.addEventListener('DOMContentLoaded', restaurarFiltroTempo);

// Modal de filtro de categoria
function abrirModalCategoria() {
    document.getElementById('modalFiltroCategoria').style.display = 'block';
}
function fecharModalCategoria() {
    document.getElementById('modalFiltroCategoria').style.display = 'none';
}
function aplicarFiltroCategoria() {
    const categoria = document.getElementById('selectCategoria').value;
    localStorage.setItem('filtroCategoria', categoria);
    fecharModalCategoria();
    window.location.search = '?filtro_categoria=' + categoria;
}
function limparFiltroCategoria() {
    localStorage.removeItem('filtroCategoria');
    fecharModalCategoria();
    window.location.search = '';
}
window.addEventListener('DOMContentLoaded', function() {
    const categoriaSalva = localStorage.getItem('filtroCategoria');
    if (categoriaSalva) {
        document.getElementById('btnFiltroCategoriaLabel').textContent = categoriaSalva;
    }
});

// Modal de filtro de conta
function abrirModalConta() {
    document.getElementById('modalFiltroConta').style.display = 'block';
}
function fecharModalConta() {
    document.getElementById('modalFiltroConta').style.display = 'none';
}
function aplicarFiltroConta() {
    const conta = document.getElementById('selectConta').value;
    localStorage.setItem('filtroConta', conta);
    document.getElementById('btnFiltroContaLabel').textContent = conta;
    fecharModalConta();
    window.location.search = '?filtro_conta=' + conta;
}
window.addEventListener('DOMContentLoaded', function() {
    const contaSalva = localStorage.getItem('filtroConta');
    if (contaSalva) {
        document.getElementById('btnFiltroContaLabel').textContent = contaSalva;
    }
});

// Modal Lançamento
function abrirModalLancamento() {
    document.getElementById('modalLancamento').classList.add('show');
}
function fecharModalLancamento() {
    document.getElementById('modalLancamento').classList.remove('show');
}

// Modal Transferência
function abrirModalTransferencia() {
    document.getElementById('modalTransferencia').classList.add('show');
}
function fecharModalTransferencia() {
    document.getElementById('modalTransferencia').classList.remove('show');
}
</script>

</body>
</html>
