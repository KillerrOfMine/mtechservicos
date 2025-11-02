<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

// Lógica de salvamento direto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tipo_produto = $_POST['tipo_produto'] ?? '';
        $nome = $_POST['nome_produto'] ?? '';
        $codigo_barras = $_POST['codigo_barras'] ?? '';
        $origem_icms = $_POST['origem_icms'] ?? '';
        $unidade_medida = $_POST['unidade_medida'] ?? '';
        $ncm = $_POST['ncm'] ?? '';
        $valor = $_POST['valor'] ?? 0;
        $peso_liquido = is_numeric($_POST['peso_liquido'] ?? '') ? $_POST['peso_liquido'] : 0;
        $peso_bruto = is_numeric($_POST['peso_bruto'] ?? '') ? $_POST['peso_bruto'] : 0;
        $num_volumes = is_numeric($_POST['num_volumes'] ?? '') ? $_POST['num_volumes'] : 0;
        $tipo_embalagem = $_POST['tipo_embalagem'] ?? '';
        $embalagem = $_POST['embalagem'] ?? '';
        $largura = is_numeric($_POST['largura'] ?? '') ? $_POST['largura'] : 0;
        $altura = is_numeric($_POST['altura'] ?? '') ? $_POST['altura'] : 0;
        $comprimento = is_numeric($_POST['comprimento'] ?? '') ? $_POST['comprimento'] : 0;

        // Validação dos obrigatórios
        if (empty($nome) || empty($unidade_medida) || empty($ncm) || $valor === '' || $valor === null) {
            throw new Exception('Preencha todos os campos obrigatórios.');
        }

        $stmt = $pdo->prepare('INSERT INTO produtos (
            tipo_produto, nome, codigo_barras, origem_icms, unidade_medida, ncm, valor,
            peso_liquido, peso_bruto, num_volumes, tipo_embalagem, embalagem,
            largura, altura, comprimento
        ) VALUES (
            :tipo_produto, :nome, :codigo_barras, :origem_icms, :unidade_medida, :ncm, :valor,
            :peso_liquido, :peso_bruto, :num_volumes, :tipo_embalagem, :embalagem,
            :largura, :altura, :comprimento
        )');
        $stmt->execute([
            ':tipo_produto' => $tipo_produto,
            ':nome' => $nome,
            ':codigo_barras' => $codigo_barras,
            ':origem_icms' => $origem_icms,
            ':unidade_medida' => $unidade_medida,
            ':ncm' => $ncm,
            ':valor' => $valor,
            ':peso_liquido' => $peso_liquido,
            ':peso_bruto' => $peso_bruto,
            ':num_volumes' => $num_volumes,
            ':tipo_embalagem' => $tipo_embalagem,
            ':embalagem' => $embalagem,
            ':largura' => $largura,
            ':altura' => $altura,
            ':comprimento' => $comprimento
        ]);
        echo '<div style="color:green;font-weight:bold;padding:24px;">Produto cadastrado com sucesso!</div>';
    } catch (Exception $e) {
        echo '<div style="color:red;font-weight:bold;padding:24px;">Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } catch (PDOException $e) {
        echo '<div style="color:red;font-weight:bold;padding:24px;">Erro ao salvar produto: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<div class="container" style="max-width:900px;margin:80px auto 0 auto;background:var(--erp-bg,#fff);border-radius:16px;box-shadow:0 4px 24px #0001;padding:32px 24px;">
    <h2 class="erp-title">Novo produto</h2>
    <form method="post">
        <div style="display:flex;gap:24px;flex-wrap:wrap;">
            <div style="flex:1;min-width:320px;">
                <label for="nome_produto">Nome do produto <span style="color:#d81b60">*</span></label><br>
                <input type="text" name="nome_produto" id="nome_produto" class="erp-input" placeholder="Descrição completa do produto" required>
                <label for="unidade_medida">Unidade de medida <span style="color:#d81b60">*</span></label><br>
                <input type="text" name="unidade_medida" id="unidade_medida" class="erp-input" placeholder="Ex: Pç, Kg, ..." required>
                <label for="ncm">NCM - Nomenclatura comum do Mercosul <span style="color:#d81b60">*</span></label><br>
                <input type="text" name="ncm" id="ncm" class="erp-input" placeholder="Exemplo: 1001.10.10" required>
                <label for="valor">Valor do produto <span style="color:#d81b60">*</span></label><br>
                <input type="number" step="0.01" name="valor" id="valor" class="erp-input" placeholder="R$" required>
                <label for="tipo_produto">Tipo do Produto</label><br>
                <select name="tipo_produto" id="tipo_produto" class="erp-input">
                    <option value="Simples">Simples</option>
                    <option value="Kit">Kit</option>
                    <option value="Com variações">Com variações</option>
                    <option value="Matéria-prima">Matéria-prima</option>
                </select>
                <label for="codigo_barras">Código de barras (GTIN)</label><br>
                <input type="text" name="codigo_barras" id="codigo_barras" class="erp-input" placeholder="Código de barras">
                <label for="origem_icms">Origem do produto conforme ICMS</label><br>
                <select name="origem_icms" id="origem_icms" class="erp-input">
                    <option value="0">0 - Nacional, exceto as indicadas nos códigos 3 a 5</option>
                    <option value="1">1 - Estrangeira - Importação direta, exceto a indicada no código 6</option>
                    <option value="2">2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7</option>
                    <option value="3">3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70%</option>
                    <option value="4">4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos</option>
                    <option value="5">5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%</option>
                    <option value="6">6 - Estrangeira - Importação direta, sem similar nacional, constante em lista da CAMEX</option>
                    <option value="7">7 - Estrangeira - Adquirida no mercado interno, sem similar nacional, constante em lista da CAMEX</option>
                    <option value="8">8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%</option>
                </select>
            </div>
            <div style="flex:1;min-width:320px;">
                <h3 class="erp-subtitle">Dimensões e peso</h3>
                <label for="peso_liquido">Peso Líquido (Kg)</label><br>
                <input type="number" step="0.01" name="peso_liquido" id="peso_liquido" class="erp-input">
                <label for="peso_bruto">Peso Bruto (Kg)</label><br>
                <input type="number" step="0.01" name="peso_bruto" id="peso_bruto" class="erp-input">
                <label for="num_volumes">Nº de volumes</label><br>
                <input type="number" name="num_volumes" id="num_volumes" class="erp-input">
                <label for="tipo_embalagem">Tipo da embalagem</label><br>
                <select name="tipo_embalagem" id="tipo_embalagem" class="erp-input">
                    <option value="Pacote / Caixa">Pacote / Caixa</option>
                    <option value="Envelope">Envelope</option>
                    <option value="Palete">Palete</option>
                    <option value="Outro">Outro</option>
                </select>
                <label for="embalagem">Embalagem</label><br>
                <input type="text" name="embalagem" id="embalagem" class="erp-input" placeholder="Embalagem customizada">
                <div style="display:flex;gap:12px;">
                    <div style="flex:1;">
                        <label for="largura">Largura (cm)</label><br>
                        <input type="number" step="0.01" name="largura" id="largura" class="erp-input">
                    </div>
                    <div style="flex:1;">
                        <label for="altura">Altura (cm)</label><br>
                        <input type="number" step="0.01" name="altura" id="altura" class="erp-input">
                    </div>
                    <div style="flex:1;">
                        <label for="comprimento">Comprimento (cm)</label><br>
                        <input type="number" step="0.01" name="comprimento" id="comprimento" class="erp-input">
                    </div>
                </div>
            </div>
        </div>
        <div style="margin-top:32px;display:flex;gap:18px;">
            <button type="submit" class="erp-btn-primary">salvar</button>
            <a href="produtos.php" class="erp-btn-secondary">cancelar</a>
        </div>
    </form>
</div>
<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
body, .container, .erp-title, .erp-subtitle, .erp-input, .erp-btn-primary, .erp-btn-secondary {
    font-family: 'Roboto', Arial, Helvetica, sans-serif;
}
.erp-title {
    font-size:2em;
    font-weight:700;
    color:#1857d8;
    margin-bottom:24px;
}
.erp-subtitle {
    font-size:1.2em;
    font-weight:600;
    margin-bottom:12px;
    color:#222;
}
.erp-input {
    width:100%;
    padding:10px;
    border-radius:6px;
    border:1px solid #b0bec5;
    margin-bottom:16px;
    font-size:1em;
    background:#f8fafc;
    color:#222;
}
.erp-btn-primary {
    background:#1857d8;
    color:#fff;
    font-weight:700;
    border:none;
    border-radius:8px;
    padding:14px 40px;
    font-size:1.1em;
    cursor:pointer;
    text-decoration:none;
    transition:background 0.2s;
}
.erp-btn-primary:hover {
    background:#2980ff;
}
.erp-btn-secondary {
    background:#b0bec5;
    color:#222;
    font-weight:700;
    border:none;
    border-radius:8px;
    padding:14px 40px;
    font-size:1.1em;
    cursor:pointer;
    text-decoration:none;
    transition:background 0.2s;
    margin-left:8px;
}
.erp-btn-secondary:hover {
    background:#78909c;
}
</style>
