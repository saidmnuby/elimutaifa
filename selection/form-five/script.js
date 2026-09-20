document.addEventListener('DOMContentLoaded', function () {
    const menu = document.getElementById('menuToggle'), sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay');
    if (menu && sidebar && overlay) { menu.addEventListener('click', function () { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); }); overlay.addEventListener('click', function () { sidebar.classList.remove('open'); overlay.classList.remove('active'); }); }
    const input = document.getElementById('fo-filter'), items = Array.from(document.querySelectorAll('[data-fo-item]'));
    const count = document.getElementById('fo-count'), empty = document.getElementById('fo-empty');
    if (input) { const filter = function () { const q = input.value.trim().toLocaleLowerCase(); let visible = 0; items.forEach(function (item) { item.hidden = !item.dataset.foItem.toLocaleLowerCase().includes(q); if (!item.hidden) visible++; }); if (count) count.textContent = visible + ' / ' + items.length; if (empty) empty.hidden = visible !== 0; }; input.addEventListener('input', filter); filter(); }
});
