<?php
// Gerar hash para senha @Mar1401a
$senha = '@Mar1401a';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "Hash gerado: $hash\n\n";
echo "UPDATE professores SET senha = '$hash', alterar_senha_proximo_login = false WHERE login = 'junilson.augusto';\n";
