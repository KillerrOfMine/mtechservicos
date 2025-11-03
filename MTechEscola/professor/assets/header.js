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
