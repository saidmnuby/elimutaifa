(function () {
    'use strict';
    const active = new Set();
    // Each request owns its card, so aborted/stale responses cannot hide newer work.
    window.ETPageLoading = {
        begin: function (options) {
            options = options || {};
            const scope = options.scope || document.body;
            if (options.scope) scope.classList.add('school-loading-scope');
            const overlay = document.createElement('div');
            overlay.className = 'school-loading-overlay' + (options.scope ? ' school-loading-inline' : '');
            overlay.hidden = true;
            overlay.setAttribute('role', 'status');
            overlay.setAttribute('aria-live', 'polite');
            overlay.innerHTML = '<div class="school-loading-card"><span class="school-loading-spinner" aria-hidden="true"></span><div><strong data-loading-title></strong><p data-loading-message></p></div></div>';
            overlay.querySelector('[data-loading-title]').textContent = options.title || 'Inapakia';
            overlay.querySelector('[data-loading-message]').textContent = options.message || 'Tafadhali subiri huku taarifa zikipakiwa.';
            scope.appendChild(overlay);
            let finished = false;
            const show = function () {
                if (finished) return;
                overlay.hidden = false;
                overlay.classList.add('is-visible');
            };
            const timer = options.delay === 0 ? null : window.setTimeout(show, options.delay == null ? 150 : options.delay);
            if (options.delay === 0) show();
            const finish = function () {
                if (finished) return;
                finished = true;
                if (timer !== null) window.clearTimeout(timer);
                overlay.remove();
                if (options.scope && !scope.querySelector('.school-loading-inline')) scope.classList.remove('school-loading-scope');
                active.delete(finish);
            };
            active.add(finish);
            return finish;
        }
    };
    const clear = function () { Array.from(active).forEach(function (finish) { finish(); }); };
    window.addEventListener('pageshow', clear);
    window.addEventListener('pagehide', clear);
    document.addEventListener('DOMContentLoaded', function () {
        const exam = document.body.dataset.exam;
        const selection = ['form-one', 'form-five'].includes(exam);
        if (!selection && !['acsee', 'csee', 'ftna', 'psle', 'sfna'].includes(exam)) return;
        function navigate(title, message) {
            clear();
            window.ETPageLoading.begin({title: title, message: message, delay: 0});
        }
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            const target = event.submitter?.formTarget || form.target;
            if (target && target !== '_self') return;
            const url = new URL(event.submitter?.hasAttribute('formaction') ? event.submitter.formAction : form.action, location.href);
            if (url.origin !== location.origin) return;
            const candidate = form.id === 'index1' || (selection && form.querySelector('[name="action"][value="candidate"]'));
            const school = /\/schools\/?$/.test(url.pathname) || form.matches('.school-search-action form');
            const browse = selection && form.querySelector('[name="action"][value="browse"]');
            if (!candidate && !school && !browse) return;
            // Wait for all validation handlers before deciding whether a request starts.
            queueMicrotask(function () {
                if (event.defaultPrevented) return;
                navigate(selection ? 'Inatafuta selection' : candidate ? 'Inatafuta matokeo' : 'Inatafuta shule',
                    selection ? 'Tafadhali subiri huku taarifa za uchaguzi zikipakiwa.' : 'Tafadhali subiri huku taarifa za matokeo zikipakiwa.');
            });
        });
        document.addEventListener('click', function (event) {
            if (!(event.target instanceof Element)) return;
            const link = event.target.closest('a.secondary-school-match, .schoolCard a[data-school-name], a.fo-list-button');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
            if (new URL(link.href, location.href).origin !== location.origin) return;
            queueMicrotask(function () {
                if (!event.defaultPrevented) navigate(selection ? 'Inatafuta selection ya shule' : 'Inatafuta matokeo ya shule', 'Tafadhali subiri huku taarifa zikipakiwa.');
            });
        });
    });
}());
