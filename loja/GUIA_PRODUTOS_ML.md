# 📋 Guia: Como Criar Anúncios no Mercado Livre

## Por que meus produtos não sincronizam?

O erro **403 Forbidden** nas APIs do Mercado Livre significa que **você ainda não tem produtos anunciados** na sua conta.

## ✅ Solução em 3 Passos

### 1️⃣ Criar seu Primeiro Anúncio

Acesse o Mercado Livre e crie um anúncio:
- 🔗 **Link direto**: https://www.mercadolivre.com.br/vendas/publicar
- 📱 **Ou pelo app**: Menu → Vender → Publicar produto

### 2️⃣ Preencher Informações do Produto

- **Título**: Nome claro e descritivo
- **Categoria**: Escolha a categoria correta
- **Preço**: Defina o valor
- **Fotos**: Adicione pelo menos 1 foto
- **Descrição**: Detalhe o produto
- **Quantidade**: Informe o estoque

### 3️⃣ Publicar e Sincronizar

1. Clique em **"Publicar"** no Mercado Livre
2. Aguarde alguns minutos (ML processa o anúncio)
3. Volte ao dashboard: https://mtechservicos.com/loja/dashboard.php
4. Clique em **"🔄 Sincronizar"**

## 🔍 Status Atual da Sua Conta

De acordo com o debug realizado:

```
✅ Token ML: Válido
✅ User ID: 162691921
✅ Seller Experience: NEWBIE
✅ Permissões: read, write, offline_access
⚠️ Produtos Ativos: 0 (ESTE É O PROBLEMA)
```

## 💡 Dicas Importantes

1. **Mínimo 1 produto**: Você precisa ter pelo menos 1 anúncio ativo
2. **Status "Ativo"**: Produtos pausados não aparecem na API
3. **Aguarde processamento**: Após publicar, aguarde 5-10 minutos
4. **Reconecte se necessário**: Se publicou mas não aparece, desconecte e reconecte o ML

## 🔄 Como Sincronizar Depois

Após criar produtos no ML:

1. Acesse: https://mtechservicos.com/loja/sincronizar.php
2. Clique em **"🔄 Sincronizar Agora"**
3. Os produtos serão importados automaticamente

## 📊 O Que Será Importado

Quando você tiver produtos, o sistema irá sincronizar:

- ✅ Título e descrição
- ✅ Preço e moeda
- ✅ Estoque disponível
- ✅ Fotos do produto
- ✅ Condição (novo/usado)
- ✅ Link do anúncio
- ✅ Quantidade vendida

## 🆘 Ainda com Problemas?

Se após criar produtos no ML você ainda tiver erro 403:

1. **Desconecte**: https://mtechservicos.com/loja/desconectar_ml.php
2. **Reconecte**: https://mtechservicos.com/loja/conectar_ml.php
3. **Sincronize**: https://mtechservicos.com/loja/sincronizar.php

## 📞 Suporte

- **Dashboard**: https://mtechservicos.com/loja/dashboard.php
- **Debug ML**: https://mtechservicos.com/loja/debug_ml.php
- **Criar Anúncio**: https://www.mercadolivre.com.br/vendas/publicar

---

✨ **Resumo**: Você precisa ter pelo menos 1 produto ativo no Mercado Livre para que a API funcione!
