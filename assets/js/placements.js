(function () {
    'use strict';
    const source = document.currentScript;
    const base = new URL('../../', source.src);
    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = new URL('assets/css/placements.css', base).href;
    document.head.appendChild(stylesheet);

    function safeUrl(value, image) {
        try {
            const url = new URL(value, location.href);
            if (url.protocol === 'https:' || (url.origin === location.origin && (url.protocol === 'http:' || url.protocol === 'https:'))) return url.href;
        } catch (_) { /* Omit invalid URLs. */ }
        return null;
    }
    function createPlacement(item) {
                if (!['banner', 'card'].includes(item.format) || !['system', 'sponsor'].includes(item.kind)) return;
                const href = safeUrl(item.href);
                if (!href) return;
                const article = document.createElement('article');
                article.className = 'et-placement et-placement--' + item.format;
                const link = document.createElement('a');
                link.className = 'et-placement-link';
                link.href = href;
                link.rel = item.kind === 'sponsor' ? 'sponsored noopener noreferrer' : 'noopener noreferrer';
                if (item.external) link.target = '_blank';
                if (item.image_url) {
                    const imageUrl = safeUrl(item.image_url, true);
                    if (imageUrl) {
                        const image = document.createElement('img');
                        image.src = imageUrl; image.alt = ''; image.loading = 'lazy'; image.decoding = 'async'; image.referrerPolicy = 'no-referrer';
                        image.addEventListener('error', function () { image.remove(); });
                        link.appendChild(image);
                    }
                }
                const body = document.createElement('div'); body.className = 'et-placement-copy';
                const label = document.createElement('small');
                label.textContent = item.kind === 'sponsor' ? 'Tangazo la udhamini · ' + item.sponsor_name : 'Tangazo la ElimuTaifa';
                const title = document.createElement('strong'); title.textContent = item.title;
                body.append(label, title);
                if (item.description) { const description = document.createElement('p'); description.textContent = item.description; body.appendChild(description); }
                const action = document.createElement('span'); action.textContent = item.external ? 'Fungua taarifa ↗' : 'Soma taarifa →';
                body.appendChild(action); link.appendChild(body); article.appendChild(link);
                return article;
    }
    function render(items, slots) {
        slots.filter(function (slot) { return ['top', 'bottom'].includes(slot.dataset.etPlacementSlot); }).forEach(function (slot) {
            const matches = items.filter(function (item) { return item.slot === slot.dataset.etPlacementSlot; }).slice(0, slot.dataset.etPlacementSlot === 'top' ? 1 : 2);
            const fragment = document.createDocumentFragment();
            matches.forEach(function (item) {
                const article = createPlacement(item);
                if (article) fragment.appendChild(article);
            });
            if (fragment.childNodes.length) {
                slot.replaceChildren(fragment); slot.classList.add('et-placement-slot'); slot.hidden = false;
                slot.setAttribute('aria-label', 'Matangazo');
            }
        });
        const popupItem = items.find(function (item) { return item.slot === 'popup'; });
        if (!popupItem) return;
        const article = createPlacement(popupItem);
        if (!article) return;
        const storageKey = 'elimutaifa-placement-popup-' + popupItem.id;
        const mode = ['always', 'three'].includes(popupItem.display_mode) ? popupItem.display_mode : 'session';
        let views = 0;
        try {
            if (mode === 'session' && window.sessionStorage.getItem(storageKey) === 'seen') return;
            if (mode === 'three') { views = Number(window.localStorage.getItem(storageKey) || 0); if (views >= 3) return; }
        } catch (_) { /* Storage is optional. */ }
        const popup = document.createElement('aside');
        const interstitial = popupItem.popup_style === 'interstitial';
        popup.className = 'et-placement-popup' + (interstitial ? ' et-placement-popup--interstitial' : '');
        popup.setAttribute('role', 'dialog');
        popup.setAttribute('aria-label', popupItem.kind === 'sponsor' ? 'Tangazo la udhamini' : 'Tangazo la ElimuTaifa');
        const close = document.createElement('button');
        close.type = 'button'; close.className = 'et-placement-popup-close'; close.textContent = '×';
        const delay = interstitial ? Math.max(0, Math.min(30, Number(popupItem.skip_delay) || 0)) : 0;
        close.textContent = interstitial ? (delay ? 'Ruka baada ya ' + delay + 's' : 'Ruka tangazo') : '×';
        close.setAttribute('aria-label', interstitial ? 'Ruka tangazo' : 'Funga tangazo');
        close.disabled = delay > 0;
        close.addEventListener('click', function () {
            popup.remove();
            document.documentElement.classList.remove('et-placement-modal-open');
        });
        popup.append(close, article);
        document.documentElement.dataset.etPlacementPopup = '1';
        window.setTimeout(function () {
            try {
                if (mode === 'session') window.sessionStorage.setItem(storageKey, 'seen');
                if (mode === 'three') window.localStorage.setItem(storageKey, String(views + 1));
            } catch (_) { /* Storage is optional. */ }
            document.body.appendChild(popup);
            if (interstitial) document.documentElement.classList.add('et-placement-modal-open');
            if (delay) {
                let remaining = delay;
                const timer = window.setInterval(function () {
                    remaining -= 1;
                    close.textContent = remaining > 0 ? 'Ruka baada ya ' + remaining + 's' : 'Ruka tangazo';
                    if (remaining <= 0) { window.clearInterval(timer); close.disabled = false; }
                }, 1000);
            }
        }, 700);
    }
    async function init() {
        const slots = Array.from(document.querySelectorAll('[data-et-placement-slot]'));
        if (!slots.length) return;
        try {
            const preview = document.getElementById('etPlacementPreview');
            let payload;
            if (preview) { payload = JSON.parse(preview.textContent); }
            else {
                const endpoint = new URL('api/placements.php', base);
                endpoint.searchParams.set('page', slots[0].dataset.etPlacementPage);
                const controller = new AbortController();
                const timeout = setTimeout(function () { controller.abort(); }, 5000);
                try {
                    const response = await fetch(endpoint, { credentials: 'same-origin', signal: controller.signal });
                    if (!response.ok) return;
                    payload = await response.json();
                } finally { clearTimeout(timeout); }
            }
            if (Array.isArray(payload.items)) render(payload.items, slots);
        } catch (_) { /* Optional placements never block forms or results. */ }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
