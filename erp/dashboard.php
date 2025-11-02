<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
  header('Location: /erp/login.php');
  exit;
}
// Verifica permissão de acesso à dashboard
require_once __DIR__ . '/includes/db_connect.php';
// Buscar nome e perfil do usuário logado
$usuario_id = $_SESSION['usuario_id'] ?? null;
$role = $_SESSION['role'] ?? '';
$user_name = 'Usuário';
if ($usuario_id) {
        $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = :id');
        $stmt->execute([':id' => $usuario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['nome'])) {
                $user_name = htmlspecialchars($row['nome']);
        }
}
if ($role !== 'admin' && $role !== 'superuser') {
} else {
    // Admin e superuser sempre têm acesso
    // Nenhuma verificação extra
}
if ($role !== 'admin' && $role !== 'superuser') {
    // Verifica permissão pelo array de chaves do perfil
    if (!isset($_SESSION['permissoes']) || !in_array('dashboard', $_SESSION['permissoes'])) {
        header('Location: /erp/login.php?erro=permissao');
        exit;
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
<style>
body.dark-theme {
    background: #23272f;
    color: #e3e6ee;
}
.dark-theme .dashboard-title {
    color: #22aaff;
}
.dark-theme .dashboard-container {
    background: #23272f;
}
.dark-theme .dashboard-welcome {
    color: #e3e6ee;
}
.dark-theme .dashboard-card {
    background: #23272f;
    color: #e3e6ee;
    border: 1px solid #31343a;
}
.dark-theme .dashboard-card h2 {
    color: #22aaff;
}
.dark-theme .dashboard-card p {
    color: #e3e6ee;
}
.dark-theme .dashboard-card .btn {
    color: #22aaff;
}
.dark-theme .dashboard-card .btn:hover {
    color: #fff;
}
.dark-theme .dashboard-side-menu {
    background: #23272f;
    color: #e3e6ee;
}
.dark-theme .dashboard-side-menu h3 {
    color: #22aaff;
}
.dark-theme .dashboard-side-menu .card-list button {
    background: #22aaff;
    color: #fff;
}
.dark-theme .dashboard-side-menu .card-list button:hover {
    background: #1857d8;
}
.dark-theme .dashboard-add-card-btn,
.dark-theme .dashboard-add-card-btn-fixed {
    background: #22aaff;
    color: #fff;
}
.dark-theme .dashboard-add-card-btn:hover,
.dark-theme .dashboard-add-card-btn-fixed:hover {
    background: #1857d8;
}
body {
    background: #f6f8fb;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
}
.dashboard-container {
    max-width: 1200px;
    margin: 64px auto 0 auto;
    padding: 0 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 80vh;
}
.dashboard-title {
    font-size: 2.6rem;
    font-weight: 800;
    color: #1857d8;
    margin-bottom: 18px;
    text-align: center;
    letter-spacing: 1px;
}
.dashboard-welcome {
    font-size: 1.15rem;
    margin-bottom: 40px;
    text-align: center;
    color: #222;
}
.dashboard-cards {
    display: flex;
    gap: 40px;
    justify-content: center;
    margin-bottom: 56px;
    flex-wrap: wrap;
}
.dashboard-card {
    background: #fff;
    border-radius: 22px;
    box-shadow: 0 8px 32px 0 rgba(24,87,216,0.10);
    padding: 38px 32px 28px 32px;
    min-width: 260px;
    max-width: 340px;
    flex: 1 1 260px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    margin-bottom: 18px;
    transition: box-shadow 0.18s, transform 0.18s;
    border: 1px solid #e3eaf5;
    position: relative;
}
.dashboard-card:hover {
    box-shadow: 0 16px 48px 0 rgba(24,87,216,0.18);
    transform: translateY(-4px) scale(1.03);
}
.dashboard-card h2 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1857d8;
    margin-bottom: 12px;
    letter-spacing: 0.5px;
}
.dashboard-card p {
    font-size: 1.05rem;
    color: #222;
    margin-bottom: 22px;
}
.dashboard-card .btn {
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    font-weight: 600;
    color: #1857d8;
    background: none;
    border: none;
    border-radius: 12px;
    padding: 0;
    font-size: 1.08em;
    text-decoration: none;
    box-shadow: none;
    margin-top: auto;
    transition: color 0.18s, text-decoration 0.18s;
    display: inline-block;
}
.dashboard-card .btn:hover {
    color: #0a318f;
    text-decoration: underline;
    background: none;
}
.dashboard-card .remove-card {
    position: absolute;
    top: 18px;
    right: 18px;
    background: #e74c3c;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 1.2em;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(24,87,216,0.08);
    transition: background 0.18s;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
}
.dashboard-card .remove-card:hover {
    background: #c0392b;
}
.dashboard-side-menu {
    position: fixed;
    top: 0;
    right: -400px;
    width: 340px;
    height: 100vh;
    background: #fff;
    box-shadow: -4px 0 24px rgba(24,87,216,0.10);
    border-radius: 16px 0 0 16px;
    transition: right 0.3s;
    z-index: 9999;
    padding: 32px 24px;
    display: flex;
    flex-direction: column;
}
.dashboard-side-menu.open {
    right: 0;
}
.dashboard-side-menu h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1857d8;
    margin-bottom: 18px;
}
.dashboard-side-menu .card-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.dashboard-side-menu .card-list button {
    background: #1857d8;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 24px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
}
.dashboard-side-menu .card-list button:hover {
    background: #22aaff;
}
.dashboard-side-menu .close-menu {
    position: absolute;
    top: 18px;
    right: 18px;
    background: #e74c3c;
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    font-size: 1.2em;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(24,87,216,0.08);
    transition: background 0.18s;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
}
.dashboard-side-menu .close-menu:hover {
    background: #c0392b;
}
.dashboard-add-card-btn {
    background: #1857d8;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 24px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 24px;
    transition: background 0.18s;
}
.dashboard-add-card-btn-fixed {
    position: fixed;
    top: 70px;
    right: 32px;
    z-index: 101;
    background: #1857d8;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 24px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(24,87,216,0.08);
    transition: background 0.18s;
}
.dashboard-add-card-btn:hover {
    background: #22aaff;
}
.dashboard-add-card-btn-fixed:hover {
    background: #22aaff;
}
@media (max-width: 900px) {
    .dashboard-cards {
        flex-direction: column;
        gap: 24px;
        align-items: center;
    }
    .dashboard-card {
        max-width: 98vw;
    }
    .dashboard-side-menu {
        width: 98vw;
        border-radius: 0;
        padding: 24px 12px;
    }
    .dashboard-add-card-btn-fixed {
        right: 8px;
        top: 62px;
        padding: 8px 16px;
        font-size: 0.95em;
    }
}
</style>
<div class="dashboard-container">
    <div class="dashboard-title">Bem-vindo ao ERP MTech</div>
    <div class="dashboard-welcome">Olá, <?= $user_name ?>!</div>
    <button class="dashboard-add-card-btn-fixed" onclick="openSideMenu()">+ Adicionar Card</button>
    <div class="dashboard-cards" id="dashboardCards"></div>
</div>
<div class="dashboard-side-menu" id="dashboardSideMenu">
    <button class="close-menu" onclick="closeSideMenu()">&times;</button>
    <h3>Adicionar Card</h3>
    <div class="card-list" id="addCardList"></div>
</div>
<script>
// Sincronizar tema escuro com header/localStorage
function applyThemeFromStorage() {
    const saved = localStorage.getItem('erp_theme');
    document.body.classList.toggle('dark-theme', saved === 'dark');
}
window.addEventListener('DOMContentLoaded', applyThemeFromStorage);
const userId = "<?= htmlspecialchars($_SESSION['usuario_id'] ?? 'anon') ?>";
const defaultCards = [
    { id: 'empresa', title: 'Empresa', desc: 'Personalize os dados da empresa, logo e visual.', link: 'configuracoes.php', linkText: 'Configurações' },
    { id: 'relatorios', title: 'Relatórios', desc: 'Consulte relatórios e informações do ERP.', link: 'relatorios_cad.php', linkText: 'Relatórios' }
];
const allCards = [
    ...defaultCards,
    { id: 'financeiro', title: 'Financeiro', desc: 'Controle financeiro, contas e fluxo de caixa.', link: 'caixas_bancos.php', linkText: 'Financeiro' },
    { id: 'servicos', title: 'Serviços', desc: 'Gerencie ordens de serviço e atendimento.', link: 'ordens_servico.php', linkText: 'Serviços' }
];
function getUserCards() {
    const saved = localStorage.getItem('dashboard_cards_' + userId);
    if (saved) {
        try { return JSON.parse(saved); } catch { return defaultCards.map(c => c.id); }
    }
    return defaultCards.map(c => c.id);
}
function setUserCards(cardIds) {
    localStorage.setItem('dashboard_cards_' + userId, JSON.stringify(cardIds));
}
function renderCards() {
    const cardIds = getUserCards();
    const cards = cardIds.map(id => allCards.find(c => c.id === id)).filter(Boolean);
    const container = document.getElementById('dashboardCards');
    container.innerHTML = '';
    cards.forEach(card => {
        const el = document.createElement('div');
        el.className = 'dashboard-card';
        el.innerHTML = `
            <button class="remove-card" title="Remover" onclick="removeCard('${card.id}')">&times;</button>
            <h2>${card.title}</h2>
            <p>${card.desc}</p>
            <a href="${card.link}" class="btn">${card.linkText}</a>
        `;
        container.appendChild(el);
    });
}
function removeCard(id) {
    let cardIds = getUserCards();
    cardIds = cardIds.filter(cid => cid !== id);
    setUserCards(cardIds);
    renderCards();
}
function openSideMenu() {
    document.getElementById('dashboardSideMenu').classList.add('open');
    renderAddCardList();
}
function closeSideMenu() {
    document.getElementById('dashboardSideMenu').classList.remove('open');
}
function renderAddCardList() {
    const cardIds = getUserCards();
    const addList = document.getElementById('addCardList');
    addList.innerHTML = '';
    allCards.forEach(card => {
        if (!cardIds.includes(card.id)) {
            const btn = document.createElement('button');
            btn.textContent = `+ ${card.title}`;
            btn.onclick = function() {
                setUserCards([...cardIds, card.id]);
                renderCards();
                renderAddCardList();
            };
            addList.appendChild(btn);
        }
    });
    if (addList.innerHTML === '') {
        addList.innerHTML = '<span style="color:#888;">Todos os cards já estão adicionados.</span>';
    }
}
document.addEventListener('DOMContentLoaded', renderCards);
</script>
