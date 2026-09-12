(function () {
    "use strict";

    const regionSelect = document.getElementById("region");
    const municipalitySelect = document.getElementById("municipality");
    const scriptElement = document.currentScript;

    if (!regionSelect || !municipalitySelect || !scriptElement) {
        return;
    }

    const directoryUrl = new URL("../../api/region-municipalities.php", scriptElement.src);
    const directoryRequest = fetch(directoryUrl, {
        credentials: "same-origin",
        headers: { Accept: "application/json" }
    }).then(function (response) {
        if (!response.ok) {
            throw new Error("Region directory request failed");
        }
        return response.json();
    });

    function resetMunicipalities(label) {
        municipalitySelect.replaceChildren();
        const option = document.createElement("option");
        option.value = "";
        option.textContent = label;
        municipalitySelect.appendChild(option);
    }

    regionSelect.addEventListener("change", async function () {
        const selectedRegion = this.value;
        municipalitySelect.disabled = true;

        if (!selectedRegion) {
            resetMunicipalities("-- Chagua mkoa kwanza --");
            return;
        }

        resetMunicipalities("Inapakia halmashauri...");

        try {
            const directory = await directoryRequest;
            const municipalities = Array.isArray(directory[selectedRegion])
                ? directory[selectedRegion]
                : [];

            resetMunicipalities(
                municipalities.length > 0
                    ? "-- Chagua halmashauri / wilaya --"
                    : "Hakuna halmashauri zilizopatikana"
            );

            municipalities.forEach(function (municipality) {
                const option = document.createElement("option");
                option.value = municipality;
                option.textContent = municipality;
                municipalitySelect.appendChild(option);
            });

            municipalitySelect.disabled = municipalities.length === 0;
        } catch (error) {
            resetMunicipalities("Halmashauri hazikuweza kupakiwa");
            municipalitySelect.disabled = true;
        }
    });
})();
