document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.announcement-tools');
    if (!form) return;
    const search = document.getElementById('announcement-search');
    const category = document.getElementById('announcement-category');
    const cards = Array.from(document.querySelectorAll('.public-card[data-category]'));
    const count = document.getElementById('announcement-count');
    const empty = document.getElementById('announcement-empty');
    const filter = function () {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        cards.forEach(function (card) {
            card.hidden = (category.value && card.dataset.category !== category.value) || !card.dataset.search.toLocaleLowerCase().includes(query);
            if (!card.hidden) visible++;
        });
        count.textContent = visible + ' / ' + cards.length + ' taarifa';
        empty.hidden = visible > 0 || cards.length === 0;
    };
    const clear = function () { search.value = ''; category.value = ''; filter(); };
    form.hidden = false;
    form.addEventListener('submit', function (event) { event.preventDefault(); filter(); });
    form.addEventListener('reset', function () { queueMicrotask(clear); });
    search.addEventListener('input', filter);
    category.addEventListener('change', filter);
    document.getElementById('announcement-clear').addEventListener('click', function () { clear(); search.focus(); });
    function openLinkedAnnouncement() {
        let id; try { id = decodeURIComponent(location.hash.slice(1)); } catch (_) { return; }
        const card = document.getElementById(id);
        if (!card || !card.matches('.public-card[data-category]')) return;
        clear();
        const details = card.querySelector('details');
        if (details) details.open = true;
        card.scrollIntoView({block:'start'});
    }
    window.addEventListener('hashchange', openLinkedAnnouncement);
    filter(); openLinkedAnnouncement();
});
