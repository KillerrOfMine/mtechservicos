<!-- Header com menu hambúrguer -->
<div class="header-nav">
    <a href="home.php" class="btn-voltar">←</a>
    <h1><?= $page_title ?? 'MTech Escola' ?></h1>
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
</div>

<!-- Overlay do menu -->
<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

<!-- Menu lateral -->
<div class="menu-lateral" id="menuLateral">
    <div class="menu-header">
        <h2>Menu</h2>
        <button class="menu-close" onclick="toggleMenu()">✕</button>
    </div>
    <a href="home.php" class="menu-item">🏠 Início</a>
    <a href="horario.php" class="menu-item">📅 Meu Horário</a>
    <a href="../presenca.php" class="menu-item">📋 Frequência</a>
    <a href="diario.php" class="menu-item">📖 Diário</a>
    <a href="notas.php" class="menu-item">📊 Notas</a>
    <a href="atividades.php" class="menu-item">📝 Atividades</a>
    <a href="login.php" class="menu-item">🚪 Sair</a>
</div>

<script>
function toggleMenu() {
    document.getElementById('menuLateral').classList.toggle('ativo');
    document.getElementById('menuOverlay').classList.toggle('ativo');
}
</script>
