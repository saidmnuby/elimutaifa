
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const pendingLinks = document.querySelectorAll('.pending-link');
            function toggleMenu() { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); }
            menuToggle.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);
            pendingLinks.forEach(function (link) { link.addEventListener('click', function (event) { event.preventDefault(); }); });
        });
    