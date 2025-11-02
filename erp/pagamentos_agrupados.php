<?php
session_start();
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
    <title>Pagamentos Agrupados - ERP MTech</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card-header {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .card-value {
            font-size: 32px;
            font-weight: bold;
            color: #2196F3;
        }
        
        .card-subtitle {
            font-size: 14px;
            color: #999;
            margin-top: 5px;
        }
        
        .card.danger .card-value { color: #f44336; }
        .card.success .card-value { color: #4CAF50; }
        .card.warning .card-value { color: #FF9800; }
        
        .actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #2196F3;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        }
        
        .btn-success {
            background: #4CAF50;
            color: white;
        }
        
        .btn-success:hover {
            background: #388E3C;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        thead {
            background: #2196F3;
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
        }
        
        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f5f5f5;
        }
        
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status.aguardando {
            background: #FFF3E0;
            color: #F57C00;
        }
        
        .status.processando {
            background: #E3F2FD;
            color: #1976D2;
        }
        
        .status.pago {
            background: #E8F5E9;
            color: #388E3C;
        }
        
        .status.erro {
            background: #FFEBEE;
            color: #C62828;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.3s;
        }
        
        .progress-fill.warning {
            background: linear-gradient(90deg, #FF9800, #FFC107);
        }
        
        .progress-fill.danger {
            background: linear-gradient(90deg, #f44336, #EF5350);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }
        
        .close-modal:hover {
            color: #333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert.show {
            display: block;
        }
        
        .alert.success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }
        
        .alert.error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #f44336;
        }
        
        .icon {
            width: 20px;
            height: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="icon">💰</span>
            Gerenciamento de Pagamentos Agrupados
        </h1>
        
        <div id="alert" class="alert"></div>
        
        <!-- Dashboard -->
        <div class="dashboard">
            <div class="card">
                <div class="card-header">Contas Pendentes</div>
                <div class="card-value" id="contas_pendentes">0</div>
                <div class="card-subtitle" id="valor_pendente">R$ 0,00</div>
            </div>
            
            <div class="card danger">
                <div class="card-header">Contas Atrasadas</div>
                <div class="card-value" id="contas_atrasadas">0</div>
                <div class="card-subtitle" id="valor_atrasado">R$ 0,00</div>
            </div>
            
            <div class="card warning">
                <div class="card-header">Lotes Aguardando</div>
                <div class="card-value" id="lotes_aguardando">0</div>
                <div class="card-subtitle" id="valor_lotes">R$ 0,00</div>
            </div>
            
            <div class="card success">
                <div class="card-header">PIX Gratuitos</div>
                <div class="card-value" id="pix_restantes">30</div>
                <div class="card-subtitle">
                    <span id="pix_utilizados">0</span> / <span id="pix_limite">30</span> utilizados
                    <div class="progress-bar">
                        <div class="progress-fill" id="pix_progress" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ações -->
        <div class="actions">
            <button class="btn btn-primary" onclick="agruparPagamentos()">
                <span class="icon">🔗</span>
                Agrupar Pagamentos
            </button>
            
            <button class="btn btn-success" onclick="processarTodos()">
                <span class="icon">✅</span>
                Processar Todos os Lotes
            </button>
            
            <button class="btn btn-primary" onclick="atualizarDados()">
                <span class="icon">🔄</span>
                Atualizar
            </button>
        </div>
        
        <!-- Tabela de Lotes -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fornecedor</th>
                    <th>Qtd. Contas</th>
                    <th>Valor Total</th>
                    <th>Data Programada</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabela_lotes">
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                        Carregando dados...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Modal de Detalhes -->
    <div id="modal_detalhes" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModal()">&times;</span>
            <h2>Detalhes do Lote</h2>
            <div id="modal_corpo"></div>
        </div>
    </div>
    
    <script>
        // Carrega dados ao iniciar
        window.onload = function() {
            atualizarDados();
            // Atualiza a cada 30 segundos
            setInterval(atualizarDados, 30000);
        };
        
        // Atualiza todos os dados
        function atualizarDados() {
            fetch('api_pagamentos.php?action=dashboard')
                .then(r => r.json())
                .then(data => {
                    atualizarDashboard(data);
                });
            
            fetch('api_pagamentos.php?action=listar_lotes')
                .then(r => r.json())
                .then(data => {
                    atualizarTabela(data);
                });
        }
        
        // Atualiza dashboard
        function atualizarDashboard(data) {
            document.getElementById('contas_pendentes').textContent = data.contas_pendentes || 0;
            document.getElementById('valor_pendente').textContent = formatarMoeda(data.valor_pendente);
            
            document.getElementById('contas_atrasadas').textContent = data.contas_atrasadas || 0;
            document.getElementById('valor_atrasado').textContent = formatarMoeda(data.valor_atrasado);
            
            document.getElementById('lotes_aguardando').textContent = data.lotes_aguardando || 0;
            document.getElementById('valor_lotes').textContent = formatarMoeda(data.valor_lotes_aguardando);
            
            // Estatísticas PIX
            const pixStats = data.estatisticas_pix || {};
            document.getElementById('pix_restantes').textContent = pixStats.restantes || 30;
            document.getElementById('pix_utilizados').textContent = pixStats.utilizados || 0;
            document.getElementById('pix_limite').textContent = pixStats.limite_mensal || 30;
            
            const progresso = document.getElementById('pix_progress');
            progresso.style.width = (pixStats.percentual_uso || 0) + '%';
            progresso.className = 'progress-fill';
            if (pixStats.percentual_uso > 80) progresso.className += ' danger';
            else if (pixStats.percentual_uso > 60) progresso.className += ' warning';
        }
        
        // Atualiza tabela de lotes
        function atualizarTabela(lotes) {
            const tbody = document.getElementById('tabela_lotes');
            
            if (!lotes || lotes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #999;">Nenhum lote aguardando pagamento</td></tr>';
                return;
            }
            
            tbody.innerHTML = lotes.map(lote => `
                <tr>
                    <td>#${lote.id}</td>
                    <td>${lote.fornecedor_nome}</td>
                    <td>${lote.quantidade_contas}</td>
                    <td>${formatarMoeda(lote.valor_total)}</td>
                    <td>${formatarData(lote.data_pagamento_programada)}</td>
                    <td><span class="status ${lote.status.toLowerCase()}">${lote.status}</span></td>
                    <td>
                        <button class="btn btn-success" style="padding: 6px 12px; font-size: 13px;" onclick="processarLote(${lote.id})">
                            Pagar
                        </button>
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;" onclick="verDetalhes(${lote.id})">
                            Detalhes
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Agrupa pagamentos
        function agruparPagamentos() {
            if (!confirm('Deseja agrupar as contas pendentes por fornecedor?')) return;
            
            fetch('api_pagamentos.php?action=agrupar')
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso) {
                        mostrarAlerta(`${data.lotes_criados} lote(s) criado(s) com sucesso!`, 'success');
                        atualizarDados();
                    } else {
                        mostrarAlerta('Erro ao agrupar: ' + data.mensagem, 'error');
                    }
                });
        }
        
        // Processa um lote
        function processarLote(id) {
            if (!confirm('Confirma o pagamento deste lote via PIX?')) return;
            
            fetch(`api_pagamentos.php?action=processar_lote&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso) {
                        mostrarAlerta('Pagamento realizado com sucesso!', 'success');
                        atualizarDados();
                    } else {
                        mostrarAlerta('Erro: ' + data.mensagem, 'error');
                    }
                });
        }
        
        // Processa todos os lotes
        function processarTodos() {
            if (!confirm('Confirma o pagamento de TODOS os lotes aguardando?')) return;
            
            fetch('api_pagamentos.php?action=processar_todos')
                .then(r => r.json())
                .then(data => {
                    mostrarAlerta(
                        `Processamento concluído: ${data.sucesso} sucesso, ${data.erro} erros`,
                        data.erro > 0 ? 'error' : 'success'
                    );
                    atualizarDados();
                });
        }
        
        // Ver detalhes do lote
        function verDetalhes(id) {
            fetch(`api_pagamentos.php?action=detalhes_lote&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    const modal = document.getElementById('modal_detalhes');
                    const corpo = document.getElementById('modal_corpo');
                    
                    corpo.innerHTML = `
                        <p><strong>Fornecedor:</strong> ${data.fornecedor_nome}</p>
                        <p><strong>Chave PIX:</strong> ${data.pix_chave}</p>
                        <p><strong>Valor Total:</strong> ${formatarMoeda(data.valor_total)}</p>
                        <p><strong>Contas Agrupadas:</strong> ${data.quantidade_contas}</p>
                        <hr>
                        <h3>Documentos:</h3>
                        <p>${data.documentos || 'Nenhum documento'}</p>
                    `;
                    
                    modal.classList.add('active');
                });
        }
        
        function fecharModal() {
            document.getElementById('modal_detalhes').classList.remove('active');
        }
        
        function mostrarAlerta(mensagem, tipo) {
            const alert = document.getElementById('alert');
            alert.textContent = mensagem;
            alert.className = 'alert ' + tipo + ' show';
            setTimeout(() => alert.classList.remove('show'), 5000);
        }
        
        function formatarMoeda(valor) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valor || 0);
        }
        
        function formatarData(data) {
            return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
        }
    </script>
</body>
</html>
