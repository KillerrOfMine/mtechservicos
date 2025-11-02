<?php
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/db_connect.php';
// Carregar última configuração
try {
    $stmt = $pdo->query('SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1');
    $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $config = [];
}
$header_logo = $config['header_logo'] ?? '';
$user_name = 'Usuário';
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['nome'])) {
        $user_name = htmlspecialchars($row['nome']);
    }
}
$tema = $config['tema'] ?? 'claro';
$logo_url = '';
if ($tema === 'Escuro' && !empty($config['logo_clara'])) {
    $logo_url = $config['logo_clara'];
} elseif (!empty($config['logo_escura'])) {
    $logo_url = $config['logo_escura'];
} else {
    $logo_url = $config['header_logo'] ?? '';
}
$favicon_url = $config['favicon'] ?? '';
?>

<!-- O PHP está fechado antes do CSS -->
<style>
.erp-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    min-height: 54px;
    height: 54px;
    padding: 0 32px;
    margin: 0 auto;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 2px 16px 0 rgba(24,87,216,0.08);
    background: <?= ($tema === 'Escuro') ? '#23272f' : '#fff' ?>;
    color: <?= ($tema === 'Escuro') ? '#e3e6ee' : '#222' ?>;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Segoe UI', 'Arial', sans-serif;
    font-size: 1em;
    max-width: 100vw;
}
body {
    padding-top: 54px;
    background: <?= ($tema === 'Escuro') ? '#181a20' : '#f5f7fa' ?>;
}
.logo-area img {
    height: 40px;
    vertical-align: middle;
}
.erp-header nav {
    display: flex;
    align-items: center;
    gap: 16px;
}
.menu-modulo {
    position: relative;
    display: inline-block;
    vertical-align: middle;
    min-width: 120px;
    max-width: 180px;
    white-space: nowrap;
}
.menu-modulo span {
    font-weight: 500;
    color: #222;
    font-size: 1em;
    margin-right: 8px;
    letter-spacing: 0.5px;
    cursor: pointer;
    padding: 8px 18px;
    border-radius: 10px;
    background: none;
    transition: background 0.18s;
    border: none;
}
.menu-modulo span:hover, .menu-modulo span:focus {
    background: <?= ($tema === 'Escuro') ? '#31343a' : '#f3f6fa' ?>;
    color: <?= ($tema === 'Escuro') ? '#00bfae' : '#1857d8' ?>;
}
.menu-modulo ul, .submenu {
    display: none;
    position: absolute;
    top: 110%;
    left: 0;
    margin: 0;
    padding: 12px 0;
    background: #fff;
    border-radius: 14px;
    list-style: none;
    box-shadow: 0 6px 24px 0 rgba(24,87,216,0.13);
    z-index: 1000;
    min-width: 220px;
    max-width: 320px;
    overflow-y: auto;
    max-height: 320px;
}
.menu-modulo.open .submenu {
    display: block;
}
.submenu a {
    display: block;
    color: <?= ($tema === 'Escuro') ? '#e3e6ee' : '#222' ?>;
    padding: 12px 32px;
    border-radius: 10px;
    font-weight: 500;
    text-decoration: none;
    margin: 2px 0;
    font-size: 1em;
    background: none;
    transition: background 0.18s, color 0.18s;
}
.submenu a:hover {
    background: <?= ($tema === 'Escuro') ? '#00bfae' : '#1857d8' ?>;
    color: #fff;
}
.erp-header .user-area {
    display: flex;
    align-items: center;
    gap: 18px;
}
.erp-header .user-area span {
    color: <?= ($tema === 'Escuro') ? '#e3e6ee' : '#222' ?>;
    font-weight: 500;
    font-size: 1em;
    letter-spacing: 0.5px;
}
.erp-header .user-area .btn {
    background: <?= ($tema === 'Escuro') ? '#00bfae' : '#1857d8' ?>;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 24px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
}
.erp-header .user-area .btn:hover {
    background: <?= ($tema === 'Escuro') ? '#1857d8' : '#22aaff' ?>;
}
.erp-header nav a,
.erp-header nav .menu-modulo span {
    color: <?= ($tema === 'Escuro') ? '#e3e6ee' : '#222' ?> !important;
    background: none;
    text-decoration: none !important;
    font-weight: 500;
    transition: color 0.18s, background 0.18s;
}
.dark-theme .erp-header nav a,
.dark-theme .erp-header nav .menu-modulo span {
    color: #fff !important;
    background: none;
    text-decoration: none !important;
    font-weight: 500;
}
.erp-header nav a.active,
.erp-header nav .menu-modulo span.active {
    background: none !important;
    color: <?= ($tema === 'Escuro') ? '#00bfae' : '#1857d8' ?> !important;
    border-radius: 0;
    font-weight: 500;
    padding: 8px 18px;
    box-shadow: none !important;
}
.dark-theme .erp-header nav a.active,
.dark-theme .erp-header nav .menu-modulo span.active {
    background: none !important;
    color: inherit !important;
    border-radius: 0;
    font-weight: 500;
    padding: 8px 18px;
    box-shadow: none !important;
}
.theme-modal-trigger button {
    background: #fff;
    color: #23272f;
    transition: background 0.2s, color 0.2s;
    box-shadow: 0 2px 8px rgba(24,87,216,0.08);
}
.dark-theme .theme-modal-trigger button {
    background: #22242a;
    color: #fff;
    box-shadow: 0 2px 8px rgba(24,87,216,0.18);
}
.theme-modal-trigger button svg rect {
    transition: fill 0.2s;
    fill: #23272f;
}
.dark-theme .theme-modal-trigger button svg rect {
    fill: #fff;
}
.erp-header nav a:hover,
.erp-header nav .menu-modulo span:hover {
    background: #e5e8ef;
    color: #222 !important;
    border-radius: 12px;
}
.dark-theme .erp-header nav a:hover,
.dark-theme .erp-header nav .menu-modulo span:hover {
    background: #181a1b;
    color: #fff !important;
    border-radius: 12px;
}
.submenu {
    background: #fff;
    color: #222;
}
.dark-theme .submenu {
    background: #23272f !important;
    color: #fff !important;
}
.submenu a {
    color: #222 !important;
    font-weight: 500;
}
.dark-theme .submenu a {
    color: #fff !important;
    font-weight: 500;
}
.submenu::-webkit-scrollbar {
    width: 12px;
    background: #fff;
    border-radius: 10px;
}
.submenu::-webkit-scrollbar-thumb {
    background: #dbeafe;
    border-radius: 10px;
    border: 3px solid #fff;
}
.dark-theme .submenu::-webkit-scrollbar {
    width: 12px;
    background: #22242a;
    border-radius: 10px;
}
.dark-theme .submenu::-webkit-scrollbar-thumb {
    background: #181a1b;
    border-radius: 10px;
    border: 3px solid #22242a;
}
#themeModal {
    animation: fadeIn 0.2s;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.userConfigMenu {
    position: absolute;
    top: 54px;
    left: 0;
    min-width: 140px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(24,87,216,0.13);
    padding: 18px 18px 12px 18px;
    z-index: 10000;
    display: none;
}
.dark-theme .userConfigMenu {
    background: #22242a !important;
    color: #fff !important;
}
.userConfigMenu.open {
    display: block;
}
.theme-switch {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
    margin-bottom: 4px;
    justify-content: center;
}
.theme-switch-label {
    font-size: 1em;
    font-weight: 500;
    min-width: 48px;
    text-align: center;
    color: #23272f;
    transition: color 0.2s;
}
.theme-switch-slider {
    width: 38px;
    height: 20px;
    background: #eceef2;
    border-radius: 12px;
    position: relative;
    transition: background 0.2s;
    box-shadow: 0 4px 18px rgba(24,87,216,0.10);
    margin: 0 4px;
}
.theme-switch.dark .theme-switch-slider {
    background: #31343a;
    box-shadow: 0 4px 18px rgba(41,128,255,0.18);
}
.theme-switch-slider:before {
    content: '';
    position: absolute;
    top: 3px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(24,87,216,0.10);
    transition: left 0.2s, background 0.2s, border 0.2s;
    background: #fff;
    left: 21px;
    border: 2px solid #eceef2;
}
.theme-switch.dark .theme-switch-slider:before {
    left: 3px;
    background: #fff;
    border: 2px solid #23272f;
    box-shadow: 0 2px 8px rgba(41,128,255,0.18);
}
.theme-switch:not(.dark) .theme-switch-slider:before {
    left: 21px;
    background: #fff;
    border: 2px solid #eceef2;
    box-shadow: 0 2px 8px rgba(24,87,216,0.10);
}
.dark-theme .theme-switch-label {
    color: #e3e6ee !important;
    font-weight: 600;
}
.erp-header nav a.active,
.erp-header nav .menu-modulo span.active {
    background: none !important;
    color: inherit !important;
    border-radius: 0;
    font-weight: 500;
    padding: 8px 18px;
    box-shadow: none !important;
}
.dark-theme .erp-header nav a.active,
.dark-theme .erp-header nav .menu-modulo span.active {
    background: none !important;
    color: inherit !important;
    border-radius: 0;
    font-weight: 500;
    padding: 8px 18px;
    box-shadow: none !important;
}
.submenu {
    background: #fff;
}
.dark-theme .submenu {
    background: #23272f !important;
    color: #fff !important;
}
.dark-theme .submenu a {
    color: #fff !important;
    font-weight: 500;
}
.dark-theme .submenu a:hover {
    background: #2980ff !important;
    color: #fff !important;
}
body.dark-theme .erp-header {
    background: #22242a !important;
    color: #fff !important;
    box-shadow: 0 2px 16px 0 rgba(24,87,216,0.13);
}
.theme-modal-trigger button {
    background: #fff;
    color: #23272f;
    transition: background 0.2s, color 0.2s;
    box-shadow: 0 2px 8px rgba(24,87,216,0.08);
}
.dark-theme .theme-modal-trigger button {
    background: #22242a;
    color: #fff;
    box-shadow: 0 2px 8px rgba(24,87,216,0.18);
}
.theme-modal-trigger button svg rect {
    transition: fill 0.2s;
    fill: #23272f;
}
.dark-theme .theme-modal-trigger button svg rect {
    fill: #fff;
}
.logo-area .logo-clara { display: none; }
.logo-area .logo-escura { display: inline-block; }
body.dark-theme .logo-area .logo-clara { display: inline-block; }
body.dark-theme .logo-area .logo-escura { display: none; }
.logo-area img.logo-clara:only-child { display: inline-block !important; }
.logo-area img.logo-escura:only-child { display: inline-block !important; }
</style>
<header class="erp-header">
    <div style="display:flex;align-items:center;gap:18px;">
        <!-- Menu hambúrguer com submenu de configurações pessoais -->
        <div class="theme-modal-trigger" style="position:relative;">
            <button id="openUserConfigMenu" title="Configurações pessoais" style="background:none !important;border:none;cursor:pointer;padding:6px 12px;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:none;transition:box-shadow 0.2s;">
                <span style="display:inline-block;width=28px;height=28px;">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect y="6" width="28" height="3.5" rx="1.5" fill="#23272f"/>
                        <rect y="12.25" width="28" height="3.5" rx="1.5" fill="#23272f"/>
                        <rect y="18.5" width="28" height="3.5" rx="1.5" fill="#23272f"/>
                    </svg>
                </span>
            </button>
            <div id="userConfigMenu" class="userConfigMenu">
                <div id="themeSwitch" class="theme-switch">
                    <span class="theme-switch-label" id="themeSwitchLabelEscuro">Escuro</span>
                    <span class="theme-switch-slider"></span>
                    <span class="theme-switch-label" id="themeSwitchLabelClaro">Claro</span>
                </div>
                <!-- Futuras opções: perfil, notificações, etc -->
            </div>
        </div>
        <!-- Logo -->
        <div class="logo-area">
            <?php
            $logo_clara = !empty($config['logo_clara']) ? $config['logo_clara'] : '';
            $logo_escura = !empty($config['logo_escura']) ? $config['logo_escura'] : '';
            if ($logo_clara && $logo_escura) {
                echo '<img src="' . htmlspecialchars($logo_escura) . '" alt="Logo Escura" class="logo-escura" style="height:40px;max-height:40px;vertical-align:middle;">';
                echo '<img src="' . htmlspecialchars($logo_clara) . '" alt="Logo Clara" class="logo-clara" style="height:40px;max-height:40px;vertical-align:middle;">';
            } elseif ($logo_clara) {
                echo '<img src="' . htmlspecialchars($logo_clara) . '" alt="Logo Clara" class="logo-clara" style="height:40px;max-height:40px;vertical-align:middle;">';
            } elseif ($logo_escura) {
                echo '<img src="' . htmlspecialchars($logo_escura) . '" alt="Logo Escura" class="logo-escura" style="height:40px;max-height:40px;vertical-align:middle;">';
            } else {
                echo '<span style="font-weight:700;font-size:1.3em;letter-spacing:1px;">ERP</span>';
            }
            ?>
        </div>
        <!-- Navegação -->
        <nav style="margin-left:18px;">
        <?php
        $modulos_agrupados = [
            'Dashboard' => [
                'dashboard.php'     => ['label' => 'Dashboard', 'url' => '/erp/dashboard.php'],
            ],
            'Cadastros' => [
                'contatos.php'      => ['label' => 'Clientes e Fornecedores', 'url' => '/erp/contatos.php'],
                'produtos.php'      => ['label' => 'Produtos', 'url' => '/erp/produtos.php'],
                'anuncios.php'      => ['label' => 'Anúncios', 'url' => '/erp/anuncios.php'],
                'servicos.php'      => ['label' => 'Serviços', 'url' => '/erp/servicos.php'],
                'categorias.php'    => ['label' => 'Categorias dos Produtos', 'url' => '/erp/categorias.php'],
                'vendedores.php'    => ['label' => 'Vendedores', 'url' => '/erp/vendedores.php'],
                'embalagens.php'    => ['label' => 'Embalagens', 'url' => '/erp/embalagens.php'],
                'relatorios_cad.php'=> ['label' => 'Relatórios', 'url' => '/erp/relatorios_cad.php'],
            ],
            'Vendas' => [
                'automacoes.php'    => ['label' => 'Painel de Automações', 'url' => '/erp/automacoes.php'],
                'pdv.php'      => ['label' => 'PDV', 'url' => '/erp/pdv.php'],
                'propostas.php'     => ['label' => 'Propostas Comerciais', 'url' => '/erp/propostas.php'],
                'pedidos.php'       => ['label' => 'Pedidos de Venda', 'url' => '/erp/pedidos.php'],
                'notas_fiscais.php' => ['label' => 'Notas Fiscais', 'url' => '/erp/notas_fiscais.php'],
                'comissoes.php'     => ['label' => 'Comissões', 'url' => '/erp/comissoes.php'],
                'nfce.php'          => ['label' => 'Notas Fiscais Consumidor', 'url' => '/erp/nfce.php'],
                'expedicao.php'     => ['label' => 'Expedição', 'url' => '/erp/expedicao.php'],
                'ecommerce.php'     => ['label' => 'Pedidos no e-commerce', 'url' => '/erp/ecommerce.php'],
                'relatorios_vendas.php' => ['label' => 'Relatórios', 'url' => '/erp/relatorios_vendas.php'],
            ],
            'Finanças' => [
                'caixas_bancos.php' => ['label' => 'Caixas e Bancos', 'url' => '/erp/caixas_bancos.php'],
                'conta_digital.php' => ['label' => 'Conta Digital', 'url' => '/erp/conta_digital.php'],
                'demonstrativo.php' => ['label' => 'Demonstrativo de Vendas', 'url' => '/erp/demonstrativo.php'],
                'contas_pagar.php'  => ['label' => 'Contas a Pagar', 'url' => '/erp/contas_pagar.php'],
                'contas_receber.php'=> ['label' => 'Contas a Receber', 'url' => '/erp/contas_receber.php'],
                'relatorios_fin.php'=> ['label' => 'Relatórios', 'url' => '/erp/relatorios_fin.php'],
            ],
            'Serviços' => [
                'ordens_servico.php'=> ['label' => 'Ordens de Serviço', 'url' => '/erp/ordens_servico.php'],
                'notas_servico.php' => ['label' => 'Notas de Serviço', 'url' => '/erp/notas_servico.php'],
                'relatorios_serv.php'=> ['label' => 'Relatórios', 'url' => '/erp/relatorios_serv.php'],
            ],
            'Configurações' => [
                'configuracoes_sistema.php' => ['label' => 'Sistema', 'url' => '/erp/configuracoes_sistema.php'],
                'personalizacao.php'=> ['label' => 'Personalização', 'url' => '/erp/personalizacao.php'],
                'dados_empresa.php'=> ['label' => 'Dados da Empresa', 'url' => '/erp/dados_empresa.php'],
                'usuarios.php'      => ['label' => 'Usuários', 'url' => '/erp/usuarios.php'],
                'importacao.php'    => ['label' => 'Importação de Dados', 'url' => '/erp/importacao.php'],
                'permissoes_usuario.php' => ['label' => 'Permissionamento', 'url' => 'https://mtechservicos.com/erp/permissoes_usuario.php'],
                'logs_permissoes.php' => ['label' => 'Logs de Permissionamento', 'url' => '/erp/logs_permissoes.php'],
            ]
        ];
        $permissoes = [];
        if (!empty($_SESSION['usuario_id']) && isset($pdo)) {
            if (!isset($_SESSION['permissoes'])) {
                $stmt = $pdo->prepare('
                    SELECT p.permissoes
                    FROM usuario_perfil up
                    JOIN perfis p ON up.perfil_id = p.id
                    WHERE up.usuario_id = :uid
                ');
                $stmt->execute([':uid' => $_SESSION['usuario_id']]);
                $permissoes = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $perms = json_decode($row['permissoes'], true);
                    if (is_array($perms)) {
                        $permissoes = array_merge($permissoes, $perms);
                    }
                }
                $_SESSION['permissoes'] = array_unique($permissoes);
            }
            $permissoes = $_SESSION['permissoes'];
        }
        $modulos_renderizados = [];
        $current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        foreach ($modulos_agrupados as $modulo_nome => $modulo_paginas) {
            if (in_array($modulo_nome, $modulos_renderizados)) continue;
            $modulos_renderizados[] = $modulo_nome;
            $exibe_modulo = false;
            $paginas_permitidas = [];
            $submenus_renderizados = [];
            foreach ($modulo_paginas as $pagina => $info) {
                $chave = str_replace('.php', '', $pagina);
                if (in_array($chave, $permissoes) || ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['role'] ?? '') === 'superuser') {
                    if (in_array($info['label'], $submenus_renderizados)) continue;
                    $submenus_renderizados[] = $info['label'];
                    $exibe_modulo = true;
                    $paginas_permitidas[$pagina] = $info;
                }
            }
            if ($exibe_modulo) {
                if (count($paginas_permitidas) === 1) {
                    foreach ($paginas_permitidas as $pagina => $info) {
                        $active = ($current === $pagina) ? ' active' : '';
                        echo '<a href="'.$info['url'].'" class="'.$active.'">'.htmlspecialchars($info['label']).'</a>';
                    }
                } else {
                    echo '<div class="menu-modulo" tabindex="0" role="menu">';
                    echo '<span role="menuitem" aria-haspopup="true" aria-expanded="false" onclick=\'toggleSubmenu(this)\'>'.htmlspecialchars($modulo_nome).'</span>';
                    echo '<div class="submenu">';
                    foreach ($paginas_permitidas as $pagina => $info) {
                        $active = ($current === $pagina) ? ' active' : '';
                        echo '<a href="'.$info['url'].'" class="'.$active.'">'.htmlspecialchars($info['label']).'</a>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }
        }
        ?>
        <script>
        function toggleSubmenu(span) {
            const parent = span.parentElement;
            const opened = document.querySelector('.menu-modulo.open');
            if (opened && opened !== parent) {
                opened.classList.remove('open');
                opened.querySelector('span').setAttribute('aria-expanded', 'false');
            }
            const isOpen = parent.classList.toggle('open');
            span.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        document.addEventListener('click', function(e) {
            var opened = document.querySelector('.menu-modulo.open');
            if (opened && !opened.contains(e.target)) opened.classList.remove('open');
        });
        </script>
    </nav>
    <!-- Usuário e ação -->
    <div class="user-area">
        <span>Olá, <?= $user_name ?></span>
        <button class="btn" onclick="location.href='/erp/logout.php'">Sair</button>
    </div>
</header>
<script>
const userConfigMenu = document.getElementById('userConfigMenu');
const openUserConfigMenu = document.getElementById('openUserConfigMenu');
openUserConfigMenu.onclick = function(e) {
    e.stopPropagation();
    userConfigMenu.classList.toggle('open');
};
document.body.addEventListener('click', function(e) {
    if (!userConfigMenu.contains(e.target) && e.target !== openUserConfigMenu) {
        userConfigMenu.classList.remove('open');
    }
});
const themeSwitch = document.getElementById('themeSwitch');
const themeSwitchLabelEscuro = document.getElementById('themeSwitchLabelEscuro');
const themeSwitchLabelClaro = document.getElementById('themeSwitchLabelClaro');
themeSwitch.onclick = function(e) {
    e.stopPropagation();
    const isDark = document.body.classList.contains('dark-theme');
    document.body.classList.toggle('dark-theme', !isDark);
    localStorage.setItem('erp_theme', !isDark ? 'dark' : 'light');
    updateThemeSwitch();
};
function updateThemeSwitch() {
    const isDark = document.body.classList.contains('dark-theme');
    themeSwitch.classList.toggle('dark', isDark);
    themeSwitchLabelEscuro.style.fontWeight = '600';
    themeSwitchLabelClaro.style.fontWeight = '600';
}
window.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('erp_theme');
    document.body.classList.toggle('dark-theme', saved === 'dark');
    updateThemeSwitch();
});
</script>
<link rel="icon" type="image/png" href="<?= htmlspecialchars($favicon_url) ?>">
<link rel="stylesheet" href="/erp/assets/theme.css?v=<?php echo time(); ?>">
