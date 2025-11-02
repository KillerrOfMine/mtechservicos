<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: /erp/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importação de Dados</title>
    <link rel="stylesheet" href="/erp/assets/theme.css">
</head>
<body style="font-family: 'Inter', sans-serif; margin:0;">
<?php include 'includes/header.php'; ?>
<div class="container" style="padding:32px 24px;max-width:1100px;margin:32px auto;">
    <h2 style="margin-bottom:24px;font-size:1.6em;font-weight:700;">Importação de Dados</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:32px;">
        <!-- Card Importação de Clientes/Fornecedores -->
        <div class="card" style="padding:28px 24px;border-radius:16px;box-shadow:0 4px 20px rgba(24,87,216,0.10);background:#fff;">
            <h3 style="margin-top:0;font-size:1.25em;font-weight:700;">Importar Clientes e Fornecedores</h3>
            <form id="formImportarContatos" enctype="multipart/form-data" style="margin-top:18px;">
                <label style="font-weight:600;color:#1565c0;">Selecione o arquivo</label>
                <input type="file" name="arquivo" accept=".csv,.xls,.xlsx,.txt,.pdf" required style="margin-bottom:16px;">
                <button type="submit" class="btn" style="margin-bottom:12px;">Enviar arquivo</button>
            </form>
            <div id="previewImportacao" style="margin-top:18px;"></div>
            <div id="relacionamentoColunas" style="margin-top:18px;"></div>
            <div id="errosImportacao" style="margin-top:18px;color:#c00;font-weight:600;"></div>
            <div id="historicoImportacao" style="margin-top:18px;"></div>
        </div>
        <!-- Outros cards de importação podem ser adicionados futuramente -->
    </div>
</div>
<script>
// Simulação de preview e relacionamento de colunas
const colunasBanco = ['nome', 'fantasia', 'codigo', 'tipo_pessoa', 'cpf_cnpj', 'municipio', 'uf', 'cep', 'contato'];
document.getElementById('formImportarContatos').addEventListener('submit', function(e) {
    e.preventDefault();
    const fileInput = this.querySelector('input[type="file"]');
    const file = fileInput.files[0];
    if (!file) return;
    // Simulação: mostra preview de colunas e permite relacionar
    const preview = document.getElementById('previewImportacao');
    preview.innerHTML = '<b>Preview do arquivo:</b><br><i>(simulação)</i><br><table style="width:100%;margin-top:8px;border-collapse:collapse;"><thead><tr><th>Coluna do arquivo</th><th>Relacionar com</th></tr></thead><tbody>' +
        ['Nome','CPF/CNPJ','Cidade','Contato'].map((col,i) => `<tr><td style='padding:6px 8px;'>${col}</td><td style='padding:6px 8px;'><select>${colunasBanco.map(b=>`<option>${b}</option>`).join('')}</select></td></tr>`).join('') + '</tbody></table>';
    document.getElementById('relacionamentoColunas').innerHTML = '<b>Relacione cada coluna do arquivo com o campo do banco de dados.</b>';
    document.getElementById('errosImportacao').innerHTML = '';
    // Simulação de histórico
    document.getElementById('historicoImportacao').innerHTML = '<b>Histórico de importações:</b><br><ul><li>26/10/2025 - Importação de clientes.csv - 120 registros</li></ul>';
});
</script>
</body>
</html>
