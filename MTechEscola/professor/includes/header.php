<div class="header">
    <span class="header-logo">MTech Escola - Professor</span>
    <nav>
        <a href="home_professor.php" class="header-link">Início</a>
        <a href="interface_horarios.php" class="header-link">Meu Horário</a>
        <div class="dropdown">
            <span class="header-link" id="diario-menu" style="cursor:pointer;">Diário ▼</span>
            <div class="submenu" id="submenu-diario">
                <a href="presenca.php">Frequência</a>
                <a href="cadastrar_conteudo_aula.php">Conteúdo por Aula</a>
                <a href="folha_chamada.php">Folha de Chamada</a>
            </div>
        </div>
        <a href="notas.php" class="header-link">Lançar Notas</a>
        <div class="dropdown">
            <span class="header-link" id="atividades-menu" style="cursor:pointer;">Atividades ▼</span>
            <div class="submenu" id="submenu-atividades">
                <a href="atividades.php">Cadastrar Atividade</a>
                <a href="gerenciar_atividades.php">Gerenciar Atividades</a>
            </div>
        </div>
        <a href="logout.php" class="header-link">Sair</a>
    </nav>
</div>
<style>
.header { position: fixed; top: 0; left: 0; right: 0; height: 64px; background: rgba(20,30,50,0.95); box-shadow: 0 2px 12px #0005; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; z-index: 1000; }
.header-logo { font-size: 1.5em; font-weight: 700; letter-spacing: 2px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 0 8px #00c3ff, 0 0 16px #ffff1c; }
.header-link { color: #fff; font-size: 1em; text-decoration: none; font-weight: 500; margin-left: 24px; transition: color 0.2s; }
.header-link:hover { color: #00c3ff; }
.dropdown { display:inline-block; position:relative; }
.submenu {
    display:none;
    position:absolute;
    left:0;
    top:28px;
    background:#22334a;
    border-radius:8px;
    box-shadow:0 2px 8px #0005;
    min-width:220px;
    z-index:2000;
}
.submenu a {
    display:block;
    padding:12px 16px;
    color:#fff;
    text-decoration:none;
    transition:background 0.2s;
}
.submenu a:hover {
    background:#00c3ff22;
    color:#00c3ff;
}
.dropdown:hover .submenu { display:block; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const drops = document.querySelectorAll('.dropdown');
    drops.forEach(drop => {
        const btn = drop.querySelector('.header-link');
        const menu = drop.querySelector('.submenu');
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.submenu').forEach(m => m.style.display = 'none');
    });
});
</script>
