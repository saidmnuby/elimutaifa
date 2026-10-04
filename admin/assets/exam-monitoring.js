document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-exam-check]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
            form.setAttribute('aria-busy', 'true');
            const progress = form.querySelector('[data-exam-progress]');
            if (progress) progress.hidden = false;
            const button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; button.textContent = 'Checking source…'; }
        });
    });
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('[data-exam-check]').forEach(function (form) {
            form.removeAttribute('aria-busy');
            const button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = false; button.textContent = 'Check source'; }
            const progress = form.querySelector('[data-exam-progress]');
            if (progress) progress.hidden = true;
        });
    });
});
