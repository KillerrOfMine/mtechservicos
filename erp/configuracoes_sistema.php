<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
  header('Location: /erp/login.php');
  exit;
}
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Configurações do Sistema ERP</title>
  <link rel="stylesheet" href="/erp/assets/theme.css">
  <style>
    .menu-tabs {
      display: flex;
      gap: 18px;
      margin-bottom: 18px;
      border-bottom: none;
      justify-content: center;
    }
    .menu-tab-small {
      font-weight: 600;
      color: #888;
      background: #e3eaf2;
      border: none;
      border-radius: 8px;
      padding: 6px 14px;
      font-size: 0.98em;
      cursor: pointer;
      box-shadow: 0 1px 4px #1857d820;
      transition: background 0.2s, color 0.2s;
      margin-bottom: 0;
      min-width: 80px;
      max-width: 140px;
      white-space: nowrap;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .menu-tab-small.active {
      background: #1857d8;
      color: #fff;
      box-shadow: 0 2px 8px #1857d820;
    }
    body.dark-theme .menu-tab-small {
      background: #23272f;
      color: #aaa;
    }
    body.dark-theme .menu-tab-small.active {
      background: #1857d8;
      color: #fff;
    }
    body { background: #f6f8fa; font-family: 'Segoe UI', Arial, sans-serif; }
    .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 12px #1857d820; padding: 32px; }
    h1 { color: #1857d8; margin-bottom: 24px; }
    .menu-tabs { display: flex; gap: 18px; margin-bottom: 18px; border-bottom: none; }
    .menu-tab {
      font-weight: 600;
      color: #fff;
      background: #1857d8;
      border: none;
      border-radius: 12px;
      padding: 12px 32px;
      font-size: 1.08em;
      cursor: pointer;
      box-shadow: 0 2px 8px #1857d820;
      transition: background 0.2s, color 0.2s;
      margin-bottom: 0;
    }
    .menu-tab.active {
      background: #1565c0;
      color: #fff;
      box-shadow: 0 4px 16px #1857d820;
    }
    .search-bar { width: 100%; max-width: 420px; padding: 10px 14px; border-radius: 8px; border: 1px solid #e3eaf2; font-size: 1em; margin-bottom: 18px; }
    .config-list { margin-top: 8px; }
    .config-item { padding: 12px 0; border-bottom: 1px solid #f0f0f0; font-size: 1.07em; color: #222; cursor: pointer; }
    .config-item:last-child { border-bottom: none; }
    .config-section { margin-top: 24px; }
    .config-section-title { font-weight: 700; font-size: 1.12em; margin-bottom: 10px; color: #1857d8; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Configurações do Sistema ERP</h1>
    <input type="text" class="search-bar" id="searchInput" placeholder="Busque pela funcionalidade ou dúvida" oninput="filtrarConfigs()">
    <div class="menu-tabs" id="menuTabs">
  <button class="menu-tab-small active" onclick="selecionarTab('geral')">Geral</button>
  <button class="menu-tab-small" onclick="selecionarTab('cadastros')">Cadastros</button>
  <button class="menu-tab-small" onclick="selecionarTab('suprimentos')">Suprimentos</button>
  <button class="menu-tab-small" onclick="selecionarTab('vendas')">Vendas</button>
  <button class="menu-tab-small" onclick="selecionarTab('notas')">Notas fiscais</button>
  <button class="menu-tab-small" onclick="selecionarTab('financas')">Finanças</button>
  <button class="menu-tab-small" onclick="selecionarTab('servicos')">Serviços</button>
  <button class="menu-tab-small" onclick="selecionarTab('ecommerce')">E-commerce</button>
    </div>
    <div id="configSections">
      <!-- Conteúdo das seções será renderizado pelo JS -->
    </div>
  </div>
  <script>
    const configData = {
      geral: [
        'Alterar dados da empresa',
        'Alterar dados do usuário',
        'Cadastro de usuários do sistema',
        'Configurações do servidor de e-mail',
        'Configurações do envio de documentos',
        'Configurações das etiquetas',
        'Configurações da agenda',
        'Solicitar cancelamento da conta'
      ],
      cadastros: [
        'Configurações do cadastro de clientes',
        'Configurações do cadastro de produtos',
        'Configurações de variações de produtos',
        'Configurações de atributos de produtos',
        'Configurações de marcas de produtos',
        'Tabelas de medidas',
        'Configurações das tags',
        'Tipos de contato',
        'Linhas de produto'
      ],
      suprimentos: [
        'Configurações de estoque',
        'Configurações do envio de documentos',
        'Configurações dos marcadores nas ordens de compra'
      ],
      vendas: [
        'Configurações do PDV',
        'Configurações das propostas comerciais',
        'Configurações dos pedidos de venda',
        'Configurações do envio de documentos',
        'Configurações dos marcadores nas vendas',
        'Configurações dos marcadores nas propostas comerciais',
        'Formas de envio',
        'Configurações da expedição'
      ],
      notas: [
        'Dados da empresa',
        'Configuração do certificado digital',
        'Ambiente das notas fiscais',
        'Naturezas de operação de entrada (tributação)',
        'Naturezas de operação de saída (tributação)',
        'Configuração da nota fiscal eletrônica (NFe)',
        'Configuração da nota fiscal eletrônica para consumidor final (NFCe)',
        'ICMS DIFAL para não contribuinte',
        'Cálculo diferenciado de ST para consumidor contribuintes - DIFAL',
        'Configurações da Guia Nacional de Recolhimento de Tributos Estaduais (GNRE)',
        'Cadastro de intermediadores',
        'Configurações dos marcadores nas notas fiscais de saída',
        'Configurações de notas fiscais de entrada',
        'Configurações dos marcadores nas notas fiscais de entrada',
        'Configuração da nota fiscal eletrônica de serviços (NFSe)',
        'Configurações dos marcadores nas notas de serviço'
      ],
      financas: [
        'Configurações gerais',
        'Categorias de receita e despesa',
        'Formas de recebimento',
        'Formas de pagamento',
        'Cadastro de contas bancárias',
        '<a href="/erp/contas_financeiras.php" style="color:#1857d8;text-decoration:none;font-weight:600;">Contas financeiras</a>',
        'Configurações do contas a pagar',
        'Configurações do contas a receber',
        'Configurações do envio de documentos',
        'Configurações dos marcadores no caixa',
        'Configurações dos marcadores nas contas a pagar',
        'Configurações dos marcadores nas contas a receber',
        'Gerenciar dispositivos do aplicativo "Maquininha no Celular"'
      ],
      servicos: [
        'Configurações das ordens de serviço',
        'Configurações dos contratos',
        'Configurações do envio de documentos',
        'Configurações dos marcadores nas ordens de serviço',
        'Configurações dos marcadores nos contratos',
        'Cadastro de CNAEs',
        'Configurações de campos adicionais para ordens de serviço'
      ],
      ecommerce: [
        'Configurações gerais',
        'Integrações'
      ]
    };
    let tabAtual = 'geral';
    function selecionarTab(tab) {
      tabAtual = tab;
  document.querySelectorAll('.menu-tab-small').forEach(btn => btn.classList.remove('active'));
  document.querySelector('.menu-tab-small[onclick*="' + tab + '"]').classList.add('active');
      renderConfigs();
    }
    function renderConfigs() {
      const lista = configData[tabAtual] || [];
      const busca = document.getElementById('searchInput').value.toLowerCase();
      let html = '<div class="config-list">';
      lista.filter(item => item.toLowerCase().includes(busca)).forEach(item => {
        html += `<div class="config-item">${item}</div>`;
      });
      html += '</div>';
      document.getElementById('configSections').innerHTML = html;
    }
    function filtrarConfigs() {
      renderConfigs();
    }
    window.onload = renderConfigs;
  </script>
</body>
</html>
