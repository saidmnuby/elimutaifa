(function () {
    'use strict';
    const start = function () {
        const cycle = document.getElementById('browse-cycle');
        if (!cycle) return;
        const form = cycle.closest('form');
        const card = form.closest('.card');
        const key = 'selection-directory:' + location.pathname;
        const panel = document.createElement('div');
        panel.className = 'selection-directory';
        const region = document.createElement('select');
        const council = document.createElement('select');
        const search = document.createElement('input');
        search.type = 'search'; search.placeholder = 'Tafuta jina au code ya shule';
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        const retry = document.createElement('button');
        retry.type = 'button'; retry.textContent = 'Jaribu tena'; retry.hidden = true;
        const list = document.createElement('div'); list.className = 'fo-list';
        const fields = new Map();
        function field(label, input, id) {
            const title = document.createElement('label'); title.className = 'form-label';
            title.htmlFor = id; title.textContent = label;
            input.id = id; input.className = 'form-input';
            const wrapper = document.createElement('div');
            wrapper.append(title, input); panel.append(wrapper); fields.set(input, wrapper);
        }
        field('Mkoa uliosoma', region, 'directory-region');
        field('Halmashauri', council, 'directory-council');
        field('Tafuta shule', search, 'directory-search');
        panel.append(status, retry, list);
        card.append(panel);
        let controller, version = 0, schools = [];
        const cache = new Map();
        function reset(select, label) {
            select.replaceChildren(new Option(label, '')); select.disabled = true;
        }
        function save() {
            try { sessionStorage.setItem(key, JSON.stringify({cycle: cycle.value, region: region.value, council: council.value, search: search.value})); } catch (_) {}
        }
        function render() {
            const query = search.value.trim().toLocaleLowerCase();
            const matches = schools.filter(s => (s.name + ' ' + s.id).toLocaleLowerCase().includes(query));
            list.replaceChildren();
            matches.forEach(s => {
                const link = document.createElement('a');
                link.className = 'fo-list-button'; link.textContent = s.name;
                const url = new URL('./', location.href);
                url.search = new URLSearchParams({action:'browse', cycle:cycle.value, region:region.value, council:council.value, school:s.id});
                link.href = url.href; link.addEventListener('click', save); list.append(link);
            });
            status.textContent = schools.length ? matches.length + ' / ' + schools.length + ' shule' : '';
        }
        async function entries(params, stage, signal) {
            const url = new URL('./', location.href);
            url.search = new URLSearchParams({action:'browse', directory:'1', cycle:cycle.value, ...params});
            if (cache.has(url.href)) return cache.get(url.href);
            const response = await fetch(url, {signal, credentials:'same-origin'});
            const data = await response.json();
            if (!response.ok || data.error) throw new Error(data.error || 'Chanzo hakipatikani kwa sasa. Jaribu tena.');
            const items = data.stage === stage && Array.isArray(data.entries) ? data.entries : [];
            if (!items.length) throw new Error('Orodha haijapatikana. Jaribu tena au tumia njia ya kawaida.');
            cache.set(url.href, items); return items;
        }
        async function load(level, restore) {
            if (controller) controller.abort();
            controller = new AbortController(); const signal = controller.signal; const current = ++version;
            schools = []; list.replaceChildren(); search.value = ''; search.disabled = true;
            fields.get(search).hidden = true;
            if (level === 'region') fields.get(council).hidden = true;
            retry.hidden = true;
            if (level === 'region') reset(region, 'Chagua mkoa');
            if (level !== 'school') reset(council, 'Chagua halmashauri');
            if ((level === 'council' && !region.value) || (level === 'school' && !council.value)) { status.textContent = ''; save(); return; }
            status.textContent = 'Inapakia…'; panel.setAttribute('aria-busy', 'true');
            try {
                const params = level === 'region' ? {} : level === 'council' ? {region:region.value} : {region:region.value, council:council.value};
                const items = await entries(params, level, signal);
                if (current !== version) return;
                if (level === 'school') { schools = items; search.disabled = false; fields.get(search).hidden = false; search.value = restore?.search || ''; render(); }
                else {
                    const select = level === 'region' ? region : council;
                    items.forEach(item => select.add(new Option(item.name, item.id))); select.disabled = false;
                    fields.get(select).hidden = false;
                    status.textContent = '';
                    if (restore && items.some(item => item.id === restore[level])) {
                        select.value = restore[level]; await load(level === 'region' ? 'council' : 'school', restore);
                    }
                }
                save();
            } catch (error) {
                if (error.name !== 'AbortError' && current === version) { status.textContent = error.message; retry.hidden = false; retry.onclick = () => load(level, restore); }
            } finally { if (current === version) panel.removeAttribute('aria-busy'); }
        }
        // Keep the original GET form available as a no-JS/network fallback.
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.textContent = 'Tumia njia ya kawaida';
            const fallback = document.createElement('details');
            const summary = document.createElement('summary');
            summary.textContent = 'Orodha haipatikani?';
            fallback.append(summary, button);
            form.append(panel, fallback);
            const guide = form.querySelector('p');
            if (guide && !panel.contains(guide)) guide.hidden = true;
        }
        search.addEventListener('keydown', event => { if (event.key === 'Enter') event.preventDefault(); });
        cycle.addEventListener('change', () => load('region'));
        const cycleLabel = form.querySelector('label[for="browse-cycle"]');
        if (cycleLabel) cycleLabel.textContent = 'Mwaka wa uchaguzi';
        if (cycle.options.length === 1) {
            cycle.hidden = true;
            if (cycleLabel) cycleLabel.hidden = true;
        }
        region.addEventListener('change', () => load('council'));
        council.addEventListener('change', () => load('school'));
        search.addEventListener('input', () => { render(); save(); });
        let saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(key)); } catch (_) {}
        if (saved && Array.from(cycle.options).some(option => option.value === saved.cycle)) cycle.value = saved.cycle;
        else saved = null;
        load('region', saved);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}());
