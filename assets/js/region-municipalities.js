(() => {
    "use strict";

    // Tanzania Mainland local government authorities, grouped by region.
    const municipalitiesByRegion = {
        arusha: ["Arusha City", "Arusha District", "Karatu", "Longido", "Meru", "Monduli", "Ngorongoro"],
        "dar-es-salaam": ["Dar es Salaam City", "Ilala Municipal", "Kinondoni Municipal", "Kigamboni Municipal", "Temeke Municipal", "Ubungo Municipal"],
        dodoma: ["Bahi", "Chamwino", "Chemba", "Dodoma City", "Kondoa", "Kongwa", "Mpwapwa", "Kondoa TC"],
        geita: ["Bukombe", "Chato", "Geita Town", "Mbogwe", "Nyang'hwale", "Geita"],
        iringa: ["Iringa Municipal", "Iringa District", "Kilolo", "Mafinga Town", "Mufindi"],
        kagera: ["Biharamulo", "Bukoba Municipal", "Bukoba District", "Karagwe", "Kyerwa", "Missenyi", "Muleba", "Ngara"],
        katavi: ["Mlele", "Mpanda Municipal", "Mpimbwe", "Tanganyika", "Nsimbo"],
        kigoma: ["Buhigwe", "Kakonko", "Kasulu District", "Kasulu Town", "Kibondo", "Kigoma District", "Kigoma Ujiji Municipal", "Uvinza"],
        kilimanjaro: ["Hai", "Moshi Municipal", "Moshi District", "Mwanga", "Rombo", "Same", "Siha"],
        lindi: ["Kilwa", "Lindi Municipal", "Mtama", "Liwale", "Nachingwea", "Ruangwa"],
        manyara: ["Babati District", "Babati Town", "Hanang", "Kiteto", "Mbulu", "Simanjiro", "Mbulu TC"],
        mara: ["Bunda District", "Bunda Town", "Butiama", "Musoma Municipal", "Musoma District", "Rorya", "Serengeti", "Tarime District", "Tarime Town"],
        mbeya: ["Busokelo", "Chunya", "Kyela", "Mbeya City", "Mbeya District", "Mbarali", "Rungwe"],
        morogoro: ["Gairo", "Ifakara Town", "Mlimba", "Kilosa", "Malinyi", "Morogoro Municipal", "Morogoro District", "Mvomero", "Ulanga"],
        mtwara: ["Masasi District", "Masasi Town", "Mtwara Municipal", "Mtwara District", "Nanyumbu", "Newala District", "Newala Town", "Tandahimba", "Nanyamba TC"],
        mwanza: ["Buchosa", "Ilemela Municipal", "Kwimba", "Magu", "Misungwi", "Mwanza CC", "Sengerema", "Ukerewe"],
        njombe: ["Ludewa", "Makambako Town", "Makete", "Njombe District", "Njombe Town", "Wanging'ombe"],
        pwani: ["Bagamoyo", "Chalinze", "Kibaha District", "Kibaha Town", "Kisarawe", "Mafia", "Mkuranga", "Rufiji", "Kibiti"],
        rukwa: ["Kalambo", "Nkasi", "Sumbawanga District", "Sumbawanga Municipal"],
        ruvuma: ["Madaba", "Mbinga District", "Mbinga Town", "Namtumbo", "Nyasa", "Songea District", "Songea Municipal", "Tunduru"],
        shinyanga: ["Kahama Municipal", "Kishapu", "Shinyanga District", "Shinyanga Municipal", "Ushetu", "Msalala"],
        simiyu: ["Bariadi District", "Bariadi Town", "Busega", "Itilima", "Maswa", "Meatu"],
        singida: ["Ikungi", "Iramba", "Manyoni", "Mkalama", "Singida District", "Singida Municipal"],
        songwe: ["Ileje", "Mbozi", "Momba", "Songwe", "Tunduma Town"],
        tabora: ["Igunga", "Kaliua", "Nzega District", "Nzega Town", "Sikonge", "Tabora Municipal", "Urambo", "Uyui"],
        tanga: ["Handeni District", "Handeni Town", "Kilindi", "Korogwe District", "Korogwe Town", "Lushoto", "Mkinga", "Muheza", "Pangani", "Tanga City"]
    };

    const optionValue = (name) => name.toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");

    const populateMunicipalities = async () => {
        const regionSelect = document.getElementById("region");
        const municipalitySelect = document.getElementById("municipality");
        if (!regionSelect || !municipalitySelect) return;

        const yearSelect = document.getElementById("yearb");
        const year = yearSelect?.value || "2025";
        const exam = document.body.dataset.exam;
        let districts = [];

        municipalitySelect.disabled = true;
        municipalitySelect.replaceChildren(new Option("Inapakia halmashauri...", ""));

        try {
            const response = await fetch(`districts.php?year=${encodeURIComponent(year)}&region=${encodeURIComponent(regionSelect.value)}`);
            const data = await response.json();
            districts = Array.isArray(data.districts) ? data.districts : [];
        } catch (error) {
            districts = [];
        }

        const municipalities = districts.length
            ? districts
            : (municipalitiesByRegion[regionSelect.value] || []).map((name) => ({ code: "", name }));
        municipalitySelect.replaceChildren();

        const placeholder = new Option(
            municipalities.length ? "Chagua halmashauri" : "Chagua mkoa kwanza",
            ""
        );
        placeholder.disabled = true;
        placeholder.selected = true;
        municipalitySelect.add(placeholder);

        municipalities.forEach((municipality) => {
            municipalitySelect.add(new Option(municipality.name, municipality.code || optionValue(municipality.name)));
        });
        municipalitySelect.disabled = municipalities.length === 0;
    };

    document.addEventListener("DOMContentLoaded", () => {
        const regionSelect = document.getElementById("region");
        if (!regionSelect) return;
        regionSelect.addEventListener("change", populateMunicipalities);
        document.getElementById("schoolResultsButton")?.addEventListener("click", () => {
            const municipalitySelect = document.getElementById("municipality");
            const year = document.getElementById("year")?.value || "2025";
            if (!municipalitySelect?.value) return;
            window.location.href = `district-results.php?year=${encodeURIComponent(year)}&district=${encodeURIComponent(municipalitySelect.value)}&exam=${encodeURIComponent(exam)}`;
        });
        populateMunicipalities();
    });
})();
