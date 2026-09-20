document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('announcementList');
    const popup = document.getElementById('announcementPopup');
    const popupClose = document.getElementById('announcementClose');
    const popupLink = document.getElementById('announcementDetails');
    const popupTitle = document.getElementById('announcement-popup-title');
    const popupText = popupLink ? popupLink.querySelector('p') : null;

    function setExternalLink(link, href, external) {
        link.href = href;
        if (external) {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        } else {
            link.removeAttribute('target');
            link.removeAttribute('rel');
        }
    }

    function createAnnouncement(item) {
        const article = document.createElement('article');
        article.className = 'announcement-bar';
        let visual;
        if (item.media_type === 'image' && item.media_url) {
            visual = document.createElement('img');
            visual.className = 'announcement-thumbnail';
            visual.src = item.media_url;
            visual.alt = '';
            visual.loading = 'lazy';
            visual.decoding = 'async';
            visual.referrerPolicy = 'no-referrer';
        } else {
            visual = document.createElement('span');
            visual.className = 'announcement-icon';
            visual.setAttribute('aria-hidden', 'true');
            visual.textContent = item.media_type === 'youtube' ? '▶' : '!';
        }
        const copy = document.createElement('div');
        copy.className = 'announcement-copy';
        const label = document.createElement('span');
        label.className = 'announcement-label';
        label.textContent = item.category_label;
        const heading = document.createElement('h3');
        heading.textContent = item.title;
        const description = document.createElement('p');
        description.textContent = item.excerpt;
        copy.append(label, heading, description);
        const link = document.createElement('a');
        link.className = 'announcement-button';
        link.textContent = 'Soma taarifa →';
        setExternalLink(link, item.href, item.external);
        article.append(visual, copy, link);
        return article;
    }

    function showPopup() {
        if (!popup) return;
        if (document.documentElement.dataset.etPlacementPopup === '1') {
            popup.hidden = true;
            return;
        }
        popup.hidden = false;
        window.requestAnimationFrame(function () { popup.classList.add('is-visible'); });
    }

    function hidePopup(key) {
        if (!popup) return;
        popup.classList.remove('is-visible');
        if (key) {
            try { window.localStorage.setItem(key, 'seen'); } catch (error) { /* storage is optional */ }
        }
        window.setTimeout(function () { popup.hidden = true; }, 220);
    }

    function configurePopup(item) {
        if (!popup || !popupLink || !popupTitle || !popupText) return;
        if (document.documentElement.dataset.etPlacementPopup === '1') {
            popup.hidden = true;
            return;
        }
        if (!item) {
            popup.hidden = true;
            return;
        }
        const key = 'elimutaifa-announcement-' + item.id + '-' + String(item.updated_at || '').replace(/[^0-9]/g, '');
        popupTitle.textContent = item.title;
        popupText.textContent = item.excerpt;
        setExternalLink(popupLink, item.href, item.external);
        popupClose.onclick = function () { hidePopup(key); };
        popupLink.onclick = function () { hidePopup(key); };
        let wasSeen = false;
        try { wasSeen = window.localStorage.getItem(key) === 'seen'; } catch (error) { /* show when storage is unavailable */ }
        if (!wasSeen) window.setTimeout(showPopup, 900);
    }

    fetch('api/announcements.php', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (response) {
            if (!response.ok) throw new Error('Announcements unavailable');
            return response.json();
        })
        .then(function (data) {
            if (!data.ok || !list) return;
            list.replaceChildren();
            if (!data.items.length) {
                const empty = document.createElement('p');
                empty.className = 'announcement-empty';
                empty.textContent = 'Hakuna tangazo jipya kwa sasa. Rudi baadaye.';
                list.appendChild(empty);
            } else {
                data.items.forEach(function (item) { list.appendChild(createAnnouncement(item)); });
            }
            configurePopup(data.popup);
        })
        .catch(function () {
            const fallbackButton = document.getElementById('announcementOpen');
            const key = 'elimutaifa-announcement-platform-expansion-v1';
            if (fallbackButton) fallbackButton.addEventListener('click', showPopup);
            if (popupClose) popupClose.addEventListener('click', function () { hidePopup(key); });
            if (popupLink) popupLink.addEventListener('click', function () { hidePopup(key); });
            let wasSeen = false;
            try { wasSeen = window.localStorage.getItem(key) === 'seen'; } catch (error) { /* show fallback */ }
            if (!wasSeen) window.setTimeout(showPopup, 900);
        });
});
