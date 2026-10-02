document.addEventListener('DOMContentLoaded', function () {
    const exam = document.body.dataset.exam;
    if (!['acsee', 'csee', 'ftna', 'psle', 'sfna'].includes(exam)) return;

    const overlay = document.createElement('div');
    overlay.className = 'school-loading-overlay';
    overlay.hidden = true;
    overlay.setAttribute('role', 'status');
    overlay.setAttribute('aria-live', 'polite');
    overlay.innerHTML = '<div class="school-loading-card"><span class="school-loading-spinner" aria-hidden="true"></span><div><strong data-loading-title></strong><p data-loading-message></p></div></div>';
    document.body.appendChild(overlay);

    const showLoading = function (title, message) {
        overlay.querySelector('[data-loading-title]').textContent = title;
        overlay.querySelector('[data-loading-message]').textContent = message;
        overlay.hidden = false;
        overlay.classList.add('is-visible');
    };

    document.addEventListener('submit', function (event) {
        if (!(event.target instanceof HTMLFormElement)) return;
        if (event.target.matches('.school-search-action form')) {
            showLoading('Inafungua utafutaji wa shule', 'Inapakia orodha ya mwaka uliochagua.');
        } else if (['psle', 'sfna'].includes(exam) && event.target.matches('form[action="schools/"]')) {
            showLoading('Inatafuta shule', 'Inapakia orodha ya shule za eneo ulilochagua.');
        }
    }, true);

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) return;
        const link = event.target.closest('a.secondary-school-match, .schoolCard a[data-school-name]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || link.target === '_blank') return;
        if (new URL(link.href, window.location.href).origin !== window.location.origin) return;
        showLoading('Inatafuta matokeo ya shule', 'Tafadhali subiri huku matokeo yakipakiwa.');
    }, true);

    window.addEventListener('pageshow', function () {
        overlay.classList.remove('is-visible');
        overlay.hidden = true;
    });
});