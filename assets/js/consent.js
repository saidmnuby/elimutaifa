(function () {
    const consentKey = 'grf-consent-v1';

    function showConsent() {
        if (window.localStorage.getItem(consentKey) === 'accepted') return;

        const banner = document.createElement('section');
        banner.className = 'consent-banner';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-labelledby', 'consent-title');
        banner.innerHTML = `
            <div class="consent-copy">
                <strong id="consent-title">Privacy, terms and cookies</strong>
                <p>By continuing to use ElimuTaifa, you acknowledge our <a href="${getSitePath('privacy/')}">Privacy Policy</a> and <a href="${getSitePath('privacy/#terms')}">Terms of Service</a>. We use essential session cookies and temporary storage to operate and improve the service.</p>
            </div>
            <button class="consent-button" type="button">Accept &amp; continue</button>
        `;

        document.body.appendChild(banner);
        banner.querySelector('.consent-button').addEventListener('click', function () {
            window.localStorage.setItem(consentKey, 'accepted');
            banner.remove();
        });
    }

    function getSitePath(path) {
        const script = Array.from(document.scripts).find(function (item) {
            return /assets\/js\/consent\.js(?:\?|$)/.test(item.src);
        });
        const siteRoot = script ? new URL('../../', script.src) : new URL('/', window.location.href);
        return new URL(path, siteRoot).href;
    }

    function initializeSiteWideUi() {
        showConsent();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSiteWideUi);
    } else {
        initializeSiteWideUi();
    }
}());
