<!-- Modal Pesquisar Cadastro -->
<div id="modalPesquisarCadastroOverlay" class="erp-modal-overlay" style="display:none;z-index:10000;"></div>
<div id="modalPesquisarCadastro" class="erp-modal-side" tabindex="-1" style="right:0;left:auto;top:0;bottom:0;z-index:10001;box-shadow:-4px 0 32px rgba(24,87,216,0.10);border-radius:24px 0 0 24px;">
    <div class="erp-modal-content" style="max-width:420px;width:100%;height:100vh;border-radius:24px 0 0 24px;padding:32px 24px;box-sizing:border-box;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:1.25em;font-weight:700;">Pesquisar Cadastro</h2>
            <button type="button" class="btn" onclick="fecharModalPesquisarCadastro()" style="width:44px;height:44px;border-radius:12px;font-size:1.2em;display:flex;align-items:center;justify-content:center;">✖</button>
        </div>
        <div style="margin-bottom:24px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <input type="text" id="pesquisaCadastroInput" class="input" placeholder="Pesquise por nome, cód., fantasia, email ou CPF/CNPJ" style="flex:1;">
                <button type="button" class="btn" onclick="executarPesquisaCadastro()" style="padding:0 12px;display:flex;align-items:center;justify-content:center;">
                    <span class="material-icons" style="font-size:1.3em;">search</span>
                </button>
                <button type="button" class="btn" style="padding:0 10px;">
                    <span class="material-icons" style="font-size:1.3em;">filter_list</span>
                </button>
            </div>
        </div>
        <div id="resultadoPesquisaCadastro" style="min-height:120px;"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:24px;">
            <button type="button" class="btn" onclick="fecharModalPesquisarCadastro()">Fechar</button>
        </div>
    </div>
</div>
<script src="/erp/assets/modal_pesquisar_cadastro.js?v=<?php echo time(); ?>"></script>
<!-- Para usar: include 'includes/modal_pesquisar_cadastro.php'; -->
