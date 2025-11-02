<!-- Modal Cadastro/Edição de Contato -->
<div class="modal-overlay" id="overlayModal" onclick="fecharModalContato()" style="backdrop-filter: blur(4px);"></div>
<div id="modalContato" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10001;">
    <div class="modal-content-contato">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h2 style="margin:0;font-size:1.25em;font-weight:700;">Cadastro de Contato</h2>
            <button type="button" class="btn" onclick="fecharModalContato()">✖</button>
        </div>
        <form id="formContato" onsubmit="salvarContato(event)">
            <div class="grid-contato">
                <div class="col">
                    <label for="nome">Nome</label>
                    <input type="text" name="nome" id="nome" class="input" required placeholder="Nome ou Razão Social do contato">
                </div>
                <div class="col">
                    <label for="fantasia">Fantasia</label>
                    <input type="text" name="fantasia" id="fantasia" class="input" placeholder="Fantasia">
                </div>
                <div class="col">
                    <label for="codigo">Código</label>
                    <input type="text" name="codigo" id="codigo" class="input" placeholder="Opcional">
                </div>
                <div class="col">
                    <label for="tipo_pessoa">Tipo de pessoa</label>
                    <select name="tipo_pessoa" id="tipo_pessoa" class="input" required>
                        <option value="">Selecione</option>
                        <option value="juridica">Pessoa Jurídica</option>
                        <option value="fisica">Pessoa Física</option>
                    </select>
                </div>
                <div class="col">
                    <label for="cpf_cnpj">CNPJ/CPF</label>
                    <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="input" required placeholder="CNPJ ou CPF">
                </div>
                <div class="col">
                    <label for="contribuinte">Contribuinte</label>
                    <select name="contribuinte" id="contribuinte" class="input">
                        <option value="">Selecione</option>
                        <option value="1">Contribuinte</option>
                        <option value="9">Não Contribuinte</option>
                    </select>
                </div>
                <div class="col">
                    <label for="insc_estadual">Inscrição Estadual</label>
                    <input type="text" name="insc_estadual" id="insc_estadual" class="input" placeholder="Inscrição Estadual">
                </div>
                <div class="col">
                    <label for="insc_municipal">Inscrição Municipal</label>
                    <input type="text" name="insc_municipal" id="insc_municipal" class="input" placeholder="Inscrição Municipal">
                </div>
                <div class="col">
                    <label for="tipo_contato">Tipo de contato</label>
                    <select name="tipo_contato" id="tipo_contato" class="input">
                        <option value="">Selecione uma opção</option>
                        <option value="cliente">Cliente</option>
                        <option value="fornecedor">Fornecedor</option>
                        <option value="transportador">Transportador</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="col" style="grid-column:1/4;">
                    <label for="endereco">Endereço</label>
                    <input type="text" name="endereco" id="endereco" class="input" placeholder="Endereço">
                </div>
                <div class="col">
                    <label for="municipio">Município</label>
                    <input type="text" name="municipio" id="municipio" class="input" placeholder="Município">
                </div>
                <div class="col">
                    <label for="uf">UF</label>
                    <select name="uf" id="uf" class="input">
                        <option value="">Selecione</option>
                        <option value="SP">SP</option>
                        <option value="RJ">RJ</option>
                        <option value="MG">MG</option>
                        <!-- ... outros estados ... -->
                    </select>
                </div>
                <div class="col">
                    <label for="cep">CEP</label>
                    <input type="text" name="cep" id="cep" class="input" placeholder="CEP">
                </div>
                <div class="col" style="grid-column:1/4;">
                    <label for="contato">Contato</label>
                    <input type="text" name="contato" id="contato" class="input" placeholder="Telefone, e-mail, etc">
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:16px;margin-top:24px;">
                <button type="button" class="btn" onclick="fecharModalContato()">Cancelar</button>
                <button type="submit" class="btn">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
#modalContato {
    display: none;
}
#modalContato.show {
    display: block;
}
.modal-overlay {
    display: none;
}
.modal-overlay.show {
    display: block;
}
</style>
