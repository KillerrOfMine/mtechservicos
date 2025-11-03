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
<link rel="stylesheet" href="/MTechEscola/professor/assets/header.css?v=<?php echo time(); ?>">
<script src="/MTechEscola/professor/assets/header.js?v=<?php echo time(); ?>"></script>
