<?php
declare(strict_types=1);

/**
 * Canonical region, council and NECTA district-code directory for PSLE/SFNA.
 * Public selectors and server-side validation must both use this source.
 *
 * @return array<string, array<string, string>>
 */
function grf_district_directory(): array
{
    return [
        'arusha' => [
            'Arusha City' => '0102', 'Arusha District' => '0101', 'Karatu' => '0103',
            'Longido' => '0104', 'Meru' => '0105', 'Monduli' => '0106', 'Ngorongoro' => '0107',
        ],
        'dar-es-salaam' => [
            'Dar es Salaam City' => '0202', 'Kinondoni Municipal' => '0203',
            'Kigamboni Municipal' => '0205', 'Temeke Municipal' => '0206', 'Ubungo Municipal' => '0204',
        ],
        'dodoma' => [
            'Bahi' => '0301', 'Chamwino' => '0306', 'Chemba' => '0307', 'Dodoma City' => '0302',
            'Kondoa' => '0303', 'Kongwa' => '0305', 'Mpwapwa' => '0304', 'Kondoa TC' => '0308',
        ],
        'geita' => [
            'Bukombe' => '2401', 'Chato' => '2402', 'Geita Town' => '2403',
            'Mbogwe' => '2405', "Nyang'hwale" => '2406', 'Geita' => '2404',
        ],
        'iringa' => [
            'Iringa Municipal' => '0401', 'Iringa District' => '0402', 'Kilolo' => '0403',
            'Mafinga Town' => '0405', 'Mufindi' => '0404',
        ],
        'kagera' => [
            'Biharamulo' => '0501', 'Bukoba Municipal' => '0503', 'Bukoba District' => '0502',
            'Karagwe' => '0504', 'Kyerwa' => '0507', 'Missenyi' => '0508', 'Muleba' => '0505', 'Ngara' => '0506',
        ],
        'katavi' => [
            'Mlele' => '2501', 'Mpanda Municipal' => '2502', 'Mpimbwe' => '2505',
            'Tanganyika' => '2503', 'Nsimbo' => '2504',
        ],
        'kigoma' => [
            'Buhigwe' => '0605', 'Kakonko' => '0606', 'Kasulu District' => '0601', 'Kasulu Town' => '0608',
            'Kibondo' => '0602', 'Kigoma District' => '0603', 'Kigoma Ujiji Municipal' => '0604', 'Uvinza' => '0607',
        ],
        'kilimanjaro' => [
            'Hai' => '0701', 'Moshi Municipal' => '0703', 'Moshi District' => '0702', 'Mwanga' => '0704',
            'Rombo' => '0705', 'Same' => '0706', 'Siha' => '0707',
        ],
        'lindi' => [
            'Kilwa' => '0801', 'Lindi Municipal' => '0803', 'Mtama' => '0802',
            'Liwale' => '0804', 'Nachingwea' => '0805', 'Ruangwa' => '0806',
        ],
        'manyara' => [
            'Babati District' => '2101', 'Babati Town' => '2106', 'Hanang' => '2102', 'Kiteto' => '2103',
            'Mbulu' => '2104', 'Simanjiro' => '2105', 'Mbulu TC' => '2107',
        ],
        'mara' => [
            'Bunda District' => '0901', 'Bunda Town' => '0909', 'Butiama' => '0907',
            'Musoma Municipal' => '0903', 'Musoma District' => '0902', 'Rorya' => '0906',
            'Serengeti' => '0904', 'Tarime District' => '0905', 'Tarime Town' => '0908',
        ],
        'mbeya' => [
            'Busokelo' => '1009', 'Chunya' => '1001', 'Kyela' => '1003', 'Mbeya City' => '1005',
            'Mbeya District' => '1004', 'Mbarali' => '1008', 'Rungwe' => '1007',
        ],
        'morogoro' => [
            'Gairo' => '1107', 'Ifakara Town' => '1109', 'Mlimba' => '1101', 'Kilosa' => '1102',
            'Malinyi' => '1108', 'Morogoro Municipal' => '1104', 'Morogoro District' => '1103',
            'Mvomero' => '1106', 'Ulanga' => '1105',
        ],
        'mtwara' => [
            'Masasi District' => '1201', 'Masasi Town' => '1207', 'Mtwara Municipal' => '1203',
            'Mtwara District' => '1202', 'Nanyumbu' => '1206', 'Newala District' => '1204',
            'Newala Town' => '1209', 'Tandahimba' => '1205', 'Nanyamba TC' => '1208',
        ],
        'mwanza' => [
            'Buchosa' => '1308', 'Ilemela Municipal' => '1301', 'Kwimba' => '1302', 'Magu' => '1303',
            'Misungwi' => '1305', 'Mwanza CC' => '1304', 'Sengerema' => '1306', 'Ukerewe' => '1307',
        ],
        'njombe' => [
            'Ludewa' => '2601', 'Makambako Town' => '2603', 'Makete' => '2602',
            'Njombe District' => '2605', 'Njombe Town' => '2604', "Wanging'ombe" => '2606',
        ],
        'pwani' => [
            'Bagamoyo' => '1401', 'Chalinze' => '1408', 'Kibaha District' => '1402', 'Kibaha Town' => '1407',
            'Kisarawe' => '1403', 'Mafia' => '1404', 'Mkuranga' => '1406', 'Rufiji' => '1405', 'Kibiti' => '1409',
        ],
        'rukwa' => [
            'Kalambo' => '1501', 'Nkasi' => '1502', 'Sumbawanga District' => '1503', 'Sumbawanga Municipal' => '1504',
        ],
        'ruvuma' => [
            'Madaba' => '1608', 'Mbinga District' => '1601', 'Mbinga Town' => '1607', 'Namtumbo' => '1605',
            'Nyasa' => '1606', 'Songea District' => '1603', 'Songea Municipal' => '1604', 'Tunduru' => '1602',
        ],
        'shinyanga' => [
            'Kahama Municipal' => '1701', 'Kishapu' => '1702', 'Shinyanga District' => '1705',
            'Shinyanga Municipal' => '1704', 'Ushetu' => '1706', 'Msalala' => '1703',
        ],
        'simiyu' => [
            'Bariadi District' => '2702', 'Bariadi Town' => '2701', 'Busega' => '2703',
            'Itilima' => '2704', 'Maswa' => '2705', 'Meatu' => '2706',
        ],
        'singida' => [
            'Ikungi' => '1805', 'Iramba' => '1801', 'Itigi' => '1807', 'Manyoni' => '1802',
            'Mkalama' => '1806', 'Singida District' => '1803', 'Singida Municipal' => '1804',
        ],
        'songwe' => [
            'Ileje' => '3102', 'Mbozi' => '3103', 'Momba' => '3104', 'Songwe' => '3101', 'Tunduma Town' => '3105',
        ],
        'tabora' => [
            'Igunga' => '1901', 'Kaliua' => '1907', 'Nzega District' => '1902', 'Nzega Town' => '1908',
            'Sikonge' => '1906', 'Tabora Municipal' => '1903', 'Urambo' => '1905', 'Uyui' => '1904',
        ],
        'tanga' => [
            'Bumbuli' => '2011', 'Handeni District' => '2001', 'Handeni Town' => '2012', 'Kilindi' => '2008',
            'Korogwe District' => '2002', 'Korogwe Town' => '2009', 'Lushoto' => '2003', 'Mkinga' => '2010',
            'Muheza' => '2004', 'Pangani' => '2005', 'Tanga City' => '2007',
        ],
    ];
}

/** @return array<string, list<string>> */
function grf_public_region_directory(): array
{
    $publicDirectory = [];
    foreach (grf_district_directory() as $region => $councils) {
        $publicDirectory[$region] = array_keys($councils);
    }
    return $publicDirectory;
}

function grf_district_code_for_selection(string $region, string $council): ?string
{
    $region = strtolower(trim($region));
    $council = mb_strtolower(trim($council), 'UTF-8');
    $directory = grf_district_directory();

    if (!isset($directory[$region])) {
        return null;
    }

    foreach ($directory[$region] as $name => $code) {
        if (mb_strtolower($name, 'UTF-8') === $council) {
            return $code;
        }
    }

    return null;
}

/** @return array<string, string> */
function grf_flat_district_directory(): array
{
    $flatDirectory = [];
    foreach (grf_district_directory() as $councils) {
        foreach ($councils as $name => $code) {
            $flatDirectory[mb_strtolower($name, 'UTF-8')] = $code;
        }
    }
    return $flatDirectory;
}

// Backward-compatible variable for code that still consumes the old flat map.
$municipalitiesByRegion = grf_flat_district_directory();
