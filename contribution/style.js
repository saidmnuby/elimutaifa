
        document.addEventListener("DOMContentLoaded", function () {
            const menuToggle = document.getElementById("menuToggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebarOverlay");
            const form = document.getElementById("contribution-form");
            const alertEl = document.getElementById("alert-message");

            function toggleMenu() {
                sidebar.classList.toggle("open");
                sidebarOverlay.classList.toggle("active");
            }

            menuToggle.addEventListener("click", toggleMenu);
            sidebarOverlay.addEventListener("click", toggleMenu);

            form.addEventListener("submit", function (event) {
                event.preventDefault();
                alertEl.className = "in-alert success show";
                alertEl.textContent = "Ujumbe wako umetumwa. Asante kwa kushiriki.";
                form.reset();
                window.setTimeout(function () {
                    alertEl.classList.remove("show");
                }, 5000);
            });
        });
    