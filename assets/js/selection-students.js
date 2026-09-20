(function () {
    'use strict';
    function normalize(value) { return value.trim().toLowerCase().replace(/[\s./-]+/g, ''); }
    if (typeof module !== 'undefined' && module.exports) module.exports = normalize;
    if (typeof document === 'undefined') return;
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('selection-student-search');
        if (!input) return;
        const rows = Array.from(document.querySelectorAll('[data-selection-student]'));
        const count = document.getElementById('selection-student-count');
        const empty = document.getElementById('selection-student-empty');
        function filter() {
            const query = normalize(input.value);
            let visible = 0;
            rows.forEach(function (row) {
                row.hidden = !normalize(row.dataset.selectionStudent).includes(query);
                if (!row.hidden) visible++;
            });
            if (count) count.textContent = visible + ' / ' + rows.length + ' wanafunzi';
            if (empty) empty.hidden = visible !== 0;
        }
        input.addEventListener('input', filter);
        filter();
    });
}());
