document.addEventListener('DOMContentLoaded', function () {
    const menu = document.getElementById('menuToggle'), sidebar = document.getElementById('sidebar'), overlay = document.getElementById('sidebarOverlay');
    if (menu && sidebar && overlay) {
        const toggle = function () { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); };
        menu.addEventListener('click', toggle); overlay.addEventListener('click', toggle);
    }
    const input = document.getElementById('school-filter');
    if (!input) return;
    const page = document.querySelector('.education-page');
    const recentList = document.getElementById('recentSchools');
    let rememberRecentSchool = function () {};
    const exam = page ? page.dataset.exam : '';
    if (recentList && ['acsee', 'csee', 'ftna'].includes(exam)) {
        const storageKey = 'elimutaifa.recent-schools.' + exam;
        const maxRecentSchools = 2;
        const validateRecentSchool = function (item) {
            if (!item || typeof item !== 'object') return null;
            const code = String(item.code || '').trim().toUpperCase();
            const name = String(item.name || '').replace(/\s+/g, ' ').trim().slice(0, 180);
            const year = String(item.year || '').trim();
            if (!/^[SP]Q?\d{4}$/.test(code) || !/^\d{4}$/.test(year) || !name) return null;
            return { code: code, name: name, year: year };
        };
        let recentSchools = [];
        try {
            const stored = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
            if (Array.isArray(stored)) recentSchools = stored.map(validateRecentSchool).filter(Boolean).slice(0, maxRecentSchools);
        } catch (error) {
            recentSchools = [];
        }
        const saveRecentSchools = function () {
            try { window.localStorage.setItem(storageKey, JSON.stringify(recentSchools)); } catch (error) { /* Storage is optional. */ }
        };
        const renderRecentSchools = function () {
            recentList.replaceChildren();
            if (recentSchools.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'recent-schools-empty';
                empty.textContent = 'Hakuna shule iliyochaguliwa bado.';
                recentList.appendChild(empty);
                return;
            }
            recentSchools.forEach(function (school) {
                const url = new URL(window.location.href);
                url.search = '';
                url.searchParams.set('year', school.year);
                url.searchParams.set('school', school.code);
                const link = document.createElement('a');
                const name = document.createElement('strong');
                const context = document.createElement('small');
                link.className = 'recent-school-link';
                link.href = url.href;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                name.textContent = school.name;
                context.textContent = exam.toUpperCase() + ' · ' + school.year + ' · ' + school.code;
                link.append(name, context);
                link.addEventListener('click', function () { rememberRecentSchool(school, false); });
                recentList.appendChild(link);
            });
        };
        rememberRecentSchool = function (item, shouldRender) {
            const school = validateRecentSchool(item);
            if (!school) return;
            recentSchools = recentSchools.filter(function (recent) {
                return recent.code !== school.code || recent.year !== school.year;
            });
            recentSchools.unshift(school);
            recentSchools = recentSchools.slice(0, maxRecentSchools);
            saveRecentSchools();
            if (shouldRender !== false) renderRecentSchools();
        };
        renderRecentSchools();
        if (page.dataset.recentSchoolCode) {
            rememberRecentSchool({
                code: page.dataset.recentSchoolCode,
                name: page.dataset.recentSchoolName,
                year: page.dataset.recentSchoolYear
            });
        }
    }
    const mobileFilter = document.querySelector('.secondary-candidate-filter, .secondary-directory-filter');
    if (mobileFilter) {
        const home = document.createComment('mobile school search position');
        const originalParent = mobileFilter.parentNode;
        const media = window.matchMedia('(max-width: 700px)');
        const resultPanel = document.querySelector('.secondary-school-results');
        const resultList = document.querySelector('.secondary-school-list');
        const contextPanel = document.querySelector('.secondary-school-context-column');
        let viewportFrame = 0;
        const sizeResultPanel = function () {
            window.cancelAnimationFrame(viewportFrame);
            viewportFrame = window.requestAnimationFrame(function () {
                if (!resultPanel || !resultList || !contextPanel || !media.matches || mobileFilter.parentNode !== document.body) {
                    resultPanel?.classList.remove('secondary-viewport-results');
                    resultPanel?.style.removeProperty('--secondary-results-top');
                    resultPanel?.style.removeProperty('--secondary-results-height');
                    return;
                }
                const top = Math.ceil(contextPanel.getBoundingClientRect().bottom + 12);
                const bottom = Math.floor(mobileFilter.getBoundingClientRect().top - 12);
                resultPanel.classList.add('secondary-viewport-results');
                resultPanel.style.setProperty('--secondary-results-top', top + 'px');
                resultPanel.style.setProperty('--secondary-results-height', Math.max(190, bottom - top) + 'px');
            });
        };
        const placeMobileFilter = function () {
            if (media.matches && mobileFilter.parentNode !== document.body) {
                originalParent.insertBefore(home, mobileFilter);
                document.body.appendChild(mobileFilter);
            } else if (!media.matches && home.parentNode) {
                home.parentNode.insertBefore(mobileFilter, home);
                home.remove();
            }
            sizeResultPanel();
        };
        placeMobileFilter();
        media.addEventListener('change', placeMobileFilter);
        window.addEventListener('resize', sizeResultPanel);
        window.addEventListener('scroll', sizeResultPanel, { passive: true });
    }
    const count = document.getElementById('school-count');
    const localItems = Array.from(document.querySelectorAll('[data-school-search]'));
    const matches = document.getElementById('school-directory-results');
    if (!matches) {
        const empty = document.getElementById('school-empty');
        const filter = function () {
            const query = input.value.trim().toLocaleLowerCase(); let visible = 0;
            localItems.forEach(function (item) { item.hidden = !item.dataset.schoolSearch.toLocaleLowerCase().includes(query); if (!item.hidden) visible++; });
            if (count) count.textContent = visible + ' / ' + localItems.length + ' wanafunzi';
            if (empty) empty.hidden = visible > 0;
        };
        input.addEventListener('input', filter); filter(); return;
    }
    const directoryEmpty = document.getElementById('school-directory-empty');
    let timer = 0, controller = null;
    const render = function (items) {
        matches.replaceChildren();
        if (directoryEmpty) directoryEmpty.hidden = input.value.trim().length >= 2 || items.length > 0;
        items.forEach(function (item) {
            const link = document.createElement('a');
            link.className = 'secondary-school-match';
            link.href = '?year=' + encodeURIComponent(input.dataset.year) + '&school=' + encodeURIComponent(item.code);
            link.addEventListener('click', function () {
                rememberRecentSchool({ code: item.code, name: item.name, year: input.dataset.year }, false);
            });
            const name = document.createElement('strong'); name.textContent = item.name;
            const code = document.createElement('small'); code.textContent = item.code;
            link.append(name, code); matches.appendChild(link);
        });
    };
    const search = function () {
        const query = input.value.trim();
        window.clearTimeout(timer);
        if (query.length < 2) { if (controller) controller.abort(); render([]); count.textContent = 'Andika herufi 2 au zaidi.'; return; }
        timer = window.setTimeout(async function () {
            if (controller) controller.abort(); controller = new AbortController();
            const requestController = controller;
            const finish = window.ETPageLoading?.begin({scope:matches, title:'Inatafuta shule', message:'Tafadhali subiri huku orodha ya shule ikipakiwa.'}) || function () {};
            count.textContent = 'Inatafuta…';
            try {
                const url = new URL('api.php', location.href);
                url.searchParams.set('year', input.dataset.year); url.searchParams.set('q', query);
                const response = await fetch(url, {credentials:'same-origin', signal:requestController.signal});
                const data = await response.json();
                if (requestController.signal.aborted || controller !== requestController) return;
                if (!response.ok || !data.ok) throw new Error();
                render(data.items); count.textContent = data.items.length ? data.items.length + ' shule zimepatikana.' : 'Hakuna shule inayolingana.';
            } catch (error) {
                if (error.name !== 'AbortError' && controller === requestController) { render([]); count.textContent = 'Orodha haipatikani kwa sasa. Jaribu tena.'; }
            } finally { finish(); }
        }, 220);
    };
    input.addEventListener('input', search);
});
