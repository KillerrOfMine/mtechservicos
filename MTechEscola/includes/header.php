<div class="header">
    <span class="header-logo">MTech Escola</span>
    <nav>
  <a href="home.php" class="header-link">Dashboard</a>
  <a href="interface_horarios.php" class="header-link">Horário</a>
  <a href="usuarios.php" class="header-link">Usuários</a>
  <div class="dropdown">
    <span class="header-link" id="diario-menu" style="cursor:pointer;">Diário ▼</span>
    <div class="submenu" id="submenu-diario">
            <a href="presenca.php">Frequência</a>
            <a href="cadastrar_conteudo_aula.php">Conteúdo por Aula</a>
            <a href="folha_chamada.php">Folha de Chamada</a>
    </div>
  </div>
  <div class="dropdown">
    <span class="header-link" id="cadastros-menu" style="cursor:pointer;">Cadastros ▼</span>
    <div class="submenu" id="submenu-cadastros">
      <a href="alunos.php">Alunos</a>
      <a href="turmas.php">Turmas</a>
      <a href="disciplinas.php">Disciplinas</a>
      <a href="professores.php">Professores</a>
    </div>
  </div>
        <a href="notas.php" class="header-link">Lançar Notas</a>
                <div class="dropdown">
                    <span class="header-link" id="atividades-menu" style="cursor:pointer;">Atividades Avaliativas ▼</span>
                    <div class="submenu" id="submenu-atividades">
                        <a href="atividades.php">Cadastrar Atividade</a>
                        <a href="gerenciar_atividades.php">Gerenciar Atividades</a>
                    </div>
                </div>
        <a href="logout.php" class="header-link">Sair</a>
    </nav>
</div>
<link rel="stylesheet" href="/MTechEscola/assets/header.css?v=<?php echo time(); ?>">
<script src="/MTechEscola/assets/header.js?v=<?php echo time(); ?>"></script>
