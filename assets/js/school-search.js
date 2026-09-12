(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const page = document.querySelector('.education-page.level-schools');
        const searchInput = document.getElementById('schoolSearch');
        const schoolList = document.getElementById('schoolList');
        const searchSummary = document.getElementById('schoolSearchSummary');
        const emptySearch = document.getElementById('schoolSearchEmpty');
        const recentList = document.getElementById('recentSchools');

        if (!page || !searchInput || !schoolList || !recentList) return;

        const schoolLinks = Array.from(schoolList.children).filter(function (item) {
            return item.matches('a');
        });
        const exam = page.dataset.exam || 'school';
        const storageKey = 'elimutaifa.recent-schools.' + exam;
        const locationTitle = document.querySelector('.intro-card .intro-title');
        const locationText = locationTitle
            ? locationTitle.textContent.replace(/\s+/g, ' ').trim()
            : '';

        function normalizeText(value) {
            const text = String(value || '').trim().toLocaleLowerCase('sw-TZ');
            return typeof text.normalize === 'function'
                ? text.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                : text;
        }

        function validatedRecentSchool(item) {
            if (!item || typeof item !== 'object') return null;

            const name = String(item.name || '').replace(/\s+/g, ' ').trim().slice(0, 180);
            const context = String(item.context || '').replace(/\s+/g, ' ').trim().slice(0, 140);

            try {
                const url = new URL(String(item.url || ''), window.location.href);
                if (!['http:', 'https:'].includes(url.protocol) || !name) return null;
                return { name: name, url: url.href, context: context };
            } catch (error) {
                return null;
            }
        }

        function loadRecentSchools() {
            try {
                const stored = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
                if (!Array.isArray(stored)) return [];
                return stored.map(validatedRecentSchool).filter(Boolean).slice(0, 2);
            } catch (error) {
                return [];
            }
        }

        let recentSchools = loadRecentSchools();

        function saveRecentSchools() {
            try {
                window.localStorage.setItem(storageKey, JSON.stringify(recentSchools.slice(0, 2)));
            } catch (error) {
                // Recent schools still work for the current page if storage is unavailable.
            }
        }

        function rememberSchool(entry) {
            const safeEntry = validatedRecentSchool(entry);
            if (!safeEntry) return;

            recentSchools = recentSchools.filter(function (item) {
                return item.url !== safeEntry.url;
            });
            recentSchools.unshift(safeEntry);
            recentSchools = recentSchools.slice(0, 2);
            saveRecentSchools();
            renderRecentSchools();
        }

        function renderRecentSchools() {
            recentList.replaceChildren();

            if (recentSchools.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'recent-schools-empty';
                empty.textContent = 'Hakuna shule iliyochaguliwa bado.';
                recentList.appendChild(empty);
                return;
            }

            recentSchools.forEach(function (school) {
                const link = document.createElement('a');
                const name = document.createElement('strong');
                const context = document.createElement('small');

                link.className = 'recent-school-link';
                link.href = school.url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                name.textContent = school.name;
                context.textContent = school.context || 'Shule iliyochaguliwa hivi karibuni';
                link.append(name, context);
                link.addEventListener('click', function () {
                    rememberSchool(school);
                });
                recentList.appendChild(link);
            });
        }

        function updateSearch() {
            const query = normalizeText(searchInput.value);
            let matches = 0;

            schoolLinks.forEach(function (link) {
                const schoolName = link.dataset.schoolName || link.textContent;
                const isMatch = !query || normalizeText(schoolName).includes(query);
                link.hidden = !isMatch;
                if (isMatch) matches += 1;
            });

            if (searchSummary) {
                searchSummary.textContent = query
                    ? matches + ' kati ya ' + schoolLinks.length + ' zimepatikana.'
                    : 'Shule ' + schoolLinks.length + ' zinapatikana.';
            }

            if (emptySearch) emptySearch.hidden = matches !== 0;
        }

        schoolLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                rememberSchool({
                    name: link.dataset.schoolName || link.textContent,
                    url: link.href,
                    context: locationText
                });
            });
        });

        searchInput.disabled = schoolLinks.length === 0;
        searchInput.addEventListener('input', updateSearch);
        renderRecentSchools();
        updateSearch();
    });
}());
