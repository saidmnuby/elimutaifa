document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-email-setup]');
    if (!form) return;
    const provider = form.querySelector('[name="provider"]');
    function update() {
        form.querySelectorAll('[data-email-help]').forEach(function (help) { help.hidden = help.dataset.emailHelp !== provider.value; });
        form.querySelector('[data-email-secret-label]').textContent = provider.value === 'gmail' ? 'Google App Password' : 'Brevo API key';
    }
    provider.addEventListener('change', update); update();
    form.addEventListener('submit', function (event) {
        if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
        form.setAttribute('aria-busy', 'true');
        form.querySelector('button[type="submit"]').disabled = true;
        form.querySelector('[data-email-test-status]').hidden = false;
    });
    window.addEventListener('pageshow', function () {
        form.removeAttribute('aria-busy'); form.querySelector('button[type="submit"]').disabled = false;
        form.querySelector('[data-email-test-status]').hidden = true;
        form.querySelector('[name="email_secret"]').value = '';
    });
});
