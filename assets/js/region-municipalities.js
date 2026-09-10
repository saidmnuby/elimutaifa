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


const regionSelect = document.getElementById("region");
const municipalitySelect = document.getElementById("municipality");

regionSelect.addEventListener("change", function () {

    // Mkoa uliochaguliwa
    const selectedRegion = this.value;

    // Safisha options za municipality
    municipalitySelect.innerHTML =
        '<option value="">-- Chagua Wilaya --</option>';

    // Kama hakuna region iliyochaguliwa
    if (!selectedRegion) {
        municipalitySelect.disabled = true;
        return;
    }

    // Chukua municipalities za region hiyo
    const municipalities = municipalitiesByRegion[selectedRegion];

    // Tengeneza options
    municipalities.forEach(function (municipality) {

        const option = document.createElement("option");

        option.value = municipality;
        option.textContent = municipality;

        municipalitySelect.appendChild(option);
    });

    // Enable second select
    municipalitySelect.disabled = false;
});