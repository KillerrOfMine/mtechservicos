document.addEventListener('DOMContentLoaded', function() {
  // Atividades menu
  var menuAtividades = document.getElementById('atividades-menu');
  var submenuAtividades = document.getElementById('submenu-atividades');
  menuAtividades.addEventListener('click', function(e) {
    e.stopPropagation();
    submenuAtividades.style.display = (submenuAtividades.style.display === 'block') ? 'none' : 'block';
  });
  // Cadastros menu
  var menuCadastros = document.getElementById('cadastros-menu');
  var submenuCadastros = document.getElementById('submenu-cadastros');
  menuCadastros.addEventListener('click', function(e) {
    e.stopPropagation();
    submenuCadastros.style.display = (submenuCadastros.style.display === 'block') ? 'none' : 'block';
  });
  // Diário menu
  var menuDiario = document.getElementById('diario-menu');
  var submenuDiario = document.getElementById('submenu-diario');
  menuDiario.addEventListener('click', function(e) {
    e.stopPropagation();
    submenuDiario.style.display = (submenuDiario.style.display === 'block') ? 'none' : 'block';
  });
  // Fecha todos os submenus ao clicar fora
  document.addEventListener('click', function(e) {
    if (submenuAtividades.style.display === 'block') {
      submenuAtividades.style.display = 'none';
    }
    if (submenuCadastros.style.display === 'block') {
      submenuCadastros.style.display = 'none';
    }
    if (submenuDiario.style.display === 'block') {
      submenuDiario.style.display = 'none';
    }
  });
});
