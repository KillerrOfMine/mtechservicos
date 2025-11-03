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
