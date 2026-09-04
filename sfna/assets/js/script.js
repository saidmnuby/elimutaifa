

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

    // 2. Standard Two / PSLE index validation
    function validateIndexNumber(val) {
        const cleaned = val.trim().toUpperCase();
        const nectaRegex = /^PS\d{7}-\d{4}$/i;
        return nectaRegex.test(cleaned);
    }

    // 3. Form submit validation
    if (!form || !candidateInput || !alertEl) return;

    form.addEventListener("submit", function (e) {
        const rawValue = candidateInput.value.trim();
        const yearSelect = document.getElementById("year");

        if (!rawValue) {
            e.preventDefault();
            showAlert("Tafadhali ingiza Namba ya Mtihani (Index Number).", "failed");
            candidateInput.focus();
            return;
        }

        if (!yearSelect || !yearSelect.value || yearSelect.value === "none") {
            e.preventDefault();
            showAlert("Tafadhali chagua mwaka wa mtihani.", "failed");
            yearSelect?.focus();
            return;
        }
        

        // Auto format to uppercase
        candidateInput.value = rawValue.toUpperCase();

        if (!validateIndexNumber(candidateInput.value)) {
            e.preventDefault();
            showAlert("Format ya Index Number siyo sahihi! Mfano sahihi: PS123456-0001", "warning");
            candidateInput.focus();
            return;
        }

        showAlert("Inathibitisha matokeo...", "success");
    });
});

