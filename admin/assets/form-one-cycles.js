/* URL suggestions do not replace live source verification. */
(function () {
    'use strict';
    function generatedSource(year, round) {
        const slug = round.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        if (!/^20\d{2}$/.test(year) || !slug) return '';
        return 'https://selection.tamisemi.go.tz/allocations/' + year + '/' + slug + '/index.html';
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = generatedSource;
    if (typeof document === 'undefined') return;
    const mode = document.getElementById('cycle-source-mode');
    const url = document.getElementById('cycle-source-url');
    const year = document.querySelector('[name="exam_year"]');
    const round = document.querySelector('[name="round_label"]');
    const intake = document.querySelector('[name="intake_year"]');
    const form = mode && mode.closest('form');
    const isFormFive = form && form.dataset.cycleModule === 'form-five';
    if (!mode || !url || !year || !round) return;
    function update() {
        url.readOnly = mode.value === 'auto';
        if (url.readOnly) {
            const sourceYear = isFormFive ? (intake && intake.value ? intake.value : (/^20\d{2}$/.test(year.value) ? String(Number(year.value) + 1) : '')) : year.value;
            url.value = generatedSource(sourceYear, round.value);
            if (isFormFive) url.value = url.value.replace('selection.tamisemi.go.tz/allocations/', 'selform.tamisemi.go.tz/content/selection-and-allocation/');
        }
        const preview = document.getElementById('cycle-source-preview');
        if (preview) preview.textContent = url.value;
    }
    year.addEventListener('input', update);
    if (intake) intake.addEventListener('input', update);
    round.addEventListener('input', update);
    round.addEventListener('change', update);
    url.addEventListener('input', update);
    mode.addEventListener('change', update);
    update();
}());
