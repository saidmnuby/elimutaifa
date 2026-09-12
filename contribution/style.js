document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const form = document.getElementById('contribution-form');
    const alertEl = document.getElementById('alert-message');

    function toggleMenu() {
        if (sidebar) sidebar.classList.toggle('open');
        if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
    }
    if (menuToggle) menuToggle.addEventListener('click', toggleMenu);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleMenu);
    document.querySelectorAll('.pending-link').forEach(function (link) {
        link.addEventListener('click', function (event) { event.preventDefault(); });
    });

    function showMessage(message, success) {
        if (!alertEl) return;
        alertEl.className = 'in-alert ' + (success ? 'success' : 'error') + ' show';
        alertEl.textContent = message;
        window.setTimeout(function () { alertEl.classList.remove('show'); }, 6000);
    }

    if (!form) return;
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
        const payload = Object.fromEntries(new FormData(form).entries());
        fetch('../api/submissions.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    if (!response.ok) throw new Error(data.message || 'Ujumbe haukuweza kutumwa.');
                    return data;
                });
            })
            .then(function (data) { showMessage(data.message, true); form.reset(); })
            .catch(function (error) { showMessage(error.message || 'Ujumbe haukuweza kutumwa.', false); })
            .finally(function () { if (button) button.disabled = false; });
    });
});
