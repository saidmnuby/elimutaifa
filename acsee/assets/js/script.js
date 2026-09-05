 document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("index1");
    const candidateInput = document.getElementById("candidate");
    const alertEl = document.getElementById("alert-message");
    let alertTimer = null;

    // 1. Helper Function to Display Dynamic Alerts
    function showAlert(message, type = "failed") {
        if (!alertEl) return;

        alertEl.classList.remove("failed", "warning", "success", "show");
        alertEl.classList.add("in-alert", type);
        alertEl.textContent = message;
        
        // Restart animation reset
        alertEl.classList.remove("show");
        void alertEl.offsetWidth; // Trigger reflow
        alertEl.classList.add("show");

        if (alertTimer) clearTimeout(alertTimer);
        alertTimer = setTimeout(() => {
            alertEl.classList.remove("show");
        }, 4000);
    }

    setupChipGroup("exam-options", "exam_level");
    setupChipGroup("year-options", "exam_year");

    // 3. NECTA Index Number Validation
    function validateIndexNumber(val) {
        const cleaned = val.trim().toUpperCase();
        
        // Formats supported:
        // - Secondary: S1234/0001, P1234/0001, EQ1234/0001
        // - Primary / STD 7: PS123456-0001
        // Stronger NECTA index number validation
        const nectaRegex = /^(?:[SPE]Q?\d{4}\/\d{4})$/i;
        return nectaRegex.test(cleaned);
    }

    // 4. Form Submit Listener
    if (!form || !candidateInput || !alertEl) return;

    form.addEventListener("submit", function (e) {
        const rawValue = candidateInput.value.trim();

        if (!rawValue) {
            e.preventDefault();
            showAlert("Tafadhali ingiza Namba ya Mtihani (Index Number).", "failed");
            candidateInput.focus();
            return;
        }
        

        // Auto format to uppercase
        candidateInput.value = rawValue.toUpperCase();

        if (!validateIndexNumber(candidateInput.value)) {
            e.preventDefault();
            showAlert("Format ya Index Number siyo sahihi! Mfano sahihi: S3743/0037 au P2173/0002", "warning");
            candidateInput.focus();
            return;
        }

        showAlert("Inathibitisha matokeo...", "success");
    });
});


        document.addEventListener("DOMContentLoaded", function () {
            const menuToggle = document.getElementById("menuToggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebarOverlay");
            let alertTimer = null;

            // 1. Mobile Sidebar Toggle
            function toggleMenu() {
                sidebar.classList.toggle("open");
                sidebarOverlay.classList.toggle("active");
            }

            if (menuToggle) menuToggle.addEventListener("click", toggleMenu);
            if (sidebarOverlay) sidebarOverlay.addEventListener("click", toggleMenu);
        });

     function updateClock() {
      const now = new Date();
      
      // 1. Inapata Timezone ya kifaa cha mtumiaji kulingana na eneo lake (mfano: Africa/Dar_es_Salaam)
      const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

      // 2. Inapanga muundo wa saa, tarehe na sekunde kulingana na Timezone hiyo
      const timeFormatter = new Intl.DateTimeFormat('sw-TZ', {
        timeZone: userTimeZone,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false // Weka 'true' kama unataka AM/PM
      });

      const dateFormatter = new Intl.DateTimeFormat('sw-TZ', {
        timeZone: userTimeZone,
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });

      // 3. Inaweka matokeo kwenye HTML
      document.getElementById('live-clock').textContent = timeFormatter.format(now);
      document.getElementById('location-text').textContent = `${dateFormatter.format(now)} | ${userTimeZone}`;
     }

     // Isome mara moja na kuisasisha kila sekunde 1 (1000ms)
     updateClock();
     setInterval(updateClock, 1000);
  