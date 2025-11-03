function abrirModalPesquisarCadastro() {
    document.getElementById('modalPesquisarCadastroOverlay').classList.add('show');
    document.getElementById('modalPesquisarCadastroOverlay').style.display = 'block';
    document.getElementById('modalPesquisarCadastro').classList.add('show');
    document.getElementById('modalPesquisarCadastro').style.right = '0';
    document.getElementById('modalPesquisarCadastro').style.left = 'auto';
    document.getElementById('modalPesquisarCadastro').focus();
}
function fecharModalPesquisarCadastro() {
    document.getElementById('modalPesquisarCadastroOverlay').classList.remove('show');
    document.getElementById('modalPesquisarCadastroOverlay').style.display = 'none';
    document.getElementById('modalPesquisarCadastro').classList.remove('show');
    document.getElementById('modalPesquisarCadastro').style.right = '';
    document.getElementById('modalPesquisarCadastro').style.left = '';
}
function executarPesquisaCadastro() {
    // Implementar busca AJAX futuramente
    const termo = document.getElementById('pesquisaCadastroInput').value;
    document.getElementById('resultadoPesquisaCadastro').innerHTML = '<div style="padding:16px;color:#888;">Nenhum resultado para: <b>' + termo + '</b></div>';
}
