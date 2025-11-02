<!-- Header com menu hambúrguer -->
<div class="header-nav">
    <?php if ($is_professor): ?>
        <a href="professor/home.php" class="btn-voltar">←</a>
    <?php endif; ?>
    <h1><?= $page_title ?? ($is_professor ? 'Frequência' : 'MTech Escola') ?></h1>
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
    
    <?php if ($is_professor): ?>
        <a href="professor/home.php" class="menu-item">🏠 Início</a>
        <a href="professor/horario.php" class="menu-item">📅 Meu Horário</a>
        <a href="presenca.php" class="menu-item">📋 Frequência</a>
        <a href="professor/diario.php" class="menu-item">📖 Diário</a>
        <a href="professor/notas.php" class="menu-item">📊 Notas</a>
        <a href="professor/atividades.php" class="menu-item">📝 Atividades</a>
        <a href="professor/login.php" class="menu-item">🚪 Sair</a>
    <?php else: ?>
        <a href="dashboard.php" class="menu-item">🏠 Dashboard</a>
        <a href="presenca.php" class="menu-item">📋 Frequência</a>
        <a href="logout.php" class="menu-item">🚪 Sair</a>
    <?php endif; ?>
</div>

<script>
function toggleMenu() {
    document.getElementById('menuLateral').classList.toggle('ativo');
    document.getElementById('menuOverlay').classList.toggle('ativo');
}
</script>
