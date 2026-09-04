

    document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("index1");
    const candidateInput = document.getElementById("candidate");
    const alertEl = document.getElementById("alert-message");
    let alertTimer = null;

    // 1. Helper Function to Display Dynamic Alerts
    function showAlert(message, type = "failed") {
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

    // 2. Chip Option Click Handler (Sync with Hidden Inputs)
    function setupChipGroup(containerId, hiddenInputId) {
        const container = document.getElementById(containerId);
        const hiddenInput = document.getElementById(hiddenInputId);

        if (!container || !hiddenInput) return;

        const options = container.querySelectorAll(".option");
        options.forEach((opt) => {
            opt.addEventListener("click", function () {
                options.forEach((o) => {
                    o.classList.remove("active");
                    const check = o.querySelector(".check");
                    if (check) check.remove();
                });

                this.classList.add("active");
                this.innerHTML += '<span class="check">✓</span>';
                hiddenInput.value = this.getAttribute("data-value") || this.innerText.replace("✓", "").trim();
            });
        });
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

