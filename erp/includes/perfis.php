<?php
// Perfis de usuário para atribuição rápida de permissões
return [
    'Administrador' => [
        'dashboard.php',
        'usuarios.php',
        'personalizacao.php',
        'configuracoes.php',
        'caixas_bancos.php',
        // Adicione todas as páginas do sistema
    ],
    'Financeiro' => [
        'dashboard.php',
        'caixas_bancos.php',
        // Adicione outras páginas do financeiro
    ],
    'Operacional' => [
        'dashboard.php',
        'configuracoes.php',
        // Adicione páginas operacionais
    ],
    'Consulta' => [
        'dashboard.php',
        // Apenas consulta
    ],
];
