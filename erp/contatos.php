<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /erp/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contatos</title>
<link rel="stylesheet" href="/erp/assets/theme.css">
</head>
<body style="font-family: 'Inter', sans-serif; margin:0;">
<?php include 'includes/header.php'; ?>

<div class="container" style="padding:32px 24px;max-width:1100px;margin:32px auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2 style="margin:0;font-size:1.6em;font-weight:700;">Contatos</h2>
        <button class="btn" onclick="abrirModalContato()">Incluir contato</button>
    </div>
    <div style="margin-top:24px;">
        <input type="text" class="input" placeholder="Pesquise por nome, fantasia, email ou CPF/CNPJ" style="width:100%;max-width:420px;">
    </div>
    <div style="margin-top:24px;">
        <!-- Tabela de contatos -->
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f6f8fa;">
                    <th style="width:36px;"></th>
                    <th style="padding:10px 6px;text-align:left;font-weight:600;">Nome</th>
                    <th style="padding:10px 6px;text-align:left;font-weight:600;">CPF/CNPJ</th>
                    <th style="padding:10px 6px;text-align:left;font-weight:600;">Cidade</th>
                    <th style="width:48px;"></th>
                </tr>
            </thead>
            <tbody id="listaContatos">
                <!-- Linhas de contatos serão renderizadas via JS/PHP -->
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/modal_contato.php'; ?>

<script>
function abrirModalContato(id = null) {
    document.getElementById('modalContato').classList.add('show');
    document.getElementById('overlayModal').classList.add('show');
    if (id) {
        fetch('buscar_contato.php?id=' + id)
            .then(response => response.json())
            .then(res => {
                if (res.success && res.contato) {
                    const f = document.getElementById('formContato');
                    Object.entries(res.contato).forEach(([key, value]) => {
                        if (f.elements[key]) f.elements[key].value = value ?? '';
                    });
                } else {
                    alert(res.message || 'Contato não encontrado.');
                }
            })
            .catch(() => {
                alert('Erro ao buscar contato.');
            });
    } else {
        document.getElementById('formContato').reset();
    }
}
function fecharModalContato() {
    document.getElementById('modalContato').classList.remove('show');
    document.getElementById('overlayModal').classList.remove('show');
}

function salvarContato(event) {
    event.preventDefault();
    const form = document.getElementById('formContato');
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => { data[key] = value; });
    fetch('salvar_contato.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            alert(res.message || 'Contato salvo com sucesso!');
            fecharModalContato();
            carregarContatos();
        } else {
            alert(res.message || 'Erro ao salvar contato.');
        }
    })
    .catch(() => {
        alert('Erro ao salvar contato.');
    });
}

function carregarContatos() {
    fetch('listar_contatos.php')
        .then(response => response.json())
        .then(res => {
            if (res.success && Array.isArray(res.contatos)) {
                const tbody = document.getElementById('listaContatos');
                tbody.innerHTML = '';
                res.contatos.forEach(c => {
                    const tr = document.createElement('tr');
                    tr.style.background = '#fff';
                    tr.style.borderBottom = '1px solid #f0f0f0';
                    tr.innerHTML = `
                        <td style='padding:8px 6px;text-align:center;'>
                            <span style="display:inline-block;width:28px;height:28px;border-radius:50%;background:#1565c0;color:#fff;font-weight:600;font-size:1em;display:flex;align-items:center;justify-content:center;">${(c.nome||'')[0]||''}</span>
                        </td>
                        <td style='padding:8px 6px;'>${c.nome}
                            <span style="margin-left:8px;cursor:pointer;" onclick="abrirMenuAcoes(this, ${c.id}, '${c.nome.replace(/'/g, '\'')}')">
                                <span style="background:#1565c0;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1.1em;">&#8942;</span>
                            </span>
                        </td>
                        <td style='padding:8px 6px;'>${c.cpf_cnpj || ''}</td>
                        <td style='padding:8px 6px;'>${c.municipio || ''}</td>
                        <td style='padding:8px 6px;text-align:center;'><input type='checkbox'></td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                document.getElementById('listaContatos').innerHTML = '<tr><td colspan="5" style="padding:16px;color:#888;text-align:center;">Nenhum contato encontrado.</td></tr>';
            }
        })
        .catch(() => {
            document.getElementById('listaContatos').innerHTML = '<tr><td colspan="5" style="padding:16px;color:#888;text-align:center;">Erro ao carregar contatos.</td></tr>';
        });
}

// Menu de ações estilo popover
function abrirMenuAcoes(el, id, nome) {
    fecharMenuAcoes();
    const menu = document.createElement('div');
    menu.className = 'menu-acoes-contato';
    menu.style.position = 'absolute';
    menu.style.zIndex = '99999';
    menu.style.background = '#fff';
    menu.style.boxShadow = '0 4px 16px rgba(24,87,216,0.13)';
    menu.style.borderRadius = '12px';
    menu.style.padding = '12px 0';
    menu.style.minWidth = '220px';
    menu.style.fontSize = '1em';
    menu.style.top = (el.getBoundingClientRect().bottom + window.scrollY + 4) + 'px';
    menu.style.left = (el.getBoundingClientRect().left + window.scrollX - 20) + 'px';
    menu.innerHTML = `
        <div style='padding:8px 18px;font-weight:600;display:flex;align-items:center;gap:10px;'>
            <span style='background:#1565c0;color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:1.1em;'>${nome[0]||''}</span>
            ${nome}
        </div>
        <div style='border-top:1px solid #f0f0f0;margin:6px 0;'></div>
        <div class='menu-item' onclick='fazerProposta(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#128188;</span>fazer uma proposta</div>
        <div class='menu-item' onclick='criarPedidoVenda(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#128179;</span>criar um pedido de venda</div>
        <div class='menu-item' onclick='cadastrarNotaFiscal(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#128196;</span>cadastrar uma nota fiscal</div>
        <div style='border-top:1px solid #f0f0f0;margin:6px 0;'></div>
        <div class='menu-item' onclick='tornarVendedor(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#128100;</span>tornar vendedor</div>
        <div class='menu-item' onclick='vincularRegistro(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#128214;</span>vincular a outro registro</div>
        <div class='menu-item' onclick='imprimirFicha(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#128424;</span>imprimir ficha cadastral</div>
        <div style='border-top:1px solid #f0f0f0;margin:6px 0;'></div>
        <div class='menu-item' onclick='consultarVendas(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#9432;</span>consultar últimas vendas</div>
        <div class='menu-item' onclick='consultarCompras(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#9432;</span>consultar últimas compras</div>
        <div class='menu-item' onclick='consultarServicos(${id})' style='padding:8px 18px;cursor:pointer;display:flex;align-items:center;gap:8px;'><span>&#9432;</span>consultar últimos serviços</div>
    `;
    document.body.appendChild(menu);
    window._menuAcoesContato = menu;
    document.addEventListener('mousedown', fecharMenuAcoes);
}
function fecharMenuAcoes() {
    if (window._menuAcoesContato) {
        window._menuAcoesContato.remove();
        window._menuAcoesContato = null;
        document.removeEventListener('mousedown', fecharMenuAcoes);
    }
}
// Funções de ação (placeholders)
function fazerProposta(id) { alert('Proposta para contato ' + id); fecharMenuAcoes(); }
function criarPedidoVenda(id) { alert('Pedido de venda para contato ' + id); fecharMenuAcoes(); }
function cadastrarNotaFiscal(id) { alert('Nota fiscal para contato ' + id); fecharMenuAcoes(); }
function tornarVendedor(id) { alert('Tornar vendedor: ' + id); fecharMenuAcoes(); }
function vincularRegistro(id) { alert('Vincular registro: ' + id); fecharMenuAcoes(); }
function imprimirFicha(id) { alert('Imprimir ficha: ' + id); fecharMenuAcoes(); }
function consultarVendas(id) { alert('Consultar vendas: ' + id); fecharMenuAcoes(); }
function consultarCompras(id) { alert('Consultar compras: ' + id); fecharMenuAcoes(); }
function consultarServicos(id) { alert('Consultar serviços: ' + id); fecharMenuAcoes(); }

window.addEventListener('DOMContentLoaded', carregarContatos);
</script>
<style>
.menu-acoes-contato {
    box-shadow: 0 4px 16px rgba(24,87,216,0.13);
    border-radius: 12px;
    background: #fff;
    min-width: 220px;
    font-size: 1em;
    position: absolute;
    padding: 12px 0;
    z-index: 99999;
}
.menu-acoes-contato .menu-item {
    transition: background 0.15s;
}
.menu-acoes-contato .menu-item:hover {
    background: #f6f8fa;
}
</style>
</body>
</html>
