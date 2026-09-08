<?php

require_once __DIR__ . '/result_request.php';

function grf_region_code(string $region): ?string
{
    $regions = [
        'arusha' => '01', 'dar-es-salaam' => '02', 'dodoma' => '03', 'iringa' => '04',
        'kagera' => '05', 'kigoma' => '06', 'kilimanjaro' => '07', 'lindi' => '08',
        'mara' => '09', 'mbeya' => '10', 'morogoro' => '11', 'mtwara' => '12',
        'mwanza' => '13', 'pwani' => '14', 'rukwa' => '15', 'ruvuma' => '16',
        'shinyanga' => '17', 'singida' => '18', 'tabora' => '19', 'tanga' => '20',
        'manyara' => '21', 'geita' => '22', 'katavi' => '23', 'njombe' => '24',
        'simiyu' => '25', 'songwe' => '26'
    ];

    return $regions[$region] ?? null;
}

function grf_district_options(string $exam, int $year, string $region): array
{
    $regionCode = grf_region_code($region);
    if ($regionCode === null || !in_array($exam, ['psle', 'sfna'], true) || $year < 2010 || $year > 2026) {
        return [];
    }

    $url = "https://onlinesys.necta.go.tz/results/{$year}/{$exam}/results/reg_{$regionCode}.htm";
    $response = grf_fetch_result($url);
    if ($response['html'] === false || $response['status'] < 200 || $response['status'] >= 400) {
        return [];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($response['html']);
    libxml_clear_errors();
    $districts = [];

    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = $link->getAttribute('href');
        if (preg_match('/distr_(\d{4})\.htm/i', $href, $match)) {
            $name = trim(preg_replace('/\s+/', ' ', $link->textContent));
            if ($name !== '') {
                $districts[$match[1]] = $name;
            }
        }
    }

    asort($districts, SORT_NATURAL | SORT_FLAG_CASE);
    $options = [];
    foreach ($districts as $code => $name) {
        $options[] = ['code' => $code, 'name' => $name];
    }
    return $options;
}
