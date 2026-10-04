(function () {
    'use strict';
    const source = document.currentScript;
    const base = new URL('../../', source.src);
    const stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.href = new URL('assets/css/placements.css?v=20261004.1', base).href;
    document.head.appendChild(stylesheet);

    function safeUrl(value, image) {
        try {
            const url = new URL(value, location.href);
            if (url.protocol === 'https:' || (url.origin === location.origin && (url.protocol === 'http:' || url.protocol === 'https:'))) return url.href;
        } catch (_) { /* Omit invalid URLs. */ }
        return null;
    }
    function createPlacement(item) {
                if (['adsense','adsterra'].includes(item.provider)) {
                    const article=document.createElement('article');
                    article.className='et-network-placement'; article.dataset.networkId=item.network_id;
                    const label=document.createElement('small'); label.textContent='Tangazo'; article.append(label);
                    const area=document.createElement('div'); area.className='et-network-area'; article.append(area);
                    return article;
                }
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
                label.textContent = item.kind === 'sponsor' ? 'Sponsors · ' + item.sponsor_name : 'Tangazo la ElimuTaifa';
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
        popup.setAttribute('aria-label', popupItem.kind === 'sponsor' ? 'Sponsors' : 'Tangazo la ElimuTaifa');
        const close = document.createElement('button');
        close.type = 'button'; close.className = 'et-placement-popup-close'; close.textContent = '×';
        const delay = interstitial ? Math.max(0, Math.min(30, Number(popupItem.skip_delay) || 0)) : 0;
        close.textContent = interstitial ? (delay ? 'Ondoa baada ya ' + delay + 's' : 'ondoa tangazo') : '×';
        close.setAttribute('aria-label', interstitial ? 'Ondoa tangazo' : 'Funga tangazo');
        close.disabled = delay > 0;
        close.addEventListener('click', function () {
            popup.remove();
            document.documentElement.classList.remove('et-placement-modal-open');
        });
        if (interstitial) {
            const panel = document.createElement('div');
            panel.className = 'et-placement-modal-panel';
            const controls = document.createElement('div');
            controls.className = 'et-placement-modal-controls';
            const caption = document.createElement('span');
            caption.textContent = 'Tangazo';
            controls.append(caption, close);
            panel.append(controls, article);
            popup.appendChild(panel);
        } else {
            popup.append(close, article);
        }
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
                    close.textContent = remaining > 0 ? 'Ondoa baada ya ' + remaining + 's' : 'Ondoa tangazo';
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
            if (Array.isArray(payload.items)) {
                render(payload.items, slots);
                if (!preview) startNetworks(payload.items, slots[0].dataset.etPlacementPage);
            }
        } catch (_) { /* Optional placements never block forms or results. */ }
    }
    function startNetworks(items,page) {
        function signal(item,event) {
            if(navigator.doNotTrack==='1') return;
            fetch(new URL('api/ad-events.php',base),{method:'POST',credentials:'same-origin',keepalive:true,headers:{'Content-Type':'application/json'},body:JSON.stringify({id:item.network_id,page:page,event:event,expires:item.expires,token:item.token})}).catch(function(){});
        }
        let googleLoader=null;
        function google(client) {
            if(googleLoader) return googleLoader;
            googleLoader=new Promise(function(resolve,reject){
                const script=document.createElement('script'); script.async=true; script.crossOrigin='anonymous';
                script.src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='+encodeURIComponent(client);
                script.onload=resolve; script.onerror=reject; document.head.append(script);
            });
            return googleLoader;
        }
        document.querySelectorAll('.et-network-placement').forEach(function(article){
            const item=items.find(function(entry){return entry.network_id===article.dataset.networkId;});
            if(!item) return;
            signal(item,'mounted');
            // Local development never requests live ads or creates artificial impressions.
            if(['localhost','127.0.0.1','::1','[::1]'].includes(location.hostname)) return;
            const area=article.querySelector('.et-network-area'); let started=false;
            function load() {
                if(started) return; started=true;
                if(item.provider==='adsense') {
                    const ad=document.createElement('ins'); ad.className='adsbygoogle'; ad.style.display='block';
                    ad.dataset.adClient=item.publisher; ad.dataset.adSlot=item.unit_code; ad.dataset.adFormat='auto'; ad.dataset.fullWidthResponsive='true'; area.append(ad);
                    google(item.publisher).then(function(){
                        try { (window.adsbygoogle=window.adsbygoogle || []).push({}); signal(item,'loaded'); }
                        catch(_) { signal(item,'failed'); }
                    },function(){signal(item,'failed');});
                } else {
                    const container=document.createElement('div'); container.id='container-'+item.unit_code; area.append(container);
                    const script=document.createElement('script'); script.async=true; script.dataset.cfasync='false'; script.src=item.script_url;
                    script.onload=function(){signal(item,'loaded');}; script.onerror=function(){signal(item,'failed');}; area.insertBefore(script,container);
                }
            }
            if('IntersectionObserver' in window) {
                const observer=new IntersectionObserver(function(entries){if(entries.some(function(entry){return entry.isIntersecting;})){observer.disconnect();load();}},{rootMargin:'200px'});
                observer.observe(article);
            } else load();
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
